<?php

namespace Src\Domain\Entity;

use Src\Application\Interface\SheetInterface;
use Src\Port\Adapters\RemoteFileConnector;

class RemoteSheet implements SheetInterface
{
    private string $location;

    private string $fileName;


    public function __construct(string $fileName)
    {
        $this->setLocation();
        $this->fileName = $fileName;
    }

    public function setLocation(): self
    {
        $this->location = $_ENV['REMOTE_URL'];
        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }
}