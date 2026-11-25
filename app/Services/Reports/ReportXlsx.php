<?php

namespace App\Services\Reports;

/** Menyusun laporan admin menjadi berkas Excel: lembar Ringkasan, Ujian Seleksi, Karier, dan Pesanan (admin). */
class ReportXlsx
{
    /** @param array $d hasil ReportController::data() */
    public function build(array $d): string
    {
        $w = new XlsxWriter(ReportXlsxStyle::definitions());
        $made = now()->timezone(config('career_trend.timezone', 'Asia/Jakarta'))->format('d/m/Y H:i T');
        $sub = 'Periode: '.$d['label'].'     |     Tanggal Export: '.$made;

        $this->summary($w, $d, $sub);
        $this->exams($w, $d, $sub);
        $this->careers($w, $d, $sub);
        if ($d['business']) {
            $this->orders($w, $d, $sub);
        }

        $path = tempnam(sys_get_temp_dir(), 'lap');
        $w->save($path);

        return $path;
    }

    private function num(int|float $n): string
    {
        return number_format($n, 0, ',', '.');
    }

    private function summary(XlsxWriter $w, array $d, string $sub): void
    {
        $top = collect($d['top'])->map(fn ($k) => $k[0].': '.($k[2] === 'money' ? 'Rp '.$this->num($k[1]) : $this->num($k[1])))->implode('  |  ');
        $rows = [];
        foreach ($d['kpi'] as $i => [$label, $value, $type]) {
            $rows[] = [$label, [(int) $value, ReportXlsxStyle::td($type, $i)]];
        }

        XlsxTable::sheet($w, 'Ringkasan', ['title' => 'LAPORAN OWLSPIRE — RINGKASAN', 'sub' => $sub, 'summary' => $top],
            [['Indikator', 34, 'text'], ['Nilai', 24, 'int']], $rows);
    }

    private function exams(XlsxWriter $w, array $d, string $sub): void
    {
        $sum = ['total' => 0, 'full' => 0, 'passed' => 0, 'weighted' => 0.0];
        $rows = [];
        foreach ($d['exams'] as $r) {
            $rows[] = [$r['name'], $r['total'], $r['full'], (float) $r['avg'], $r['passed'], $r['full'] > 0 ? $r['passed'] / $r['full'] : '-'];
            $sum['total'] += $r['total'];
            $sum['full'] += $r['full'];
            $sum['passed'] += $r['passed'];
            $sum['weighted'] += $r['avg'] * $r['total'];
        }
        $summary = 'Total: '.count($rows).' ujian  |  Selesai: '.$this->num($sum['total']).'  |  Ujian penuh: '.$this->num($sum['full']).'  |  Lulus: '.$this->num($sum['passed']);
        $total = ['Total', $sum['total'], $sum['full'], $sum['total'] > 0 ? round($sum['weighted'] / $sum['total'], 1) : 0.0, $sum['passed'], $sum['full'] > 0 ? $sum['passed'] / $sum['full'] : '-'];

        XlsxTable::sheet($w, 'Ujian Seleksi', ['title' => 'LAPORAN OWLSPIRE — UJIAN SELEKSI', 'sub' => $sub, 'summary' => $summary],
            [['Ujian', 32, 'text'], ['Selesai', 14, 'int'], ['Ujian penuh', 14, 'int'], ['Rata-rata skor', 16, 'dec'], ['Lulus', 12, 'int'], ['Tingkat lulus', 16, 'pct']],
            $rows, $total);
    }

    private function careers(XlsxWriter $w, array $d, string $sub): void
    {
        $sum = ['sims' => 0, 'targets' => 0, 'jobs' => 0, 'weighted' => 0.0];
        $rows = [];
        foreach ($d['careers'] as $r) {
            $rows[] = [$r['name'], $r['sims'], (float) $r['avg'], $r['targets'], $r['jobs']];
            $sum['sims'] += $r['sims'];
            $sum['targets'] += $r['targets'];
            $sum['jobs'] += $r['jobs'];
            $sum['weighted'] += $r['avg'] * $r['sims'];
        }
        $summary = 'Total: '.count($rows).' karier  |  Simulasi selesai: '.$this->num($sum['sims']).'  |  Peminat: '.$this->num($sum['targets']).'  |  Lowongan: '.$this->num($sum['jobs']);
        $total = ['Total', $sum['sims'], $sum['sims'] > 0 ? round($sum['weighted'] / $sum['sims'], 1) : 0.0, $sum['targets'], $sum['jobs']];
        $subLine = $sub.($d['jobsWeek'] ? '     |     Lowongan: papan minggu '.$d['jobsWeek']->format('d/m/Y') : '');

        XlsxTable::sheet($w, 'Karier', ['title' => 'LAPORAN OWLSPIRE — KARIER', 'sub' => $subLine, 'summary' => $summary],
            [['Karier', 40, 'text'], ['Simulasi selesai', 16, 'int'], ['Rata-rata skor', 16, 'dec'], ['Peminat (target)', 18, 'int'], ['Lowongan', 14, 'int']],
            $rows, $total);
    }

    private function orders(XlsxWriter $w, array $d, string $sub): void
    {
        $tone = ['paid' => 'st_ok', 'pending' => 'st_wait', 'failed' => 'st_bad', 'expired' => 'st_bad'];
        $count = 0;
        $amount = 0;
        $rows = [];
        foreach ($d['orders'] as $r) {
            $rows[] = [[$r['label'], $tone[$r['status']] ?? 'st_wait'], $r['count'], $r['amount']];
            $count += $r['count'];
            $amount += $r['amount'];
        }
        $summary = 'Total: '.$this->num($count).' pesanan  |  Nilai: Rp '.$this->num($amount);

        XlsxTable::sheet($w, 'Pesanan', ['title' => 'LAPORAN OWLSPIRE — PESANAN', 'sub' => $sub, 'summary' => $summary],
            [['Status', 24, 'center'], ['Jumlah', 14, 'int'], ['Nilai', 24, 'money']],
            $rows, ['Total', $count, $amount]);
    }
}
