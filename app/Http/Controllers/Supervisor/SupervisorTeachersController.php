<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\AcademicIndividualLesson;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SupervisorTeachersController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();

        $teachers = collect();

        // Load Quran teachers
        if (!empty($quranTeacherIds)) {
            $quranTeachers = User::whereIn('id', $quranTeacherIds)
                ->with('quranTeacherProfile')
                ->get()
                ->map(function ($user) {
                    $activeCircles = QuranCircle::where('quran_teacher_id', $user->id)
                        ->where('status', true)->count();
                    $activeIndividual = QuranIndividualCircle::where('quran_teacher_id', $user->id)
                        ->where('is_active', true)->count();

                    return [
                        'user' => $user,
                        'type' => 'quran',
                        'type_label' => __('supervisor.teachers.teacher_type_quran'),
                        'code' => $user->quranTeacherProfile?->teacher_code ?? '',
                        'active_entities' => $activeCircles + $activeIndividual,
                        'entity_route' => 'manage.group-circles.index',
                        'gender' => $user->quranTeacherProfile?->gender ?? null,
                        'phone' => $user->phone ?? '',
                        'is_active' => (bool) ($user->quranTeacherProfile?->is_approved ?? true),
                    ];
                });
            $teachers = $teachers->merge($quranTeachers);
        }

        // Load Academic teachers
        if (!empty($academicTeacherIds)) {
            $academicTeachers = User::whereIn('id', $academicTeacherIds)
                ->with('academicTeacherProfile')
                ->get()
                ->map(function ($user) {
                    $profileId = $user->academicTeacherProfile?->id;
                    $activeLessons = $profileId
                        ? AcademicIndividualLesson::where('academic_teacher_id', $profileId)
                            ->where('status', 'active')->count()
                        : 0;

                    return [
                        'user' => $user,
                        'type' => 'academic',
                        'type_label' => __('supervisor.teachers.teacher_type_academic'),
                        'code' => $user->academicTeacherProfile?->teacher_code ?? '',
                        'active_entities' => $activeLessons,
                        'entity_route' => 'manage.academic-lessons.index',
                        'gender' => $user->academicTeacherProfile?->gender ?? null,
                        'phone' => $user->phone ?? '',
                        'is_active' => (bool) ($user->is_active ?? true),
                    ];
                });
            $teachers = $teachers->merge($academicTeachers);
        }

        // Stats from unfiltered set
        $totalTeachers = $teachers->count();
        $quranCount = $teachers->where('type', 'quran')->count();
        $academicCount = $teachers->where('type', 'academic')->count();

        // Apply filters
        $filtered = $teachers;

        if ($search = $request->input('search')) {
            $search = mb_strtolower($search);
            $filtered = $filtered->filter(function ($t) use ($search) {
                return str_contains(mb_strtolower($t['user']->name), $search)
                    || str_contains(mb_strtolower($t['user']->email), $search)
                    || str_contains(mb_strtolower($t['code']), $search);
            });
        }

        if ($type = $request->input('type')) {
            $filtered = $filtered->where('type', $type);
        }

        if ($gender = $request->input('gender')) {
            $filtered = $filtered->filter(fn ($t) => $t['gender'] === $gender);
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $statusFilter = $request->input('status') === 'active';
            $filtered = $filtered->where('is_active', $statusFilter);
        }

        // Sort
        $sort = $request->input('sort', 'name_asc');
        $filtered = match ($sort) {
            'name_desc' => $filtered->sortByDesc(fn ($t) => $t['user']->name),
            'entities_desc' => $filtered->sortByDesc('active_entities'),
            'entities_asc' => $filtered->sortBy('active_entities'),
            default => $filtered->sortBy(fn ($t) => $t['user']->name),
        };

        // Paginate
        $perPage = 15;
        $page = $request->input('page', 1);
        $filteredValues = $filtered->values();
        $paginated = new LengthAwarePaginator(
            $filteredValues->forPage($page, $perPage)->values(),
            $filteredValues->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $isAdmin = $this->isAdminUser();

        return view('supervisor.teachers.index', [
            'teachers' => $paginated,
            'totalTeachers' => $totalTeachers,
            'quranCount' => $quranCount,
            'academicCount' => $academicCount,
            'filteredCount' => $filteredValues->count(),
            'isAdmin' => $isAdmin,
        ]);
    }

    public function toggleStatus(Request $request, $subdomain, User $teacher): RedirectResponse
    {
        if (!$this->isAdminUser()) {
            abort(403);
        }

        $this->ensureTeacherBelongsToScope($teacher);

        if ($teacher->quranTeacherProfile) {
            $profile = $teacher->quranTeacherProfile;
            $profile->is_approved = !$profile->is_approved;
            $profile->save();
        }

        // For academic teachers, toggle user-level is_active if it exists
        if ($teacher->academicTeacherProfile) {
            $teacher->is_active = !$teacher->is_active;
            $teacher->save();
        }

        return redirect()->back()->with('success', __('supervisor.teachers.status_updated'));
    }

    public function resetPassword(Request $request, $subdomain, User $teacher): RedirectResponse
    {
        if (!$this->isAdminUser()) {
            abort(403);
        }

        $this->ensureTeacherBelongsToScope($teacher);

        $newPassword = Str::random(10);
        $teacher->password = Hash::make($newPassword);
        $teacher->save();

        return redirect()->back()->with('success', __('supervisor.teachers.password_reset_success') . ': ' . $newPassword);
    }

    public function destroy(Request $request, $subdomain, User $teacher): RedirectResponse
    {
        if (!$this->isAdminUser()) {
            abort(403);
        }

        $this->ensureTeacherBelongsToScope($teacher);

        $teacher->delete();

        return redirect()->route('manage.teachers.index', ['subdomain' => $subdomain])
            ->with('success', __('supervisor.teachers.teacher_deleted'));
    }

    private function ensureTeacherBelongsToScope(User $teacher): void
    {
        $allIds = $this->getAllAssignedTeacherIds();
        if (!in_array($teacher->id, $allIds)) {
            abort(403);
        }
    }
}
