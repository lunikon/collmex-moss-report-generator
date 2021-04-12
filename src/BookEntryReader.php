<?php
declare(strict_types=1);

namespace App;

class BookEntryReader
{
    /** @var resource */
    private $handle;

    /** @var string[] */
    private array $headers;

    private ?array $row;

    public function __construct(string $filename)
    {
        $this->handle = fopen($filename, 'r');
        if ($this->readRow()) {
            $this->headers = $this->row ?? [];
        }
    }

    public function next(): ?BookEntry
    {
        if ($this->readRow())
            return BookEntry::fromCollmexExportLine(array_combine($this->headers, $this->row));
        else
            return null;
    }

    private function readRow(): bool
    {
        if (($row = fgetcsv($this->handle, 0, ';')) !== false) {
            $this->row = $row;
            return true;
        } else {
            $this->row = null;
            return false;
        }
    }
}