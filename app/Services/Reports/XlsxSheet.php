<?php

namespace App\Services\Reports;

/** Satu lembar kerja: baris, lebar kolom, gabung sel, bekukan baris, filter. Data murni, tanpa gaya. */
class XlsxSheet
{
    /** @var array<int, array{cells: array, height: ?float}> */
    public array $rows = [];

    /** @var array<int, float> nomor kolom (mulai 1) => lebar */
    public array $widths = [];

    /** @var string[] */
    public array $merges = [];

    public ?int $freezeAfterRow = null;

    public ?string $filter = null;

    public ?string $tabColor = null;

    public function __construct(public string $name) {}

    public function width(int $col, float $width): static
    {
        $this->widths[$col] = $width;

        return $this;
    }

    /**
     * @param  array<int, mixed>  $cells  tiap sel: nilai, atau [nilai, 'nama-gaya']
     * @return int nomor baris (mulai 1)
     */
    public function row(array $cells = [], ?float $height = null): int
    {
        $this->rows[] = ['cells' => $cells, 'height' => $height];

        return count($this->rows);
    }

    public function merge(string $range): static
    {
        $this->merges[] = $range;

        return $this;
    }

    public function freeze(int $afterRow): static
    {
        $this->freezeAfterRow = $afterRow;

        return $this;
    }

    public function filter(string $range): static
    {
        $this->filter = $range;

        return $this;
    }

    public function tab(string $hex): static
    {
        $this->tabColor = $hex;

        return $this;
    }
}
