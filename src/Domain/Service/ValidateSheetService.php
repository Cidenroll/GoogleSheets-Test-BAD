<?php

namespace Src\Domain\Service;

use Exception;
use Src\Application\Exception\SheetException;
use Src\Application\Interface\SheetInterface;
use Src\Domain\Entity\LocalSheet;
use Src\Domain\Entity\RemoteSheet;
use Src\Port\Adapters\RemoteFileConnector;

class ValidateSheetService
{
    /**
     * @throws SheetException
     */
    public static function validate(SheetInterface $sheet): void
    {
        if (!($sheet instanceof LocalSheet || $sheet instanceof RemoteSheet)) {
            throw new SheetException('Invalid location given.');
        }

        if ($sheet instanceof LocalSheet) {
            static::validateLocally($sheet);
        }

        if ($sheet instanceof RemoteSheet) {
            static::validateRemotely($sheet);
        }
    }

    /**
     * @throws SheetException
     */
    private static function validateLocally(SheetInterface $sheet): void
    {
        if (empty($sheet->getLocation())) {
            throw new SheetException('Missing export sheet location.');
        }
        if (empty($sheet->getFilename())) {
            throw new SheetException('Missing file name.');
        }

        $location = sprintf("%s/%s", rtrim($sheet->getLocation(), "/"), $sheet->getFilename());
        if (@file_get_contents($location) === false || empty(@file_get_contents($location))) {
            throw new SheetException('Empty xml contents for ' . $sheet->getFilename());
        }
    }

    /**
     * @throws SheetException
     */
    private static function validateRemotely(SheetInterface $sheet): void
    {
        /** @var RemoteSheet $remoteSheet */
        $remoteSheet = $sheet;

        if (empty($remoteSheet->getLocation())) {
            throw new SheetException('Missing export sheet location to target.');
        }
        if (empty($remoteSheet->getFilename())) {
            throw new SheetException('Missing file name to target.');
        }

        try {
            $remotePath = RemoteFileConnector::getInstance()->getRemoteFullPath($remoteSheet->getFileName());
            $handle = @fopen($remotePath, 'rb');
            if ($handle === false) {
                throw new Exception("File could not be found on the remote server.");
            }
            @fclose($handle);
        } catch (Exception $e) {
            throw new SheetException("A FTP connection problem has occurred: {$e->getMessage()}");
        }
    }
}