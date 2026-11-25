<?php

namespace App\Contracts;

/** Satu sumber lowongan = satu class. */
interface JobSourceAdapter
{
    public function key(): string;

    public function label(): string;

    public function note(): string;

    /** Terpilih otomatis di formulir pencarian. */
    public function defaultOn(): bool;

    /** False bila butuh API key yang belum diisi. */
    public function available(): bool;

    /**
     * @param array{positions: string[], skills: string[], locations: string[], hours: int, kind: string} $q
     * @return array<int, array> lowongan ternormalisasi (lihat Source::job)
     */
    public function fetch(array $q): array;
}
