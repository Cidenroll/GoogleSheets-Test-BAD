<?php

namespace Src\Domain\Service;

use SimpleXMLElement;
use Src\Application\Asset\History as HistoryAsset;
use Src\Application\Exception\SheetException;
use Src\Application\Interface\SheetInterface;
use Src\Domain\Entity\GoogleApiSheet;
use Src\Domain\Entity\GoogleApiSheetCollection;
use Src\Domain\Entity\History;
use Src\Domain\Entity\LocalSheet;
use Src\Domain\Entity\RemoteSheet;
use Src\Port\Adapters\RemoteFileConnector;


class UploadSheetService
{
    public function __construct(
        private readonly HistoryService $historyService,
        private readonly GoogleApiService $googleApiService
    )
    {
    }

    /**
     * @throws SheetException
     */
    public function uploadSheet(string $filename, string $locationType): ?HistoryAsset
    {
        $uploadSheet = match ($locationType) {
            SheetInterface::SHEET_LOCATION_LOCAL => new LocalSheet($filename),
            SheetInterface::SHEET_LOCATION_REMOTE => new RemoteSheet($filename)
        };

        ValidateSheetService::validate($uploadSheet);

        $historyModel =  new History($filename, false, $locationType, '');
        if ($uploadSheet instanceof LocalSheet) {
            $this->uploadLocal($historyModel, $uploadSheet);
        } else {
            $this->uploadRemote($historyModel, $uploadSheet);
        }

        $id = $this->historyService->create($historyModel);

        return new HistoryAsset(
            $id,
            $filename,
            $historyModel->isUploaded(),
            $locationType,
            $historyModel->getIdGoogleSpreadSheet()
        );
    }

    private function uploadLocal(History $history, SheetInterface $uploadSheet): void
    {
        $xml = simplexml_load_string(
            file_get_contents(
                sprintf("%s/%s", rtrim($uploadSheet->getLocation(), "/"), $uploadSheet->getFilename())
            ),
            'SimpleXMLElement',
            LIBXML_NOCDATA
        );

        $this->exportSheet($history, $xml);
    }


    private function uploadRemote(History $history, SheetInterface $uploadSheet): void
    {
        $xml = simplexml_load_string(
            file_get_contents(RemoteFileConnector::getInstance()->getRemoteFullPath($uploadSheet->getFileName())),
            'SimpleXMLElement',
            LIBXML_NOCDATA
        );

        $this->exportSheet($history, $xml);
    }

    private function exportSheet(History $history, SimpleXMLElement $xml): void
    {
        $apiSheetCollection = new GoogleApiSheetCollection();
        foreach ($xml as $element) {

            $apiSheetCollection->append(
                new GoogleApiSheet(
                    (string)$element->entity_id,
                    (float)$element->price,
                    (string)$element->link
                )
            );
        }

        try {
            $generatedSheetId = $this->googleApiService->createSheet($apiSheetCollection);
            $history->setUploaded(true)->setIdGoogleSpreadSheet($generatedSheetId);

        } catch (\Google\Service\Exception $e) {
            $history
                ->setIdGoogleSpreadSheet(sprintf("[%s] %s", $e->getCode(), current($e->getErrors())['message']));
        }
    }
}
