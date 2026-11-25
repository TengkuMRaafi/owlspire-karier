<?php

namespace Tests\Unit;

use App\Services\Careers\CvInsights;
use App\Services\Careers\CvLayout;
use PHPUnit\Framework\TestCase;

/** Tata letak CV dua kolom dari pdf.js: urutan baca dan letak tiap bagian. */
class CvLayoutTest extends TestCase
{
    private function ln(string $t, float $x, float $y, float $w = 0.3, float $h = 0.013): array
    {
        return ['t' => $t, 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h];
    }

    /** CV dua kolom: kiri = kontak, pendidikan, kemampuan; kanan = nama, profil, pengalaman. */
    private function twoColumn(): array
    {
        $L = fn (...$a) => $this->ln(...$a);

        return ['total' => 1, 'pages' => [['lines' => [
            $L('TAKEHIRO', 0.50, 0.11, 0.35, 0.05), $L('KANEGI', 0.50, 0.17, 0.26, 0.05), $L('AUTHOR / PENULIS', 0.53, 0.25, 0.22, 0.017),
            $L('+123-456-7890', 0.18, 0.36, 0.15, 0.015), $L('hello@reallygreatsite.com', 0.18, 0.39, 0.25, 0.015),
            $L('PROFIL', 0.56, 0.344, 0.09, 0.018),
            $L('Memiliki bekal pendidikan di Borcelle', 0.51, 0.388, 0.31), $L('University, jurusan Sastra Indonesia', 0.51, 0.41, 0.31),
            $L('PENDIDIKAN', 0.20, 0.581, 0.165, 0.018), $L('PENGALAMAN KERJA', 0.555, 0.583, 0.28, 0.018),
            $L('(2018-2022)', 0.15, 0.625, 0.12), $L('Universitas Borcelle', 0.15, 0.644, 0.19), $L('Sastra Indonesia', 0.15, 0.669, 0.15),
            $L('Penulis Freelancer (2020 - Sekarang)', 0.51, 0.626, 0.36), $L('Menulis novel fiksi untuk dewasa dan', 0.53, 0.653, 0.31),
            $L('KEMAMPUAN', 0.20, 0.821, 0.176, 0.018), $L('Menulis kreatif.', 0.16, 0.864, 0.13), $L('Editing.', 0.16, 0.881, 0.06),
        ]]]];
    }

    public function test_cv_dua_kolom_dibaca_per_kolom_dengan_judul_menyertai_isinya(): void
    {
        $lay = new CvLayout(new CvInsights());
        $text = $lay->text($lay->segments($this->twoColumn()['pages']));
        $lines = explode("\n", $text);

        // Kepala dulu (nama di kolom kanan karena tulisannya terbesar, lalu kontak di kiri), lalu tiap bagian menurut letaknya.
        $this->assertSame(['TAKEHIRO', 'KANEGI', 'AUTHOR / PENULIS', '+123-456-7890', 'hello@reallygreatsite.com', 'PROFIL'], array_slice($lines, 0, 6));
        $this->assertSame('Memiliki bekal pendidikan di Borcelle', $lines[6]);
        $this->assertLessThan(array_search('PENGALAMAN KERJA', $lines), array_search('PENDIDIKAN', $lines) ?: 99, 'dua bagian sejajar tetap utuh, bukan bercampur');
        $this->assertSame(['Menulis kreatif.', 'Editing.'], array_slice($lines, -2));
        $this->assertSame('KEMAMPUAN', $lines[count($lines) - 3]);

        // Isi pendidikan tidak tercampur dengan isi pengalaman.
        $edu = array_search('PENDIDIKAN', $lines);
        $this->assertSame(['PENDIDIKAN', '(2018-2022)', 'Universitas Borcelle', 'Sastra Indonesia'], array_slice($lines, $edu, 4));
    }

