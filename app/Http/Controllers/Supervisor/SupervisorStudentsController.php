<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\AttendanceStatus;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSession;
use App\Models\AcademicSessionAttendance;
use App\Models\AcademicSubscription;
use App\Models\Certificate;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\QuranCircle;
use App\Models\QuranCircleEnrollment;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSessionAttendance;
use App\Models\QuranSubscription;
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

        // Stats from unfiltered set (count queries, no need to load all users)
        $totalStudents = $allStudentIds->count();
        $activeCount = $allStudentIds->isNotEmpty()
            ? User::whereIn('id', $allStudentIds)->where('active_status', true)->count()
            : 0;
        $inactiveCount = $totalStudents - $activeCount;
        $maleCount = $allStudentIds->isNotEmpty()
            ? StudentProfile::whereIn('user_id', $allStudentIds)->where('gender', 'male')->count()
            : 0;
        $femaleCount = $allStudentIds->isNotEmpty()
            ? StudentProfile::whereIn('user_id', $allStudentIds)->where('gender', 'female')->count()
            : 0;

        // Load users with profiles — apply name/email/code search at DB level
        $students = collect();
        if ($allStudentIds->isNotEmpty()) {
            $query = User::whereIn('id', $allStudentIds)
                ->with('studentProfile.gradeLevel');

            if ($search = $request->input('search')) {
                // Normalize Arabic alef variants (أ إ آ ٱ → ا) for accent-insensitive search
                $normalizedSearch = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $search);
                $likePattern = '%' . $normalizedSearch . '%';

                $query->where(function ($q) use ($search, $likePattern) {
                    $normFirst = "REPLACE(REPLACE(REPLACE(REPLACE(first_name, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ٱ', 'ا')";
                    $normLast  = "REPLACE(REPLACE(REPLACE(REPLACE(last_name, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ٱ', 'ا')";

                    $q->whereRaw("$normFirst LIKE ?", [$likePattern])
                        ->orWhereRaw("$normLast LIKE ?", [$likePattern])
                        ->orWhereRaw("CONCAT($normFirst, ' ', $normLast) LIKE ?", [$likePattern])
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhereHas('studentProfile', function ($pq) use ($search, $likePattern) {
                            $normPFirst = "REPLACE(REPLACE(REPLACE(REPLACE(first_name, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ٱ', 'ا')";
                            $normPLast  = "REPLACE(REPLACE(REPLACE(REPLACE(last_name, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ٱ', 'ا')";
                            $pq->where('student_code', 'like', '%' . $search . '%')
                                ->orWhereRaw("$normPFirst LIKE ?", [$likePattern])
                                ->orWhereRaw("$normPLast LIKE ?", [$likePattern]);
                        });
                });
            }

            $students = $query->get()
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

        // TEMP DEBUG - remove after fixing search issue
        Log::info('SEARCH_DEBUG', [
            'allStudentIds_count' => $allStudentIds->count(),
            'search' => $request->input('search'),
            'students_count' => $students->count(),
            'program' => $request->input('program'),
            'status' => $request->input('status'),
            'gender' => $request->input('gender'),
            'grade_level' => $request->input('grade_level'),
            'all_query_params' => $request->query(),
        ]);

        // Apply remaining filters (program, gender, status, grade level) at collection level
        $filtered = $students;

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
            'maleCount' => $maleCount,
            'femaleCount' => $femaleCount,
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

    public function edit($subdomain, User $student): View
    {
        if (!$this->isAdminUser()) {
            abort(403);
        }

        $this->ensureStudentBelongsToScope($student);

        $student->load('studentProfile.gradeLevel');

        $academy = AcademyContextService::getCurrentAcademy();
        $gradeLevels = AcademicGradeLevel::where('academy_id', $academy->id)
            ->where('is_active', true)->orderBy('name')->get();

        return view('supervisor.students.edit', compact('student', 'gradeLevels'));
    }

    public function update(Request $request, $subdomain, User $student): RedirectResponse
    {
        if (!$this->isAdminUser()) {
            abort(403);
        }

        $this->ensureStudentBelongsToScope($student);

        $rules = [
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$student->id,
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:male,female',
            'birth_date' => 'nullable|date|before:today',
            'nationality' => 'nullable|string|max:100',
            'grade_level_id' => 'required|exists:academic_grade_levels,id',
            'parent_phone' => 'nullable|string|max:20',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $student->first_name = $request->first_name;
        $student->last_name = $request->last_name;
        $student->email = $request->email;
        $student->phone = $request->phone;
        $student->save();

        if ($request->hasFile('avatar')) {
            if ($student->avatar) {
                Storage::disk('public')->delete($student->avatar);
            }
            $avatarPath = $request->file('avatar')->store('avatars/students', 'public');
            $student->avatar = $avatarPath;
            $student->save();
        }

        $profile = StudentProfile::where('user_id', $student->id)->first();
        $profileData = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $student->email,
            'gender' => $request->gender,
            'birth_date' => $request->birth_date,
            'nationality' => $request->nationality,
            'grade_level_id' => $request->grade_level_id,
            'parent_phone' => $request->parent_phone,
        ];

        if ($profile) {
            $profile->update($profileData);
        } else {
            StudentProfile::create(array_merge($profileData, [
                'user_id' => $student->id,
            ]));
        }

        return redirect()->route('manage.students.show', ['subdomain' => $subdomain, 'student' => $student->id])
            ->with('success', __('supervisor.students.student_updated'));
    }

    public function show($subdomain, User $student): View
    {
        $this->ensureStudentBelongsToScope($student);

        $student->load('studentProfile.gradeLevel');

        // Get quran subscriptions
        $quranSubscriptions = QuranSubscription::where('student_id', $student->id)
            ->with(['individualCircle.quranTeacher', 'package'])
            ->latest()
            ->get();

        // Get academic subscriptions
        $academicSubscriptions = AcademicSubscription::where('student_id', $student->id)
            ->with(['lesson.academicTeacher.user', 'lesson.subject'])
            ->latest()
            ->get();

        // Get certificates
        $certificates = Certificate::where('student_id', $student->id)->latest('issued_at')->get();

        // Attendance stats
        $quranAttendance = QuranSessionAttendance::where('student_id', $student->id)->get();
        $academicAttendance = AcademicSessionAttendance::where('student_id', $student->id)->get();
        $allAttendance = $quranAttendance->merge($academicAttendance);

        $totalAttendanceRecords = $allAttendance->count();
        $presentCount = $allAttendance->where('attendance_status', AttendanceStatus::ATTENDED->value)->count();
        $attendanceRate = $totalAttendanceRecords > 0 ? round(($presentCount / $totalAttendanceRecords) * 100) : 0;

        // Recent sessions (last 10)
        $recentQuranSessions = QuranSession::where('student_id', $student->id)
            ->with('quranTeacher')
            ->latest('scheduled_at')
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
                'type' => 'quran',
                'date' => $s->scheduled_at,
                'teacher_name' => $s->quranTeacher?->name ?? '',
                'status' => $s->status,
                'title' => $s->title ?? __('supervisor.students.quran_session'),
            ]);

        $recentAcademicSessions = AcademicSession::where('student_id', $student->id)
            ->with('academicTeacher.user')
            ->latest('scheduled_at')
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
                'type' => 'academic',
                'date' => $s->scheduled_at,
                'teacher_name' => $s->academicTeacher?->user?->name ?? '',
                'status' => $s->status,
                'title' => $s->title ?? __('supervisor.students.academic_session'),
            ]);

        $recentSessions = $recentQuranSessions->merge($recentAcademicSessions)
            ->sortByDesc('date')
            ->take(10)
            ->values();

        // Completed sessions count
        $completedQuran = QuranSession::where('student_id', $student->id)
            ->where('status', 'completed')->count();
        $completedAcademic = AcademicSession::where('student_id', $student->id)
            ->where('status', 'completed')->count();
        $completedSessions = $completedQuran + $completedAcademic;

        $isAdmin = $this->isAdminUser();

        return view('supervisor.students.show', [
            'student' => $student,
            'quranSubscriptions' => $quranSubscriptions,
            'academicSubscriptions' => $academicSubscriptions,
            'certificates' => $certificates,
            'attendanceRate' => $attendanceRate,
            'completedSessions' => $completedSessions,
            'recentSessions' => $recentSessions,
            'isAdmin' => $isAdmin,
        ]);
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
