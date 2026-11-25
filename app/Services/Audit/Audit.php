<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Satu pintu untuk mencatat jejak aktivitas penting. Kegagalan mencatat tidak boleh menggagalkan aksi aslinya. */
class Audit
{
    public static function log(string $action, ?Model $subject = null, array $meta = [], ?User $actor = null): void
    {
        try {
            $actor ??= auth()->user();
            $request = request();

            // Selama admin masuk sebagai pengguna, catat juga siapa yang sebenarnya bertindak.
            if ($request->hasSession() && $request->session()->has('impersonator_id')) {
                $meta['impersonator_id'] = (int) $request->session()->get('impersonator_id');
            }

            AuditLog::create([
                'actor_id' => $actor?->id,
                'actor_name' => $actor?->name,
                'action' => $action,
                'subject_type' => $subject ? Str::snake(class_basename($subject)) : null,
                'subject_id' => $subject?->getKey(),
                'subject_label' => $subject instanceof User ? $subject->email : ($subject->name ?? $subject->invoice_no ?? null),
                'meta' => $meta ?: null,
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
