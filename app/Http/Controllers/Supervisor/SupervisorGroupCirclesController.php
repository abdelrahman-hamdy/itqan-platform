<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\DifficultyLevel;
use App\Enums\WeekDays;
use App\Models\QuranCircle;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        $isAdmin = $this->isAdminUser();

        $quranTeachers = User::whereIn('id', $quranTeacherIds)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        return view('supervisor.group-circles.show', compact('circle', 'teacher', 'isAdmin', 'quranTeachers'));
    }

    public function update(Request $request, $subdomain, $circleId): RedirectResponse
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $circle = QuranCircle::whereIn('quran_teacher_id', $quranTeacherIds)->findOrFail($circleId);

        $weekDayValues = WeekDays::values();
        $difficultyValues = DifficultyLevel::values();

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'age_group' => 'required|in:children,youth,adults,all_ages',
            'gender_type' => 'required|in:male,female,mixed',
            'specialization' => 'required|in:memorization,recitation,interpretation,arabic_language,complete',
            'memorization_level' => ['required', Rule::in($difficultyValues)],
            'description' => 'nullable|string|max:500',
            'quran_teacher_id' => ['required', Rule::in($quranTeacherIds)],
            'max_students' => 'required|integer|min:1|max:20',
            'monthly_fee' => 'required|numeric|min:0',
            'monthly_sessions_count' => 'required|in:4,8,12,16,20',
            'schedule_days' => 'nullable|array',
            'schedule_days.*' => Rule::in($weekDayValues),
            'schedule_time' => 'nullable|string',
            'status' => 'required|in:0,1',
            'supervisor_notes' => 'nullable|string|max:2000',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $validated['status'] = (bool) $validated['status'];

        if (! $this->isAdminUser()) {
            unset($validated['admin_notes']);
        }

        $circle->update($validated);

        return redirect()->back()->with('success', __('supervisor.common.updated_successfully'));
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
