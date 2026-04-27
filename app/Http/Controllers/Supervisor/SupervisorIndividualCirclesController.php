<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\DifficultyLevel;
use App\Models\QuranIndividualCircle;
use App\Models\User;
use App\Services\CircleTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            'active' => (clone $baseQuery)->effectivelyActive()->count(),
            'paused' => (clone $baseQuery)->effectivelyPaused()->count(),
            'completed' => (clone $baseQuery)->whereNotNull('completed_at')->count(),
        ];

        // Apply filters
        $query = clone $baseQuery;
        $query->with(['quranTeacher', 'student', 'subscription.package', 'linkedSubscriptions'])->withCount('sessions');

        if ($request->status) {
            match ($request->status) {
                'active' => $query->effectivelyActive(),
                'paused' => $query->effectivelyPaused(),
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
            ->with(['quranTeacher', 'student', 'sessions' => fn ($q) => $q->orderBy('scheduled_at', 'desc'), 'subscription.package', 'subscription.certificate', 'linkedSubscriptions'])
            ->findOrFail($circleId);

        $teacher = User::find($circle->quran_teacher_id);
        $isAdmin = $this->isAdminUser();

        // Always fetch teachers for the edit form (supervisors can now edit too)
        $quranTeachers = User::whereIn('id', $quranTeacherIds)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        return view('supervisor.individual-circles.show', compact('circle', 'teacher', 'isAdmin', 'quranTeachers'));
    }

    public function update(Request $request, $subdomain, $circleId): RedirectResponse
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $circle = QuranIndividualCircle::whereIn('quran_teacher_id', $quranTeacherIds)->findOrFail($circleId);
        $isAdmin = $this->isAdminUser();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'specialization' => ['required', Rule::in(array_keys(QuranIndividualCircle::SPECIALIZATIONS))],
            'memorization_level' => ['required', Rule::in(DifficultyLevel::values())],
            'description' => 'nullable|string|max:500',
            'recording_enabled' => 'nullable|in:0,1',
            'supervisor_notes' => 'nullable|string|max:2000',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        foreach (['recording_enabled'] as $boolField) {
            if (isset($validated[$boolField])) {
                $validated[$boolField] = (bool) $validated[$boolField];
            }
        }

        // Role-based notes: admin can't edit supervisor_notes, supervisor can't edit admin_notes
        if ($isAdmin) {
            unset($validated['supervisor_notes']);
        } else {
            unset($validated['admin_notes']);
        }

        $circle->update($validated);

        return redirect()->back()->with('success', __('supervisor.common.updated_successfully'));
    }

    public function transfer(Request $request, $subdomain, $circleId): RedirectResponse
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $circle = QuranIndividualCircle::whereIn('quran_teacher_id', $quranTeacherIds)->findOrFail($circleId);

        $validated = $request->validate([
            'new_teacher_id' => ['required', 'integer', Rule::in($quranTeacherIds)],
        ]);

        $newTeacher = User::findOrFail($validated['new_teacher_id']);

        try {
            app(CircleTransferService::class)->transfer(
                circle: $circle,
                newTeacher: $newTeacher,
                performedBy: auth()->user(),
            );

            return redirect()->back()->with('success', __('circles.transfer.success', ['teacher_name' => $newTeacher->name]));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
