<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\AcademicGradeLevel;
use App\Models\AcademicIndividualLesson;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
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
use Illuminate\Validation\Rules\Password as PasswordRules;
use Illuminate\View\View;

class SupervisorStudentsController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicProfileIds = $this->getAssignedAcademicTeacherProfileIds();

        // Discover students from each program separately for badge computation
        $quranStudentIds = collect();
        $academicStudentIds = collect();
        $interactiveStudentUserIds = collect();

        if ($this->isAdminUser()) {
            // Admin sees all students in academy
            $academyId = $this->getAcademyId();
            $allStudentIds = User::where('user_type', 'student')
                ->where('academy_id', $academyId)
                ->pluck('id');

            // For program badges, still compute per-program membership
            if (!empty($quranTeacherIds)) {
                $fromIndividual = QuranIndividualCircle::whereIn('quran_teacher_id', $quranTeacherIds)
                    ->where('is_active', true)->pluck('student_id');
                $activeCircleIds = QuranCircle::whereIn('quran_teacher_id', $quranTeacherIds)
                    ->where('status', true)->pluck('id');
                $fromCircles = QuranCircleEnrollment::whereIn('circle_id', $activeCircleIds)
                    ->where('status', QuranCircleEnrollment::STATUS_ENROLLED)->pluck('student_id');
                $quranStudentIds = $fromIndividual->merge($fromCircles)->unique();
            }

            if (!empty($academicProfileIds)) {
                $academicStudentIds = AcademicIndividualLesson::whereIn('academic_teacher_id', $academicProfileIds)
                    ->active()->pluck('student_id')->unique();

                $courseIds = InteractiveCourse::whereIn('assigned_teacher_id', $academicProfileIds)->pluck('id');
                if ($courseIds->isNotEmpty()) {
                    $enrolledProfileIds = InteractiveCourseEnrollment::whereIn('course_id', $courseIds)
                        ->active()->pluck('student_id');
                    $interactiveStudentUserIds = StudentProfile::whereIn('id', $enrolledProfileIds)
                        ->whereNotNull('user_id')->pluck('user_id')->unique();
                }
            }
        } else {
            // Supervisor path: discover students through teacher chain
            // 1. Quran Individual
            $fromIndividual = collect();
            if (!empty($quranTeacherIds)) {
                $fromIndividual = QuranIndividualCircle::whereIn('quran_teacher_id', $quranTeacherIds)
                    ->where('is_active', true)->pluck('student_id');
            }

            // 2. Quran Group
            $fromCircles = collect();
            if (!empty($quranTeacherIds)) {
                $activeCircleIds = QuranCircle::whereIn('quran_teacher_id', $quranTeacherIds)
                    ->where('status', true)->pluck('id');
                $fromCircles = QuranCircleEnrollment::whereIn('circle_id', $activeCircleIds)
                    ->where('status', QuranCircleEnrollment::STATUS_ENROLLED)->pluck('student_id');
            }

            $quranStudentIds = $fromIndividual->merge($fromCircles)->unique();

            // 3. Academic Lessons
            if (!empty($academicProfileIds)) {
                $academicStudentIds = AcademicIndividualLesson::whereIn('academic_teacher_id', $academicProfileIds)
                    ->active()->pluck('student_id')->unique();
            }

            // 4. Interactive Courses
            if (!empty($academicProfileIds)) {
                $courseIds = InteractiveCourse::whereIn('assigned_teacher_id', $academicProfileIds)->pluck('id');
                if ($courseIds->isNotEmpty()) {
                    $enrolledProfileIds = InteractiveCourseEnrollment::whereIn('course_id', $courseIds)
                        ->active()->pluck('student_id');
                    $interactiveStudentUserIds = StudentProfile::whereIn('id', $enrolledProfileIds)
                        ->whereNotNull('user_id')->pluck('user_id')->unique();
                }
            }

            $allStudentIds = $quranStudentIds
                ->merge($academicStudentIds)
                ->merge($interactiveStudentUserIds)
                ->unique()->values();
        }

        // Convert to arrays for in_array checks
        $quranStudentIdsArray = $quranStudentIds->toArray();
        $academicStudentIdsArray = $academicStudentIds->toArray();
        $interactiveStudentUserIdsArray = $interactiveStudentUserIds->toArray();

        // Load users with profiles
        $students = collect();
        if ($allStudentIds->isNotEmpty()) {
            $students = User::whereIn('id', $allStudentIds)
                ->with('studentProfile.gradeLevel')
                ->get()
                ->map(function ($user) use ($quranStudentIdsArray, $academicStudentIdsArray, $interactiveStudentUserIdsArray) {
                    return [
                        'user' => $user,
                        'student_code' => $user->studentProfile?->student_code ?? '',
                        'gender' => $user->studentProfile?->gender ?? null,
                        'phone' => $user->studentProfile?->phone ?? $user->phone ?? '',
                        'grade_level' => $user->studentProfile?->gradeLevel?->getDisplayName() ?? '',
                        'grade_level_id' => $user->studentProfile?->grade_level_id,
                        'is_active' => (bool) ($user->active_status ?? false),
                        'enrollment_date' => $user->studentProfile?->enrollment_date ?? $user->created_at,
                        'programs' => array_values(array_filter([
                            in_array($user->id, $quranStudentIdsArray) ? 'quran' : null,
                            in_array($user->id, $academicStudentIdsArray) ? 'academic' : null,
                            in_array($user->id, $interactiveStudentUserIdsArray) ? 'interactive' : null,
                        ])),
                    ];
                });
        }

        // Stats from unfiltered set
        $totalStudents = $students->count();
        $activeCount = $students->where('is_active', true)->count();
        $inactiveCount = $totalStudents - $activeCount;

        $quranStudents = $students->filter(fn ($s) => in_array('quran', $s['programs']));
        $quranCount = $quranStudents->count();
        $quranMale = $quranStudents->where('gender', 'male')->count();
        $quranFemale = $quranStudents->where('gender', 'female')->count();

        $academicStudents = $students->filter(fn ($s) => in_array('academic', $s['programs']));
        $academicCount = $academicStudents->count();
        $academicMale = $academicStudents->where('gender', 'male')->count();
        $academicFemale = $academicStudents->where('gender', 'female')->count();

        // Apply filters
        $filtered = $students;

        if ($search = $request->input('search')) {
            $search = mb_strtolower($search);
            $filtered = $filtered->filter(function ($s) use ($search) {
                return str_contains(mb_strtolower($s['user']->name), $search)
                    || str_contains(mb_strtolower($s['user']->email), $search)
                    || str_contains(mb_strtolower($s['student_code']), $search);
            });
        }

        if ($program = $request->input('program')) {
            $filtered = $filtered->filter(fn ($s) => in_array($program, $s['programs']));
        }

        if ($gender = $request->input('gender')) {
            $filtered = $filtered->filter(fn ($s) => $s['gender'] === $gender);
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $statusFilter = $request->input('status') === 'active';
            $filtered = $filtered->where('is_active', $statusFilter);
        }

        if ($gradeLevel = $request->input('grade_level')) {
            $filtered = $filtered->filter(fn ($s) => (string) $s['grade_level_id'] === (string) $gradeLevel);
        }

        // Sort
        $sort = $request->input('sort', 'name_asc');
        $filtered = match ($sort) {
            'name_desc' => $filtered->sortByDesc(fn ($s) => $s['user']->name),
            'newest' => $filtered->sortByDesc('enrollment_date'),
            'oldest' => $filtered->sortBy('enrollment_date'),
            'grade_level' => $filtered->sortBy('grade_level'),
            default => $filtered->sortBy(fn ($s) => $s['user']->name),
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
        $academy = AcademyContextService::getCurrentAcademy();
        $gradeLevels = AcademicGradeLevel::where('academy_id', $academy->id)
            ->where('is_active', true)->orderBy('name')->get();

        return view('supervisor.students.index', [
            'students' => $paginated,
            'totalStudents' => $totalStudents,
            'activeCount' => $activeCount,
            'inactiveCount' => $inactiveCount,
            'quranCount' => $quranCount,
            'quranMale' => $quranMale,
            'quranFemale' => $quranFemale,
            'academicCount' => $academicCount,
            'academicMale' => $academicMale,
            'academicFemale' => $academicFemale,
            'filteredCount' => $filteredValues->count(),
            'isAdmin' => $isAdmin,
            'gradeLevels' => $gradeLevels,
        ]);
    }

    public function toggleStatus(Request $request, $subdomain, User $student): RedirectResponse
    {
        if (!$this->isAdminUser()) {
            abort(403);
        }

        $this->ensureStudentBelongsToScope($student);

        $student->active_status = !$student->active_status;
        $student->save();

        return redirect()->back()->with('success', __('supervisor.students.status_updated'));
    }

    public function resetPassword(Request $request, $subdomain, User $student): RedirectResponse
    {
        if (!$this->isAdminUser()) {
            abort(403);
        }

        $this->ensureStudentBelongsToScope($student);

        $newPassword = $request->input('new_password');
        $confirmation = $request->input('new_password_confirmation');

        if (!$newPassword || mb_strlen($newPassword) < 6) {
            return redirect()->back()->with('error', __('supervisor.students.password_too_short'));
        }

        if ($newPassword !== $confirmation) {
            return redirect()->back()->with('error', __('supervisor.students.passwords_dont_match'));
        }

        $student->password = Hash::make($newPassword);
        $student->save();

        return redirect()->back()->with('success', __('supervisor.students.password_reset_success'));
    }

    public function destroy(Request $request, $subdomain, User $student): RedirectResponse
    {
        if (!$this->isAdminUser()) {
            abort(403);
        }

        $this->ensureStudentBelongsToScope($student);

        $student->delete();

        return redirect()->route('manage.students.index', ['subdomain' => $subdomain])
            ->with('success', __('supervisor.students.student_deleted'));
    }

    public function create(Request $request, $subdomain = null): View
    {
        if (!$this->isAdminUser()) {
            abort(403);
        }

        $academy = AcademyContextService::getCurrentAcademy();
        $gradeLevels = AcademicGradeLevel::where('academy_id', $academy->id)
            ->where('is_active', true)->orderBy('name')->get();

        return view('supervisor.students.create', compact('academy', 'gradeLevels'));
    }

    public function store(Request $request, $subdomain = null): RedirectResponse
    {
        if (!$this->isAdminUser()) {
            abort(403);
        }

        $rules = [
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:male,female',
            'birth_date' => 'nullable|date|before:today',
            'nationality' => 'nullable|string|max:100',
            'grade_level_id' => 'required|exists:academic_grade_levels,id',
            'parent_phone' => 'nullable|string|max:20',
            'password' => ['required', PasswordRules::min(6)->letters()->numbers()],
            'password_confirmation' => 'required|same:password',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'first_name.required' => __('auth.register.student.first_name_required', [], 'ar'),
            'last_name.required' => __('auth.register.student.last_name_required', [], 'ar'),
            'email.required' => __('auth.register.student.email_required', [], 'ar'),
            'email.unique' => __('auth.register.student.email_unique', [], 'ar'),
            'gender.required' => __('supervisor.students.gender_placeholder'),
            'password.min' => __('supervisor.students.password_too_short'),
            'password_confirmation.same' => __('supervisor.students.passwords_dont_match'),
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $academy = AcademyContextService::getCurrentAcademy();

        $user = new User([
            'academy_id' => $academy->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);
        $user->user_type = 'student';
        $user->active_status = true;
        $user->save();

        // Handle avatar upload
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars/students', 'public');
            $user->avatar = $avatarPath;
            $user->save();
        }

        try {
            // The User observer may auto-create a StudentProfile; update it with our fields
            $profile = StudentProfile::where('user_id', $user->id)->first();
            if ($profile) {
                $profile->update([
                    'gender' => $request->gender,
                    'birth_date' => $request->birth_date,
                    'nationality' => $request->nationality,
                    'grade_level_id' => $request->grade_level_id,
                    'parent_phone' => $request->parent_phone,
                    'enrollment_date' => now(),
                    'avatar' => $avatarPath,
                ]);
            } else {
                StudentProfile::create([
                    'user_id' => $user->id,
                    'gender' => $request->gender,
                    'birth_date' => $request->birth_date,
                    'nationality' => $request->nationality,
                    'grade_level_id' => $request->grade_level_id,
                    'parent_phone' => $request->parent_phone,
                    'enrollment_date' => now(),
                    'avatar' => $avatarPath,
                ]);
            }
        } catch (QueryException $e) {
            Log::error('Student creation failed: '.$e->getMessage(), [
                'user_id' => $user->id,
                'academy_id' => $academy->id,
            ]);

            if ($avatarPath) {
                Storage::disk('public')->delete($avatarPath);
            }
            $user->delete();

            return back()->withErrors(['error' => __('supervisor.students.create_error')])->withInput();
        }

        return redirect()->route('manage.students.index', ['subdomain' => $subdomain])
            ->with('success', __('supervisor.students.student_created'));
    }

    private function ensureStudentBelongsToScope(User $student): void
    {
        if ($this->isAdminUser()) {
            // Admin: check student belongs to academy
            if ($student->academy_id !== $this->getAcademyId()) {
                abort(403);
            }

            return;
        }

        // Supervisor: check student is in discovered set
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicProfileIds = $this->getAssignedAcademicTeacherProfileIds();

        $studentIds = collect();

        if (!empty($quranTeacherIds)) {
            $fromIndividual = QuranIndividualCircle::whereIn('quran_teacher_id', $quranTeacherIds)
                ->where('is_active', true)->pluck('student_id');
            $activeCircleIds = QuranCircle::whereIn('quran_teacher_id', $quranTeacherIds)
                ->where('status', true)->pluck('id');
            $fromCircles = QuranCircleEnrollment::whereIn('circle_id', $activeCircleIds)
                ->where('status', QuranCircleEnrollment::STATUS_ENROLLED)->pluck('student_id');
            $studentIds = $studentIds->merge($fromIndividual)->merge($fromCircles);
        }

        if (!empty($academicProfileIds)) {
            $fromAcademic = AcademicIndividualLesson::whereIn('academic_teacher_id', $academicProfileIds)
                ->active()->pluck('student_id');
            $studentIds = $studentIds->merge($fromAcademic);

            $courseIds = InteractiveCourse::whereIn('assigned_teacher_id', $academicProfileIds)->pluck('id');
            if ($courseIds->isNotEmpty()) {
                $enrolledProfileIds = InteractiveCourseEnrollment::whereIn('course_id', $courseIds)
                    ->active()->pluck('student_id');
                $fromCourses = StudentProfile::whereIn('id', $enrolledProfileIds)
                    ->whereNotNull('user_id')->pluck('user_id');
                $studentIds = $studentIds->merge($fromCourses);
            }
        }

        if (!$studentIds->unique()->contains($student->id)) {
            abort(403);
        }
    }
}
