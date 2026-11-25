<?php

namespace App\Services\Reports;

/**
 * Susunan satu lembar tabel yang dipakai semua ekspor Excel supaya tampilannya seragam:
 * judul, keterangan, ringkasan, jarak, kepala tabel, baris data selang-seling, (opsional) baris total.
 * Warna dan huruf tidak ada di sini; lihat ReportXlsxStyle.
 */
final class XlsxTable
{
    /**
     * @param  array{title: string, sub: string, summary: string}  $head
     * @param  array<int, array{0: string, 1: int|float, 2?: string}>  $columns  [judul, lebar, jenis: text|center|tag|int|dec|pct|money]
     * @param  array<int, array<int, mixed>>  $rows  nilai per kolom; sel boleh [nilai, 'nama-gaya'] untuk gaya khusus
     * @param  array<int, mixed>|null  $total  nilai baris total per kolom (kolom pertama = tulisan "Total")
     */
    public static function sheet(XlsxWriter $w, string $name, array $head, array $columns, array $rows, ?array $total = null, bool $numbered = true): XlsxSheet
    {
        $data = $columns;
        if ($numbered) {
            array_unshift($columns, ['No', 6, 'center']);
        }
        $n = count($columns);
        $last = self::letter($n);

        $s = $w->sheet($name, ReportXlsxStyle::TAB);
        foreach ($columns as $i => $c) {
            $s->width($i + 1, $c[1]);
        }

        $line = fn (string $text, string $style) => array_merge([[$text, $style]], array_fill(0, $n - 1, ['', $style]));
        $s->row($line($head['title'], 'band'), 28);
        $s->row($line($head['sub'], 'band_sub'), 18);
        $s->row($line($head['summary'], 'band_sum'), 20);
        $s->merge("A1:{$last}1")->merge("A2:{$last}2")->merge("A3:{$last}3");
        $s->row([], 6);

        $headRow = $s->row(array_map(fn ($c) => [$c[0], 'th'], $columns), 26);

        foreach (array_values($rows) as $i => $row) {
            $row = array_values($row);
            $cells = $numbered ? [[$i + 1, ReportXlsxStyle::td('center', $i)]] : [];
            foreach ($data as $k => $c) {
                $cells[] = self::cell($row[$k] ?? null, $c[2] ?? 'text', $i);
            }
            $s->row($cells, 19);
        }
        $lastData = count($s->rows);

        if (! $rows) {
            $r = $s->row([['Belum ada data untuk ditampilkan.', 'note']], 20);
            $s->merge("A{$r}:{$last}{$r}");
        }

        if ($total) {
            $cells = $numbered ? [['', 'tot_text']] : [];
            foreach ($data as $k => $c) {
                $kind = $c[2] ?? 'text';
                $style = isset(ReportXlsxStyle::FORMATS[$kind]) ? "tot_$kind" : ($kind === 'text' ? 'tot_text' : 'tot_center');
                $cells[] = [$total[$k] ?? '', $style];
            }
            $s->row($cells, 22);
        }

        return $s->freeze($headRow)->filter("A{$headRow}:{$last}{$lastData}");
    }

    private static function cell(mixed $v, string $kind, int $i): array
    {
        if (is_array($v)) {
            return [$v[0] ?? '', $v[1] ?? ReportXlsxStyle::td($kind, $i)];
        }
        if ($v === null || $v === '') {
            return ['-', ReportXlsxStyle::td(in_array($kind, ['text', 'tag'], true) ? $kind : 'center', $i)];
        }
        if (is_string($v) && isset(ReportXlsxStyle::FORMATS[$kind])) {
            $kind = 'center'; // tulisan seperti "-" di kolom angka
        }

        return [$v, ReportXlsxStyle::td($kind, $i)];
    }

    private static function letter(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m).$s;
            $n = intdiv($n - 1, 26);
        }

        return $s;
    }
}
