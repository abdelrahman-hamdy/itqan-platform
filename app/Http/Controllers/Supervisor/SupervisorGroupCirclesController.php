<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\QuranCircle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorGroupCirclesController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();

        $query = QuranCircle::whereIn('quran_teacher_id', $quranTeacherIds)
            ->with(['quranTeacher', 'schedule']);

        // Teacher filter
        if ($request->teacher_id) {
            $query->where('quran_teacher_id', $request->teacher_id);
        }

        // Status filter
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $circles = $query->latest()->paginate(15)->withQueryString();

        $teachers = $this->getTeachersForFilter('quran');

        return view('supervisor.group-circles.index', compact('circles', 'teachers'));
    }

    public function show($subdomain, $circleId): View
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();

        $circle = QuranCircle::whereIn('quran_teacher_id', $quranTeacherIds)
            ->with(['quranTeacher', 'students', 'sessions' => fn ($q) => $q->orderBy('scheduled_at', 'desc'), 'schedule'])
            ->findOrFail($circleId);

        $teacher = User::find($circle->quran_teacher_id);

        return view('supervisor.group-circles.show', compact('circle', 'teacher'));
    }

    private function getTeachersForFilter(string $type): array
    {
        $ids = $type === 'quran' ? $this->getAssignedQuranTeacherIds() : $this->getAssignedAcademicTeacherIds();

        return User::whereIn('id', $ids)->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'type_label' => $type === 'quran' ? __('supervisor.teachers.teacher_type_quran') : __('supervisor.teachers.teacher_type_academic'),
        ])->toArray();
    }
}
