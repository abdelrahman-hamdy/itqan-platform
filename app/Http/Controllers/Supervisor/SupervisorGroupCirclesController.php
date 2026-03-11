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

        $baseQuery = QuranCircle::whereIn('quran_teacher_id', $quranTeacherIds);

        if ($request->teacher_id) {
            $baseQuery->where('quran_teacher_id', $request->teacher_id);
        }

        // Stats from DB (before search/status/date filters)
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'full' => (clone $baseQuery)->where('status', 'full')->count(),
            'totalStudents' => (int) (clone $baseQuery)->sum('enrolled_students'),
        ];

        // Apply filters
        $query = clone $baseQuery;
        $query->with(['quranTeacher', 'schedule']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $circles = $query->latest()->paginate(15)->withQueryString();

        $teachers = $this->getTeachersForFilter('quran');

        return view('supervisor.group-circles.index', compact('circles', 'teachers', 'stats'));
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
