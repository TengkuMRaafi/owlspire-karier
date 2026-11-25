<?php

namespace App\Console\Commands;

use App\Models\StudyProfile;
use App\Services\Notify\Notifier;
use App\Services\Study\StudyActivity;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class StudyRemindCommand extends Command
{
    protected $signature = 'study:remind';

    protected $description = 'Kirim pengingat belajar (email) pada jam pilihan pengguna, sekali per hari';

    public function handle(Notifier $notifier, StudyActivity $activity): int
    {
        $tz = config('platform.study.timezone', 'Asia/Jakarta');
        $now = Carbon::now($tz);
        $today = $now->toDateString();
        $sent = 0;

        StudyProfile::with('user')->where('remind_channel', '!=', 'none')
            ->where(fn ($q) => $q->whereNull('last_reminded_on')->orWhere('last_reminded_on', '<', $today))
            ->chunkById(100, function ($profiles) use ($notifier, $activity, $now, $today, $tz, &$sent) {
                foreach ($profiles as $p) {
                    $at = Carbon::createFromFormat('H:i', $p->remind_time, $tz)->setDateFrom($now);
                    // Hanya dalam 3 jam setelah jam pengingat, supaya mengubah jam di malam hari tidak memicu kiriman mendadak.
                    if (! $p->user || $now->lt($at) || $now->gt($at->copy()->addHours(3))) {
                        continue;
                    }

                    // Satu percobaan per hari, apa pun hasilnya.
                    $p->update(['last_reminded_on' => $today]);

                    $counts = $activity->todayCounts($p->user_id);
                    if ($counts['reviews'] + $counts['exams'] > 0) {
                        continue; // sudah belajar hari ini
                    }

                    if ($notifier->toUser($p->user, $p->remind_channel, 'Waktunya belajar', $this->message($p, $activity))) {
                        $sent++;
                    }
                }
            });

        $this->info("Pengingat terkirim: {$sent}");

        return self::SUCCESS;
    }

    private function message(StudyProfile $p, StudyActivity $activity): string
    {
        $streak = $activity->streak($p->user_id)['current'];
        $name = explode(' ', trim($p->user->name))[0] ?: 'kamu';

        $lines = ["Hai {$name}, belum ada belajar hari ini."];
        $lines[] = $streak > 0 ? "Streak {$streak} hari akan terputus kalau hari ini terlewat." : 'Mulai streak barumu hari ini.';
        if ($p->exam_date && $p->exam_date->gte(today())) {
            $lines[] = 'Ujianmu '.($p->exam_date->isToday() ? 'hari ini' : 'tinggal '.(int) today()->diffInDays($p->exam_date).' hari lagi').'.';
        }
        $lines[] = route('account.plan');

        return implode("\n", $lines);
    }
}
