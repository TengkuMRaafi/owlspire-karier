<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Career;
use App\Models\CareerTrendWeek;
use App\Models\Entitlement;
use App\Models\ExamAttempt;
use App\Models\ExamType;
use App\Models\Order;
use App\Models\Question;
use App\Models\User;
use App\Services\Reports\ReportXlsx;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Laporan: rekap semua data panel dalam satu halaman, bisa disaring per periode dan diunduh sebagai CSV. */
class ReportController extends Controller
{
    private const PERIODS = ['7' => '7 hari terakhir', '30' => '30 hari terakhir', '90' => '90 hari terakhir', 'all' => 'Semua waktu'];

    public function index(Request $request)
    {
        return view('admin.reports.index', $this->data($request) + ['periods' => self::PERIODS]);
    }

    public function download(Request $request)
    {
        $d = $this->data($request);
        $base = 'laporan-owlspire-'.$d['period'].'-'.now()->format('Ymd');

        // Excel berformat; bila ekstensi zip PHP belum aktif, jatuh ke CSV supaya unduhan tetap jalan.
        if (! class_exists(\ZipArchive::class)) {
            return $this->csv($d, $base.'.csv');
        }

        return response()->download(app(ReportXlsx::class)->build($d), $base.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function csv(array $d, string $name): StreamedResponse
    {
        return response()->streamDownload(function () use ($d) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel membaca UTF-8
            fputcsv($out, ['Laporan Owlspire', $d['label']]);
            fputcsv($out, []);
            foreach ($d['kpi'] as [$label, $value]) {
                fputcsv($out, [$label, $value]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Ujian', 'Selesai', 'Ujian penuh', 'Rata-rata skor', 'Lulus', 'Tingkat lulus (%)']);
            foreach ($d['exams'] as $r) {
                fputcsv($out, [$r['name'], $r['total'], $r['full'], $r['avg'], $r['passed'], $r['pass_rate']]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Karier', 'Simulasi selesai', 'Rata-rata skor', 'Peminat (target)', 'Lowongan']);
            foreach ($d['careers'] as $r) {
                fputcsv($out, [$r['name'], $r['sims'], $r['avg'], $r['targets'], $r['jobs']]);
            }
            if ($d['business']) {
                fputcsv($out, []);
                fputcsv($out, ['Pesanan', 'Jumlah', 'Nilai (Rp)']);
                foreach ($d['orders'] as $r) {
                    fputcsv($out, [$r['label'], $r['count'], $r['amount']]);
                }
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function data(Request $request): array
    {
        $period = array_key_exists((string) $request->query('periode'), self::PERIODS) ? (string) $request->query('periode') : '30';
        $from = $period === 'all' ? null : now()->subDays((int) $period)->startOfDay();
        $business = $request->user()->canAccess('orders'); // uang hanya untuk admin

        $attempts = fn () => ExamAttempt::query()->whereNotNull('finished_at')->when($from, fn ($q) => $q->where('finished_at', '>=', $from));

        $stats = $attempts()->selectRaw("exam_type_id, count(*) as total, sum(case when mode = 'full' then 1 else 0 end) as full_n, avg(total_score) as avg_score, sum(case when mode = 'full' and passed then 1 else 0 end) as passed_n")
            ->groupBy('exam_type_id')->get()->keyBy('exam_type_id');

        $row = function (ExamType $t) use ($stats) {
            $s = $stats->get($t->id);
            $full = (int) ($s->full_n ?? 0);

            return ['name' => $t->name, 'total' => (int) ($s->total ?? 0), 'full' => $full, 'avg' => $s ? round((float) $s->avg_score, 1) : 0,
                'passed' => (int) ($s->passed_n ?? 0), 'pass_rate' => $full > 0 ? round(($s->passed_n ?? 0) / $full * 100) : 0];
        };
        $exams = ExamType::where('kind', 'selection')->orderBy('position')->get()->map($row)->all();

        $targets = User::whereNotNull('target_career_id')->selectRaw('target_career_id, count(*) as c')->groupBy('target_career_id')->pluck('c', 'target_career_id');
        $week = CareerTrendWeek::query()->max('week_start');
        $jobs = $week ? CareerTrendWeek::where('week_start', $week)->pluck('jobs', 'career_id') : collect();
        $careers = Career::listed()->get()->map(function (Career $c) use ($stats, $targets, $jobs) {
            $s = $stats->get($c->exam_type_id);

            return ['name' => $c->name, 'sims' => (int) ($s->total ?? 0), 'avg' => $s ? round((float) $s->avg_score, 1) : 0,
                'targets' => (int) ($targets[$c->id] ?? 0), 'jobs' => (int) ($jobs[$c->id] ?? 0)];
        })->sortByDesc('sims')->values()->all();

        $members = User::where('role', 'member');
        $premium = Entitlement::where('type', 'subscription')->whereIn('user_id', User::where('role', 'member')->select('id'))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->distinct()->count('user_id');
        $fullDone = $attempts()->where('mode', 'full');

        $orders = [];
        $revenue = 0;
        if ($business) {
            $labels = ['paid' => 'Lunas', 'pending' => 'Menunggu', 'failed' => 'Ditolak', 'expired' => 'Kedaluwarsa'];
            $byStatus = Order::query()->when($from, fn ($q) => $q->where('created_at', '>=', $from))
                ->selectRaw('status, count(*) as c, sum(amount) as total')->groupBy('status')->get()->keyBy('status');
            foreach ($labels as $key => $label) {
                $orders[] = ['status' => $key, 'label' => $label, 'count' => (int) ($byStatus[$key]->c ?? 0), 'amount' => (int) ($byStatus[$key]->total ?? 0)];
            }
            $revenue = (int) Order::where('status', 'paid')->when($from, fn ($q) => $q->where('paid_at', '>=', $from))->sum('amount');
        }

        // [label, nilai, jenis] lengkap untuk Excel; 'top' = ringkasan penting yang tampil sebagai kartu di halaman.
        $kpi = [
            ['Pengguna baru', (clone $members)->when($from, fn ($q) => $q->where('created_at', '>=', $from))->count(), 'int'],
            ['Total pengguna', (clone $members)->count(), 'int'],
            ['Premium aktif', $premium, 'int'],
            ['Ujian selesai', $attempts()->count(), 'int'],
            ['Ujian penuh selesai', $fullDone->count(), 'int'],
            ['Bank soal', Question::count(), 'int'],
        ];
        if ($business) {
            array_splice($kpi, 3, 0, [['Pendapatan', $revenue, 'money']]);
        }
        $pick = $business ? ['Pengguna baru', 'Ujian selesai', 'Premium aktif', 'Pendapatan'] : ['Pengguna baru', 'Ujian selesai', 'Ujian penuh selesai', 'Bank soal'];
        $top = array_values(array_filter($kpi, fn ($k) => in_array($k[0], $pick, true)));

        return [
            'period' => $period, 'label' => self::PERIODS[$period], 'business' => $business,
            'kpi' => $kpi, 'top' => $top, 'exams' => $exams, 'careers' => $careers, 'orders' => $orders,
            'jobsWeek' => $week ? \Illuminate\Support\Carbon::parse($week) : null,
        ];
    }
}
