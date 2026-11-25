<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entitlement;
use App\Models\User;
use App\Models\Order;
use App\Models\ExamAttempt;
use App\Models\ExamType;
use App\Models\Question;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Uang dan paket hanya untuk admin; staf melihat ringkasan konten dan ujian.
        $business = $request->user()->canAccess('orders');
        $days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->startOfDay());

        $attemptsByDay = ExamAttempt::where('started_at', '>=', $days->first())->get(['started_at'])
            ->groupBy(fn ($a) => $a->started_at->format('Y-m-d'));
        $usersByDay = User::where('created_at', '>=', $days->first())->get(['created_at'])
            ->groupBy(fn ($u) => $u->created_at->format('Y-m-d'));

        $series = $days->map(fn ($d) => [
            'label' => $d->format('d/m'),
            'dow' => $d->copy()->locale('id')->isoFormat('ddd'),
            'day' => $d->format('j'),
            'attempts' => $attemptsByDay->get($d->format('Y-m-d'), collect())->count(),
            'users' => $usersByDay->get($d->format('Y-m-d'), collect())->count(),
        ]);

        // Hanya pengguna biasa yang punya paket; admin dan staf tidak dihitung Gratis.
        $users = User::where('role', 'member')->count();
        $premiumUsers = Entitlement::where('type', 'subscription')
            ->whereIn('user_id', User::where('role', 'member')->select('id'))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->distinct()->count('user_id');

        return view('admin.dashboard', [
            'kpi' => [
                'users' => $users,
                'premium_users' => $premiumUsers,
                'free_users' => max(0, $users - $premiumUsers),
                'users_week' => User::where('role', 'member')->where('created_at', '>=', now()->subDays(7))->count(),
                'revenue' => $business ? (int) Order::where('status', 'paid')->sum('amount') : 0,
                'revenue_month' => $business ? (int) Order::where('status', 'paid')->where('paid_at', '>=', now()->startOfMonth())->sum('amount') : 0,
                'attempts' => ExamAttempt::whereNotNull('finished_at')->count(),
                'full_attempts' => ExamAttempt::whereNotNull('finished_at')->where('mode', 'full')->count(),
                'questions' => Question::count(),
            ],
            'business' => $business,
            'waiting' => ! $business ? 0 : Order::where('status', 'pending')->whereNotNull('meta->claimed_at')->count(),
            'series' => $series,
            'seriesMax' => max(1, $series->max('attempts'), $series->max('users')),
            'types' => ExamType::withCount('attempts')->orderBy('position')->get(),
            'recentOrders' => $business ? Order::with('user')->latest('id')->limit(3)->get() : collect(),
            'recentAttempts' => ExamAttempt::with(['user', 'type'])->whereNotNull('finished_at')->latest('finished_at')->limit(3)->get(),
        ]);
    }
}
