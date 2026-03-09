<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\QuranIndividualCircle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorIndividualCirclesController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();

        $query = QuranIndividualCircle::whereIn('quran_teacher_id', $quranTeacherIds)
            ->with(['quranTeacher', 'student', 'subscription.package'])
            ->withCount('sessions');

        if ($request->teacher_id) {
            $query->where('quran_teacher_id', $request->teacher_id);
        }

        if ($request->status) {
            match ($request->status) {
                'active' => $query->where('is_active', true),
                'paused' => $query->where('is_active', false)->whereNull('completed_at'),
                'completed' => $query->whereNotNull('completed_at'),
                default => null,
            };
        }

        $circles = $query->latest()->paginate(15)->withQueryString();

        $teachers = User::whereIn('id', $quranTeacherIds)->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'type_label' => __('supervisor.teachers.teacher_type_quran'),
        ])->toArray();

        return view('supervisor.individual-circles.index', compact('circles', 'teachers'));
    }

    public function show($subdomain, $circleId): View
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();

        $circle = QuranIndividualCircle::whereIn('quran_teacher_id', $quranTeacherIds)
            ->with(['quranTeacher', 'student', 'sessions' => fn ($q) => $q->orderBy('scheduled_at', 'desc'), 'subscription.package', 'subscription.certificate'])
            ->findOrFail($circleId);

        $teacher = User::find($circle->quran_teacher_id);

        return view('supervisor.individual-circles.show', compact('circle', 'teacher'));
    }
}
