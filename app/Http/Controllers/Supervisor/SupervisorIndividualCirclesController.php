<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\QuranIndividualCircle;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorIndividualCirclesController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();

        $baseQuery = QuranIndividualCircle::whereIn('quran_teacher_id', $quranTeacherIds);

        if ($request->teacher_id) {
            $baseQuery->where('quran_teacher_id', $request->teacher_id);
        }

        // Stats from DB (before search/status/date filters)
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->whereNull('completed_at')->count(),
            'paused' => (clone $baseQuery)->where('is_active', false)->whereNull('completed_at')->count(),
            'completed' => (clone $baseQuery)->whereNotNull('completed_at')->count(),
        ];

        // Apply filters
        $query = clone $baseQuery;
        $query->with(['quranTeacher', 'student', 'subscription.package'])->withCount('sessions');

        if ($request->status) {
            match ($request->status) {
                'active' => $query->where('is_active', true)->whereNull('completed_at'),
                'paused' => $query->where('is_active', false)->whereNull('completed_at'),
                'completed' => $query->whereNotNull('completed_at'),
                default => null,
            };
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $circles = $query->latest()->paginate(15)->withQueryString();

        $teachers = User::whereIn('id', $quranTeacherIds)->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'type_label' => __('supervisor.teachers.teacher_type_quran'),
        ])->toArray();

        return view('supervisor.individual-circles.index', compact('circles', 'teachers', 'stats'));
    }

    public function show($subdomain, $circleId): View
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();

        $circle = QuranIndividualCircle::whereIn('quran_teacher_id', $quranTeacherIds)
            ->with(['quranTeacher', 'student', 'sessions' => fn ($q) => $q->orderBy('scheduled_at', 'desc'), 'subscription.package', 'subscription.certificate'])
            ->findOrFail($circleId);

        $teacher = User::find($circle->quran_teacher_id);
        $isAdmin = $this->isAdminUser();

        // Get available quran teachers for reassignment
        $availableTeachers = $isAdmin
            ? User::whereIn('id', $quranTeacherIds)->get()
            : collect();

        return view('supervisor.individual-circles.show', compact('circle', 'teacher', 'isAdmin', 'availableTeachers'));
    }

    public function update(Request $request, $subdomain, $circleId): RedirectResponse
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $circle = QuranIndividualCircle::whereIn('quran_teacher_id', $quranTeacherIds)->findOrFail($circleId);

        $validated = $request->validate([
            'is_active' => 'required|boolean',
            'quran_teacher_id' => 'nullable|exists:users,id',
        ]);

        $updateData = ['is_active' => $validated['is_active']];
        if (! empty($validated['quran_teacher_id'])) {
            // Verify teacher is in scope
            if (in_array((int) $validated['quran_teacher_id'], $quranTeacherIds)) {
                $updateData['quran_teacher_id'] = $validated['quran_teacher_id'];
            }
        }

        $circle->update($updateData);

        return redirect()->back()->with('success', __('supervisor.common.updated_successfully'));
    }
}
