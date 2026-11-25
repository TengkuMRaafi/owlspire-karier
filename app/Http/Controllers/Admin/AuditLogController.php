<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

/** Log aktivitas: siapa melakukan apa dan kapan. Hanya admin (area `audit`). */
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query()->latest('id');

        if ($q = trim((string) $request->query('cari'))) {
            $query->where(fn ($w) => $w->where('actor_name', 'like', "%{$q}%")->orWhere('subject_label', 'like', "%{$q}%")
                ->orWhere('ip', 'like', "%{$q}%")->orWhere('action', 'like', "%{$q}%"));
        }
        if (array_key_exists($group = $request->query('grup'), AuditLog::GROUPS)) {
            $query->where('action', 'like', "{$group}.%");
        }
        // Satu tanggal, dihitung menurut WIB (sama dengan jam yang tampil di tabel).
        if ($day = $request->date('tanggal', 'Y-m-d', 'Asia/Jakarta')) {
            $query->whereBetween('created_at', [
                $day->copy()->startOfDay()->setTimezone(config('app.timezone')),
                $day->copy()->endOfDay()->setTimezone(config('app.timezone')),
            ]);
        }

        return view('admin.audit.index', ['logs' => $query->paginate(10)->withQueryString(), 'groups' => AuditLog::GROUPS]);
    }
}