    public function test_blok_pratinjau_dapat_kotak_di_kolom_yang_benar(): void
    {
        $in = new CvInsights();
        $lay = new CvLayout($in);
        $pages = $this->twoColumn()['pages'];
        $text = $lay->text($lay->segments($pages));
        $blocks = $lay->attach($in->blocks($text), $pages);
        $by = array_column($blocks, null, 'key');

        $this->assertNotEmpty($by['ringkasan']['lines'], 'ringkasan tidak lagi kosong');
        $this->assertGreaterThan(0.45, $by['ringkasan']['rects'][0]['x']);
        $this->assertLessThan(0.45, $by['pendidikan']['rects'][0]['x']);
        $this->assertGreaterThan(0.45, $by['pengalaman']['rects'][0]['x']);
        $this->assertLessThan(0.45, $by['skill']['rects'][0]['x']);
        $this->assertCount(2, $by['kontak']['rects'], 'kepala di dua kolom: nama di kanan, kontak di kiri');
        foreach ($blocks as $b) {
            foreach ($b['rects'] as $r) {
                $this->assertGreaterThanOrEqual(0, $r['x']);
                $this->assertLessThanOrEqual(1.0001, $r['x'] + $r['w']);
                $this->assertLessThanOrEqual(1.0001, $r['y'] + $r['h']);
            }
        }
    }

    public function test_cv_satu_kolom_tetap_berurutan_dari_atas_ke_bawah(): void
    {
        $lay = new CvLayout(new CvInsights());
        $rows = [['NAMA SAYA', 0.1, 0.05], ['saya@mail.com', 0.1, 0.09], ['PENDIDIKAN', 0.1, 0.2], ['S1 Informatika 2020', 0.1, 0.24], ['KEAHLIAN', 0.1, 0.4], ['SQL, Excel', 0.1, 0.44],
            ['PENGALAMAN', 0.1, 0.6], ['Menganalisis data penjualan 20%', 0.1, 0.64]];
        $pages = [['lines' => array_map(fn ($r) => $this->ln($r[0], $r[1], $r[2], 0.6), $rows)]];

        $lines = explode("\n", $lay->text($lay->segments($pages)));
        $this->assertSame('NAMA SAYA', $lines[0]);
        $this->assertSame(['NAMA SAYA', 'saya@mail.com', 'PENDIDIKAN', 'S1 Informatika 2020', 'KEAHLIAN', 'SQL, Excel', 'PENGALAMAN', 'Menganalisis data penjualan 20%'], $lines);
    }

    public function test_kiriman_browser_dibersihkan_dan_yang_tidak_wajar_ditolak(): void
    {
        $lay = new CvLayout(new CvInsights());

        $this->assertNull($lay->sanitize('bukan json'));
        $this->assertNull($lay->sanitize(['pages' => 'x']));
        $this->assertNull($lay->sanitize(['pages' => [['lines' => [['t' => 'a', 'x' => 0, 'y' => 0, 'w' => .1, 'h' => .1]]]]]), 'terlalu sedikit baris');

        $dirty = ['total' => 9, 'pages' => [['lines' => [
            ['t' => '  Halo   dunia  ', 'x' => -5, 'y' => 9, 'w' => 7, 'h' => 5],
            ['t' => 'dua', 'x' => 0.1, 'y' => 0.1, 'w' => 0.1, 'h' => 0.01],
            ['t' => 'tiga', 'x' => 0.1, 'y' => 0.2, 'w' => 0.1, 'h' => 0.01],
            ['t' => '', 'x' => 0.1, 'y' => 0.3, 'w' => 0.1, 'h' => 0.01],
            ['t' => 'tanpa posisi'],
            'bukan-array',
        ]]]];
        $clean = $lay->sanitize($dirty);
        $this->assertCount(3, $clean['pages'][0]['lines']);
        $this->assertSame('Halo dunia', $clean['pages'][0]['lines'][0]['t']);
        $this->assertSame(['x' => 0.0, 'y' => 1.0, 'w' => 1.0, 'h' => 0.2], array_intersect_key($clean['pages'][0]['lines'][0], array_flip(['x', 'y', 'w', 'h'])));
        $this->assertSame(9, $clean['total']);

        $many = ['pages' => array_fill(0, 7, ['lines' => array_fill(0, 3, ['t' => 'x', 'x' => 0.1, 'y' => 0.1, 'w' => 0.1, 'h' => 0.01])])];
        $this->assertCount(CvLayout::MAX_PAGES, $lay->sanitize($many)['pages']);
    }
}
