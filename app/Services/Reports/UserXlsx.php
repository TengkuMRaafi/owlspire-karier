<?php

namespace App\Services\Reports;

use App\Models\Entitlement;
use App\Models\ExamAttempt;
use Illuminate\Database\Eloquent\Builder;

/** Daftar pengguna (sesuai filter di halaman) menjadi berkas Excel dengan tampilan yang sama seperti laporan. */
class UserXlsx
{
    /** @param string $filter keterangan saringan yang dipakai, mis. "paket premium"; kosong = semua pengguna */
    public function build(Builder $query, string $filter = ''): string
    {
        $tz = config('career_trend.timezone', 'Asia/Jakarta');
        $day = fn ($t) => $t?->timezone($tz)->format('d/m/Y H:i');
        $n = ['all' => 0, 'active' => 0, 'suspended' => 0, 'premium' => 0, 'verified' => 0, 'twofa' => 0];
        $rows = [];

        $query->reorder('id')->chunkById(500, function ($chunk) use (&$rows, &$n, $day, $tz) {
            $ids = $chunk->pluck('id');
            $done = ExamAttempt::whereIn('user_id', $ids)->whereNotNull('finished_at')->selectRaw('user_id, count(*) as total')->groupBy('user_id')->pluck('total', 'user_id');
            $subs = Entitlement::where('type', 'subscription')->whereIn('user_id', $ids)
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->get()->groupBy('user_id');

            foreach ($chunk as $u) {
                $sub = $subs[$u->id] ?? null;
                $suspended = $u->isSuspended();
                $verified = $u->email_verified_at !== null;
                $twoFa = $u->hasTwoFactor();

                if ($u->isInternal()) {
                    $plan = 'Tanpa paket';
                    $until = '-';
                } elseif ($sub) {
                    $plan = 'Premium';
                    $until = $sub->contains(fn ($r) => $r->expires_at === null) ? 'Tanpa batas' : $sub->max('expires_at')->format('d/m/Y');
                    $n['premium']++;
                } else {
                    $plan = 'Gratis';
                    $until = '-';
                }

                $n['all']++;
                $n[$suspended ? 'suspended' : 'active']++;
                $n['verified'] += $verified ? 1 : 0;
                $n['twofa'] += $twoFa ? 1 : 0;

                $rows[] = [
                    $u->name, $u->email, $u->roleLabel(), $plan,
                    [$suspended ? 'Ditangguhkan' : 'Aktif', $suspended ? 'st_wait' : 'st_ok'],
                    $verified ? $u->email_verified_at->timezone($tz)->format('d/m/Y') : 'Belum',
                    $twoFa ? 'Ya' : 'Tidak',
                    $day($u->created_at), $day($u->last_login_at), (int) ($done[$u->id] ?? 0), $until,
                ];
            }
        });

        $made = now()->timezone($tz)->format('d/m/Y H:i T');
        $head = [
            'title' => 'LAPORAN DATA PENGGUNA — OWLSPIRE',
            'sub' => ($filter !== '' ? 'Disaring: '.$filter : 'Semua pengguna').'     |     Tanggal Export: '.$made,
            'summary' => 'Total: '.$n['all'].' pengguna  |  Aktif: '.$n['active'].'  |  Ditangguhkan: '.$n['suspended'].'  |  Premium: '.$n['premium']
                .'  |  Email terverifikasi: '.$n['verified'].'  |  2 langkah aktif: '.$n['twofa'],
        ];
        $columns = [
            ['Nama', 28, 'text'], ['Email', 34, 'text'], ['Role', 12, 'tag'], ['Paket', 13, 'center'], ['Status', 15, 'center'],
            ['Email terverifikasi', 18, 'center'], ['2 langkah', 11, 'center'], ['Terdaftar', 18, 'center'], ['Login terakhir', 18, 'center'],
            ['Ujian selesai', 13, 'int'], ['Premium sampai', 16, 'center'],
        ];

        $w = new XlsxWriter(ReportXlsxStyle::definitions());
        XlsxTable::sheet($w, 'Data Pengguna', $head, $columns, $rows);

        $path = tempnam(sys_get_temp_dir(), 'usr');
        $w->save($path);

        return $path;
    }
}
