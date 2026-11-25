<?php

namespace App\Services\Account;

use App\Models\Career;
use App\Models\ChatMessage;
use App\Models\CvProfile;
use App\Models\ExamAttempt;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\User;

/** Mengumpulkan data milik satu pengguna untuk diunduh sebagai JSON (hak akses data pribadi). */
class DataExport
{
    public function __construct(private Preferences $prefs)
    {
    }

    public function build(User $user): array
    {
        $cv = CvProfile::where('user_id', $user->id)->first();

        return [
            'diekspor_pada' => now()->toIso8601String(),
            'profil' => [
                'nama' => $user->name,
                'email' => $user->email,
                'keterangan' => $user->headline,
                'karier_target' => $user->target_career_id ? Career::find($user->target_career_id)?->name : null,
                'bergabung' => $user->created_at?->toIso8601String(),
            ],
            'preferensi' => collect(array_keys($this->prefs->schema()))
                ->mapWithKeys(fn ($g) => [$g => $this->prefs->group($user, $g)])->all(),
            'riwayat_ujian' => ExamAttempt::with('type:id,name')->where('user_id', $user->id)->whereNotNull('finished_at')
                ->orderBy('finished_at')->get()
                ->map(fn ($a) => [
                    'ujian' => $a->type?->name,
                    'mode' => $a->mode,
                    'skor' => $a->total_score,
                    'lulus' => $a->passed,
                    'selesai' => $a->finished_at?->toIso8601String(),
                ])->all(),
            'topik_selesai' => LessonProgress::with('lesson:id,title')->where('user_id', $user->id)->whereNotNull('completed_at')
                ->orderBy('completed_at')->get()
                ->map(fn ($p) => ['topik' => $p->lesson?->title, 'selesai' => $p->completed_at?->toIso8601String()])->all(),
            'cv' => $cv ? ['skill' => $cv->parsed['skills'] ?? [], 'teks' => $cv->raw_text] : null,
            'chat_asisten' => ChatMessage::where('user_id', $user->id)->orderBy('id')->get()
                ->map(fn ($m) => ['peran' => $m->role, 'isi' => $m->content, 'waktu' => $m->created_at?->toIso8601String()])->all(),
            'pesanan' => Order::where('user_id', $user->id)->orderBy('id')->get()
                ->map(fn ($o) => ['no_tagihan' => $o->invoice_no, 'jenis' => $o->item_type, 'jumlah' => $o->amount, 'status' => $o->status, 'dibuat' => $o->created_at?->toIso8601String()])->all(),
        ];
    }
}
