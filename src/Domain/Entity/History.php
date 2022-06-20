<?php

namespace Src\Domain\Entity;

class History
{
    private int $idHistory;

    public function __construct(
        private readonly string $fileName,
        private bool $uploaded,
        private readonly string $location,
        private string $idGoogleSpreadSheet
    )
    {
    }

    public function getIdHistory(): int
    {
        return $this->idHistory;
    }

    public function setIdHistory(int $idHistory): History
    {
        $this->idHistory = $idHistory;
        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function isUploaded(): bool
    {
        return $this->uploaded;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getIdGoogleSpreadSheet(): string
    {
        return $this->idGoogleSpreadSheet;
    }

    public function withIdHistory(int $idHistory): History
    {
        $history = clone $this;
        $history->idHistory = $idHistory;
        return $history;
    }

    public function setUploaded(bool $uploaded): History
    {
        $this->uploaded = $uploaded;
        return $this;
    }

    public function setIdGoogleSpreadSheet(string $idGoogleSpreadSheet): History
    {
        $this->idGoogleSpreadSheet = $idGoogleSpreadSheet;
        return $this;
    }
}