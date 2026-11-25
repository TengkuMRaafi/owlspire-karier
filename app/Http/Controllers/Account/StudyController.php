<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\ExamType;
use App\Models\StudyProfile;
use App\Services\Study\StudyActivity;
use App\Services\Study\StudyPlanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Rencana belajar otomatis, target harian, streak, dan pengingat (email). */
class StudyController extends Controller
{
    public function index(Request $request, StudyPlanner $planner, StudyActivity $activity)
    {
        $user = $request->user();
        $profile = StudyProfile::firstOrCreate(['user_id' => $user->id]);
        $profile->load('examType');
        return view('account.plan', [
            'profile' => $profile,
            'plan' => $planner->build($profile),
            'streak' => $activity->streak($user->id),
            'counts' => $activity->todayCounts($user->id),
            'types' => ExamType::where('is_active', true)->orderBy('kind')->orderBy('position')->get(['id', 'name', 'kind']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'daily_goal' => ['sometimes', 'integer', 'in:'.implode(',', (array) config('platform.study.goal_options', [3, 5, 10, 15, 20]))],
            'exam_type_id' => ['nullable', 'integer', 'exists:exam_types,id'],
            'exam_date' => ['nullable', 'date', 'after_or_equal:today'],
        ], ['exam_date.after_or_equal' => 'Tanggal ujian tidak boleh sudah lewat.']);

        StudyProfile::updateOrCreate(['user_id' => $request->user()->id], [
            'daily_goal' => $data['daily_goal'] ?? StudyProfile::where('user_id', $request->user()->id)->value('daily_goal') ?? 5,
            'exam_type_id' => $data['exam_type_id'] ?? null,
            'exam_date' => $data['exam_date'] ?? null,
        ]);

        return redirect()->route('account.plan')->with('status', 'Rencana belajar diperbarui.');
    }

    public function reminder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'remind_channel' => ['required', 'in:none,email'],
            'remind_time' => ['required', 'date_format:H:i'],
        ]);

        StudyProfile::updateOrCreate(['user_id' => $request->user()->id], $data);

        return redirect()->route('account.plan')->with('status', 'Pengingat disimpan.');
    }
}
