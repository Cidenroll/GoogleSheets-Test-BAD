<?php

namespace Src\Application\Command;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\WebProcessor;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Src\Application\Asset\History;
use Src\Application\Controller\UploadController;
use Src\Application\Exception\SheetException;
use Src\Application\Interface\SheetInterface;
use Src\Domain\Container\Container;
use Src\Domain\Entity\LocalSheet;
use Src\Domain\Repository\ReadHistoryRepositoryInterface;
use Src\Domain\Repository\WriteHistoryRepositoryInterface;
use Src\Port\Repositories\ReadHistoryRepository;
use Src\Port\Repositories\WriteHistoryRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UploadCommand extends Command
{
    private ContainerInterface $container;

    public function __construct(
        string $name = null
    )
    {
        parent::__construct($name);

        $this->container = new Container();
        $this->container->set(ReadHistoryRepositoryInterface::class, ReadHistoryRepository::class);
        $this->container->set(WriteHistoryRepositoryInterface::class, WriteHistoryRepository::class);
        $this->container->set(SheetInterface::class, LocalSheet::class);
    }

    protected function configure()
    {
        $this->setName('gs:upload')
            ->setDescription('Upload document to Google Sheets')
            ->setHelp('Help message here')
            ->setDefinition(
                new InputDefinition([
                    new InputOption('filename', 'f', InputOption::VALUE_REQUIRED),
                    new InputOption('load-from', 'l', InputOption::VALUE_REQUIRED)
                ])
            );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface|ReflectionException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var UploadController $controller */
        $controller = $this->container->get(UploadController::class);

        $logger = new Logger('default');
        $logger->pushHandler(new StreamHandler($_ENV['ERROR_LOG_DIR']));
        $logger->pushProcessor(new WebProcessor());

        try {
            /** @var History $historyAsset */
            $historyAsset = $controller->upload(
                $input->getOption('filename'),
                $input->getOption('load-from'
                )
            );

            if ($historyAsset->isUploaded()) {
                $output->writeln(
                    "<info>Successfully processed and uploaded file {$input->getOption('filename')}"
                    . " from {$input->getOption('load-from')}</info>");
            } else {
                $output->writeln(
                    "<comment>Successfully processed file {$input->getOption('filename')}"
                    . " from {$input->getOption('load-from')}, but could not upload data to GoogleSheets. "
                    . "Error: {$historyAsset->getIdGoogleSpreadSheet()}</comment>"
                );

                $logger->addRecord(Logger::WARNING, 'Sheet exception occurred', [
                    'error' => $historyAsset->getIdGoogleSpreadSheet(),
                    'code' => '401'
                ]);
            }
        } catch (SheetException $e) {
            $output->writeln(
                "<error>Could not upload file {$input->getOption('filename')} "
                . "from {$input->getOption('load-from')}; Error: {$e->getMessage()}</error>");

            $logger->addRecord(Logger::WARNING, 'Sheet exception occurred', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}