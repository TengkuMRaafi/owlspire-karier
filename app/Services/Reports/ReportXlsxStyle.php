<?php

namespace App\Services\Reports;

/**
 * "CSS" untuk semua berkas Excel (laporan admin dan data pengguna): warna, huruf, garis, dan format angka
 * hanya ada di sini. Warna mengikuti biru web (accent). Susunan lembar ada di XlsxTable, isi ada di
 * ReportXlsx dan UserXlsx, jadi mengubah tampilan Excel cukup menyentuh file ini.
 */
class ReportXlsxStyle
{
    public const FONT = 'Times New Roman';
    public const ACCENT = '2F58B3';       // teks judul dan latar kepala tabel (accent-700)
    public const ACCENT_DARK = '244A97';  // garis kepala tabel (accent-800)
    public const TAB = '3B6CD4';          // warna tab lembar (accent-600)
    public const TINT = 'EEF4FD';         // latar judul dan baris total (accent-50)
    public const TAG = 'E4EDFB';          // latar kolom label (role, jenis)
    public const SOFT = 'F9FAFB';         // baris selang-seling
    public const LINE = 'EAECF0';         // garis antar sel
    public const SUMMARY = 'F3F4F6';      // latar baris ringkasan
    public const MUTED = '6B7280';        // teks keterangan
    public const INK = '1B2638';          // teks isi

    /** Format angka: kunci nama jenis => kode format Excel. */
    public const FORMATS = [
        'int' => '#,##0',
        'dec' => '0.0',
        'pct' => '0%',
        'money' => '"Rp "#,##0',
    ];

    /** @return array<string, array> nama gaya => definisi (font, fill, border, align, format) */
    public static function definitions(): array
    {
        $box = fn (string $c) => ['left' => ['thin', $c], 'right' => ['thin', $c], 'top' => ['thin', $c], 'bottom' => ['thin', $c]];
        $s = [
            // Tiga baris pembuka: judul, keterangan, ringkasan
            'band' => ['font' => ['bold' => true, 'size' => 14, 'color' => self::ACCENT], 'fill' => self::TINT, 'align' => ['h' => 'center']],
            'band_sub' => ['font' => ['size' => 10, 'color' => self::MUTED], 'align' => ['h' => 'center']],
            'band_sum' => ['font' => ['bold' => true, 'size' => 10, 'color' => self::INK], 'fill' => self::SUMMARY, 'align' => ['h' => 'center']],
            'note' => ['font' => ['italic' => true, 'size' => 10, 'color' => self::MUTED], 'align' => ['h' => 'left', 'indent' => 1]],

            // Kepala tabel
            'th' => ['font' => ['bold' => true, 'size' => 11, 'color' => 'FFFFFF'], 'fill' => self::ACCENT, 'border' => $box(self::ACCENT_DARK), 'align' => ['h' => 'center', 'wrap' => true]],

            // Status (pesanan, akun)
            'st_ok' => ['font' => ['bold' => true, 'size' => 10.5, 'color' => '166534'], 'fill' => 'DCFCE7', 'border' => $box(self::LINE), 'align' => ['h' => 'center']],
            'st_wait' => ['font' => ['bold' => true, 'size' => 10.5, 'color' => '92400E'], 'fill' => 'FEF3C7', 'border' => $box(self::LINE), 'align' => ['h' => 'center']],
            'st_bad' => ['font' => ['bold' => true, 'size' => 10.5, 'color' => 'B91C1C'], 'fill' => 'FEE2E2', 'border' => $box(self::LINE), 'align' => ['h' => 'center']],
        ];

        // Sel data, dua warna selang-seling (a = putih, b = abu muda)
        foreach (['a' => null, 'b' => self::SOFT] as $z => $fill) {
            $base = ['font' => ['size' => 10.5, 'color' => self::INK], 'border' => $box(self::LINE)] + ($fill ? ['fill' => $fill] : []);
            $s["td_text_$z"] = $base + ['align' => ['h' => 'left', 'indent' => 1]];
            $s["td_center_$z"] = $base + ['align' => ['h' => 'center']];
            $s["td_tag_$z"] = ['fill' => self::TAG] + $base + ['align' => ['h' => 'center']];
            foreach (self::FORMATS as $kind => $code) {
                $s["td_{$kind}_$z"] = $base + ['format' => $code, 'align' => ['h' => 'right', 'indent' => 1]];
            }
        }

        // Baris total
        $tot = ['font' => ['bold' => true, 'size' => 10.5, 'color' => self::ACCENT], 'fill' => self::TINT, 'border' => $box(self::LINE)];
        $tot['border']['top'] = ['medium', self::ACCENT];
        $s['tot_text'] = $tot + ['align' => ['h' => 'left', 'indent' => 1]];
        $s['tot_center'] = $tot + ['align' => ['h' => 'center']];
        foreach (self::FORMATS as $kind => $code) {
            $s["tot_$kind"] = $tot + ['format' => $code, 'align' => ['h' => 'right', 'indent' => 1]];
        }

        // Satu jenis huruf untuk seluruh berkas
        foreach ($s as $name => $def) {
            $s[$name]['font']['name'] ??= self::FONT;
        }

        return $s;
    }

    /** Nama gaya sel data menurut jenis (text, center, tag, int, dec, pct, money) dan nomor baris (selang-seling). */
    public static function td(string $kind, int $i): string
    {
        return "td_{$kind}_".($i % 2 === 0 ? 'a' : 'b');
    }
}
