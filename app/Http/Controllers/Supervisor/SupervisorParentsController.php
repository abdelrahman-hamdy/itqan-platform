<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\AcademicIndividualLesson;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\ParentProfile;
use App\Models\ParentStudentRelationship;
use App\Models\QuranCircle;
use App\Models\QuranCircleEnrollment;
use App\Models\QuranIndividualCircle;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\AcademyContextService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRules;
use Illuminate\View\View;

class SupervisorParentsController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        if (! $this->canManageParents()) {
            abort(403);
        }

        // Discover parent user IDs based on role
        if ($this->isAdminUser()) {
            $parentUserIds = User::where('user_type', 'parent')
                ->where('academy_id', $this->getAcademyId())
                ->pluck('id');
        } else {
            // Supervisor path: discover students first, then traverse to parents
            $studentUserIds = $this->discoverStudentUserIds();

            if ($studentUserIds->isEmpty()) {
                $parentUserIds = collect();
            } else {
                $studentProfileIds = StudentProfile::whereIn('user_id', $studentUserIds)->pluck('id');

                // Direct parent link
                $directParentIds = StudentProfile::whereIn('user_id', $studentUserIds)
                    ->whereNotNull('parent_id')
                    ->pluck('parent_id');

                // Pivot table link
                $pivotParentIds = collect();
                if ($studentProfileIds->isNotEmpty()) {
                    $pivotParentIds = ParentStudentRelationship::whereIn('student_id', $studentProfileIds)
                        ->pluck('parent_id');
                }

                $parentProfileIds = $directParentIds->merge($pivotParentIds)->unique();

                $parentUserIds = ParentProfile::whereIn('id', $parentProfileIds)
                    ->whereNotNull('user_id')
                    ->pluck('user_id');
            }
        }

        // Load parent users with profiles
        $parents = collect();
        $childrenCounts = collect();

        if ($parentUserIds->isNotEmpty()) {
            $parentUsers = User::whereIn('id', $parentUserIds)
                ->with('parentProfile')
                ->get();

            // Pre-compute children counts
            $profileIds = $parentUsers->pluck('parentProfile.id')->filter();
            if ($profileIds->isNotEmpty()) {
                $childrenCounts = ParentStudentRelationship::whereIn('parent_id', $profileIds)
                    ->selectRaw('parent_id, count(*) as count')
                    ->groupBy('parent_id')
                    ->pluck('count', 'parent_id');
            }

            $parents = $parentUsers->map(function ($user) use ($childrenCounts) {
                return [
                    'user' => $user,
                    'parent_code' => $user->parentProfile?->parent_code ?? '',
                    'relationship_type' => $user->parentProfile?->relationship_type?->value ?? null,
                    'phone' => $user->parentProfile?->phone ?? $user->phone ?? '',
                    'occupation' => $user->parentProfile?->occupation ?? '',
                    'is_active' => (bool) ($user->active_status ?? false),
                    'children_count' => $childrenCounts[$user->parentProfile?->id] ?? 0,
                    'created_at' => $user->created_at,
                ];
            });
        }

        // Stats from unfiltered set
        $totalParents = $parents->count();
        $activeCount = $parents->where('is_active', true)->count();
        $fatherCount = $parents->where('relationship_type', 'father')->count();
        $motherCount = $parents->where('relationship_type', 'mother')->count();

        // Apply filters
        $filtered = $parents;

        if ($search = $request->input('search')) {
            $search = mb_strtolower($search);
            $filtered = $filtered->filter(function ($p) use ($search) {
                return str_contains(mb_strtolower($p['user']->name), $search)
                    || str_contains(mb_strtolower($p['user']->email), $search)
                    || str_contains(mb_strtolower($p['parent_code']), $search);
            });
        }

        if ($relationshipType = $request->input('relationship_type')) {
            $filtered = $filtered->filter(fn ($p) => $p['relationship_type'] === $relationshipType);
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $statusFilter = $request->input('status') === 'active';
            $filtered = $filtered->where('is_active', $statusFilter);
        }

        // Sort
        $sort = $request->input('sort', 'name_asc');
        $filtered = match ($sort) {
            'name_desc' => $filtered->sortByDesc(fn ($p) => $p['user']->name),
            'newest' => $filtered->sortByDesc('created_at'),
            'oldest' => $filtered->sortBy('created_at'),
            'children_count' => $filtered->sortByDesc('children_count'),
            default => $filtered->sortBy(fn ($p) => $p['user']->name),
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

        return view('supervisor.parents.index', [
            'parents' => $paginated,
            'totalParents' => $totalParents,
            'activeCount' => $activeCount,
            'fatherCount' => $fatherCount,
            'motherCount' => $motherCount,
            'filteredCount' => $filteredValues->count(),
            'isAdmin' => $this->isAdminUser(),
            'canManageParents' => $this->canManageParents(),
            'canResetPasswords' => $this->canResetPasswords(),
        ]);
    }

    public function toggleStatus(Request $request, $subdomain, User $parent): RedirectResponse
    {
        if (! $this->canManageParents()) {
            abort(403);
        }

        $this->ensureParentBelongsToScope($parent);

        $parent->active_status = ! $parent->active_status;
        $parent->save();

        return redirect()->back()->with('success', __('supervisor.parents.status_updated'));
    }

    public function resetPassword(Request $request, $subdomain, User $parent): RedirectResponse
    {
        if (! $this->canResetPasswords()) {
            abort(403);
        }

        $this->ensureParentBelongsToScope($parent);

        $newPassword = $request->input('new_password');
        $confirmation = $request->input('new_password_confirmation');

        if (! $newPassword || mb_strlen($newPassword) < 6) {
            return redirect()->back()->with('error', __('supervisor.parents.password_too_short'));
        }

        if ($newPassword !== $confirmation) {
            return redirect()->back()->with('error', __('supervisor.parents.passwords_dont_match'));
        }

        $parent->password = Hash::make($newPassword);
        $parent->plain_password = $newPassword;
        $parent->save();

        return redirect()->back()->with('success', __('supervisor.parents.password_reset_success'));
    }

    public function destroy(Request $request, $subdomain, User $parent): RedirectResponse
    {
        if (! $this->canManageParents()) {
            abort(403);
        }

        $this->ensureParentBelongsToScope($parent);

        $parent->delete();

        return redirect()->route('manage.parents.index', ['subdomain' => $subdomain])
            ->with('success', __('supervisor.parents.parent_deleted'));
    }

    public function create(Request $request, $subdomain = null): View
    {
        if (! $this->canManageParents()) {
            abort(403);
        }

        // Get students in supervisor's scope for child linking
        $studentUserIds = $this->discoverStudentUserIds();
        $students = User::whereIn('id', $studentUserIds)
            ->where('active_status', true)
            ->orderBy('first_name')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'email' => $s->email,
                'avatar' => $s->avatar ? asset('storage/'.$s->avatar) : null,
            ]);

        return view('supervisor.parents.create', compact('students'));
    }

    public function store(Request $request, $subdomain = null): RedirectResponse
    {
        if (! $this->canManageParents()) {
            abort(403);
        }

        $rules = [
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'phone' => 'nullable|string|max:20',
            'relationship_type' => 'required|in:father,mother,other',
            'occupation' => 'nullable|string|max:255',
            'password' => ['required', PasswordRules::min(6)->letters()->numbers()],
            'password_confirmation' => 'required|same:password',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:users,id',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'first_name.required' => __('auth.register.student.first_name_required', [], 'ar'),
            'last_name.required' => __('auth.register.student.last_name_required', [], 'ar'),
            'email.required' => __('auth.register.student.email_required', [], 'ar'),
            'email.unique' => __('auth.register.student.email_unique', [], 'ar'),
            'relationship_type.required' => __('supervisor.parents.relationship_placeholder'),
            'password.min' => __('supervisor.parents.password_too_short'),
            'password_confirmation.same' => __('supervisor.parents.passwords_dont_match'),
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $academy = AcademyContextService::getCurrentAcademy();

        // Remove any soft-deleted user with the same email to avoid DB unique constraint violation
        User::onlyTrashed()->where('email', $request->email)->where('academy_id', $academy->id)->forceDelete();

        $user = new User([
            'academy_id' => $academy->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'plain_password' => $request->password,
        ]);
        $user->user_type = 'parent';
        $user->active_status = true;
        $user->save();

        // Handle avatar upload
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars/parents', 'public');
            $user->avatar = $avatarPath;
            $user->save();
        }

        try {
            // The User observer may auto-create a ParentProfile; update it with our fields
            $profile = ParentProfile::where('user_id', $user->id)->first();
            if ($profile) {
                $profile->update([
                    'relationship_type' => $request->relationship_type,
                    'occupation' => $request->occupation,
                    'avatar' => $avatarPath,
                ]);
            } else {
                ParentProfile::create([
                    'academy_id' => $academy->id,
                    'user_id' => $user->id,
                    'relationship_type' => $request->relationship_type,
                    'occupation' => $request->occupation,
                    'avatar' => $avatarPath,
                ]);
            }

            // Link selected students to the parent
            if ($request->filled('student_ids') && $profile) {
                $studentProfiles = \App\Models\StudentProfile::whereIn('user_id', $request->student_ids)->pluck('id');
                foreach ($studentProfiles as $studentProfileId) {
                    \App\Models\ParentStudentRelationship::firstOrCreate([
                        'parent_id' => $profile->id,
                        'student_id' => $studentProfileId,
                    ], [
                        'relationship_type' => $request->relationship_type,
                    ]);
                }
            }
        } catch (QueryException $e) {
            Log::error('Parent creation failed: '.$e->getMessage(), [
                'user_id' => $user->id,
                'academy_id' => $academy->id,
            ]);

            if ($avatarPath) {
                Storage::disk('public')->delete($avatarPath);
            }
            $user->delete();

            return back()->withErrors(['error' => __('supervisor.parents.create_error')])->withInput();
        }

        return redirect()->route('manage.parents.index', ['subdomain' => $subdomain])
            ->with('success', __('supervisor.parents.parent_created'));
    }

    /**
     * Discover student user IDs using the same 4-source logic from SupervisorStudentsController.
     */
    private function discoverStudentUserIds(): \Illuminate\Support\Collection
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicProfileIds = $this->getAssignedAcademicTeacherProfileIds();

        $studentIds = collect();

        // 1. Quran Individual
        if (! empty($quranTeacherIds)) {
            $fromIndividual = QuranIndividualCircle::whereIn('quran_teacher_id', $quranTeacherIds)
                ->where('is_active', true)->pluck('student_id');
            $studentIds = $studentIds->merge($fromIndividual);
        }

        // 2. Quran Group
        if (! empty($quranTeacherIds)) {
            $activeCircleIds = QuranCircle::whereIn('quran_teacher_id', $quranTeacherIds)
                ->where('status', true)->pluck('id');
            $fromCircles = QuranCircleEnrollment::whereIn('circle_id', $activeCircleIds)
                ->where('status', QuranCircleEnrollment::STATUS_ENROLLED)->pluck('student_id');
            $studentIds = $studentIds->merge($fromCircles);
        }

        // 3. Academic Lessons
        if (! empty($academicProfileIds)) {
            $fromAcademic = AcademicIndividualLesson::whereIn('academic_teacher_id', $academicProfileIds)
                ->active()->pluck('student_id');
            $studentIds = $studentIds->merge($fromAcademic);
        }

        // 4. Interactive Courses
        if (! empty($academicProfileIds)) {
            $courseIds = InteractiveCourse::whereIn('assigned_teacher_id', $academicProfileIds)->pluck('id');
            if ($courseIds->isNotEmpty()) {
                $enrolledProfileIds = InteractiveCourseEnrollment::whereIn('course_id', $courseIds)
                    ->active()->pluck('student_id');
                $fromCourses = StudentProfile::whereIn('id', $enrolledProfileIds)
                    ->whereNotNull('user_id')->pluck('user_id');
                $studentIds = $studentIds->merge($fromCourses);
            }
        }

        return $studentIds->unique()->values();
    }

    private function ensureParentBelongsToScope(User $parent): void
    {
        if ($parent->user_type !== 'parent') {
            abort(403);
        }

        if ($this->isAdminUser()) {
            if ($parent->academy_id !== $this->getAcademyId()) {
                abort(403);
            }

            return;
        }

        // Supervisor: check parent is linked to a student in the discovered set
        $studentUserIds = $this->discoverStudentUserIds();
        $studentProfileIds = StudentProfile::whereIn('user_id', $studentUserIds)->pluck('id');

        $parentProfile = $parent->parentProfile;
        if (! $parentProfile) {
            abort(403);
        }

        // Check direct link
        $directlyLinked = StudentProfile::whereIn('user_id', $studentUserIds)
            ->where('parent_id', $parentProfile->id)
            ->exists();

        if ($directlyLinked) {
            return;
        }

        // Check pivot link
        $pivotLinked = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->whereIn('student_id', $studentProfileIds)
            ->exists();

        if (! $pivotLinked) {
            abort(403);
        }
    }
}
