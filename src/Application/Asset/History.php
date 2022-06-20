<?php

namespace Src\Application\Asset;

class History
{
    public function __construct(
        private readonly int    $idHistory,
        private readonly string $fileName,
        private readonly bool   $uploaded,
        private readonly string $location,
        private readonly string $idGoogleSpreadSheet
    )
    {
    }

    public function getIdHistory(): int
    {
        return $this->idHistory;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function isUploaded(): bool
    {
        return $this->uploaded;
    }

    public function getIdGoogleSpreadSheet(): string
    {
        return $this->idGoogleSpreadSheet;
    }
}