<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\AcademicGradeLevel;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSession;
use App\Models\AcademicSubject;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use App\Services\AcademyContextService;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
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

class SupervisorTeachersController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        if (! $this->canManageTeachers()) {
            abort(403);
        }

        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();

        $teachers = collect();

        // Load Quran teachers
        if (! empty($quranTeacherIds)) {
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
                        'gender' => $user->quranTeacherProfile?->gender ?? null,
                        'phone' => $user->phone ?? '',
                        'is_active' => (bool) ($user->active_status ?? false),
                        'is_fully_booked' => (bool) ($user->quranTeacherProfile?->is_fully_booked ?? false),
                        'rating' => (float) ($user->quranTeacherProfile?->rating ?? 0),
                        'created_at' => $user->created_at,
                    ];
                });
            $teachers = $teachers->merge($quranTeachers);
        }

        // Load Academic teachers
        if (! empty($academicTeacherIds)) {
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
                        'gender' => $user->academicTeacherProfile?->gender ?? null,
                        'phone' => $user->phone ?? '',
                        'is_active' => (bool) ($user->active_status ?? false),
                        'is_fully_booked' => (bool) ($user->academicTeacherProfile?->is_fully_booked ?? false),
                        'rating' => (float) ($user->academicTeacherProfile?->rating ?? 0),
                        'created_at' => $user->created_at,
                    ];
                });
            $teachers = $teachers->merge($academicTeachers);
        }

        // Stats from unfiltered set
        $totalTeachers = $teachers->count();
        $activeCount = $teachers->where('is_active', true)->count();
        $inactiveCount = $totalTeachers - $activeCount;

        $quranTeachers = $teachers->where('type', 'quran');
        $quranCount = $quranTeachers->count();
        $quranMale = $quranTeachers->where('gender', 'male')->count();
        $quranFemale = $quranTeachers->where('gender', 'female')->count();

        $academicTeachersCol = $teachers->where('type', 'academic');
        $academicCount = $academicTeachersCol->count();
        $academicMale = $academicTeachersCol->where('gender', 'male')->count();
        $academicFemale = $academicTeachersCol->where('gender', 'female')->count();

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

        if ($request->filled('status')) {
            $statusFilter = $request->input('status') === 'active';
            $filtered = $filtered->where('is_active', $statusFilter);
        }

        // Sort
        $sort = $request->input('sort', 'name_asc');
        $filtered = match ($sort) {
            'name_desc' => $filtered->sortByDesc(fn ($t) => $t['user']->name),
            'entities_desc' => $filtered->sortByDesc('active_entities'),
            'entities_asc' => $filtered->sortBy('active_entities'),
            'rating_desc' => $filtered->sortByDesc('rating'),
            'rating_asc' => $filtered->sortBy('rating'),
            'newest' => $filtered->sortByDesc('created_at'),
            'oldest' => $filtered->sortBy('created_at'),
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
            'canManageTeachers' => $this->canManageTeachers(),
            'canResetPasswords' => $this->canResetPasswords(),
            'canManageTeacherEarnings' => $this->canManageTeacherEarnings(),
        ]);
    }

    public function toggleStatus(Request $request, $subdomain, User $teacher): RedirectResponse
    {
        if (! $this->canManageTeachers()) {
            abort(403);
        }

        $this->ensureTeacherBelongsToScope($teacher);

        $teacher->active_status = ! $teacher->active_status;
        $teacher->save();

        return redirect()->back()->with('success', __('supervisor.teachers.status_updated'));
    }

    public function toggleFullyBooked(Request $request, $subdomain, User $teacher): RedirectResponse
    {
        if (! $this->canManageTeachers()) {
            abort(403);
        }

        $this->ensureTeacherBelongsToScope($teacher);

        $profile = $teacher->quranTeacherProfile ?? $teacher->academicTeacherProfile;

        if ($profile) {
            $profile->update(['is_fully_booked' => ! $profile->is_fully_booked]);
        }

        return redirect()->back()->with('success', __('supervisor.teachers.fully_booked_updated'));
    }

    public function verifyEmail(Request $request, $subdomain, User $teacher): RedirectResponse
    {
        if (! $this->canManageTeachers()) {
            abort(403);
        }

        $this->ensureTeacherBelongsToScope($teacher);

        if ($teacher->hasVerifiedEmail()) {
            return redirect()->back()->with('info', __('supervisor.teachers.email_already_verified'));
        }

        $teacher->markEmailAsVerified();

        return redirect()->back()->with('success', __('supervisor.teachers.email_verified_success'));
    }

    public function resetPassword(Request $request, $subdomain, User $teacher): RedirectResponse
    {
        if (! $this->canResetPasswords()) {
            abort(403);
        }

        $this->ensureTeacherBelongsToScope($teacher);

        $newPassword = $request->input('new_password');
        $confirmation = $request->input('new_password_confirmation');

        if (! $newPassword || mb_strlen($newPassword) < 6) {
            return redirect()->back()->with('error', __('supervisor.teachers.password_too_short'));
        }

        if ($newPassword !== $confirmation) {
            return redirect()->back()->with('error', __('supervisor.teachers.passwords_dont_match'));
        }

        $teacher->password = Hash::make($newPassword);
        $teacher->plain_password = $newPassword;
        $teacher->save();

        return redirect()->back()->with('success', __('supervisor.teachers.password_reset_success'));
    }

    public function destroy(Request $request, $subdomain, User $teacher): RedirectResponse
    {
        if (! $this->canManageTeachers()) {
            abort(403);
        }

        $this->ensureTeacherBelongsToScope($teacher);

        $teacher->delete();

        return redirect()->route('manage.teachers.index', ['subdomain' => $subdomain])
            ->with('success', __('supervisor.teachers.teacher_deleted'));
    }

    public function create(Request $request, $subdomain = null): View
    {
        if (! $this->canManageTeachers()) {
            abort(403);
        }

        $academy = AcademyContextService::getCurrentAcademy();
        $subjects = AcademicSubject::where('academy_id', $academy->id)->where('is_active', true)->orderBy('name')->get();
        $gradeLevels = AcademicGradeLevel::where('academy_id', $academy->id)->where('is_active', true)->orderBy('name')->get();

        return view('supervisor.teachers.create', compact('academy', 'subjects', 'gradeLevels'));
    }

    public function store(Request $request, $subdomain = null): RedirectResponse
    {
        if (! $this->canManageTeachers()) {
            abort(403);
        }

        $teacherType = $request->input('teacher_type', 'quran_teacher');

        $rules = [
            'teacher_type' => 'required|in:quran_teacher,academic_teacher',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->where('academy_id', AcademyContextService::getCurrentAcademyId())],
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:male,female',
            'password' => PasswordRules::min(6)->letters()->numbers(),
            'password_confirmation' => 'required|same:password',
            'education_level' => 'required|in:diploma,bachelor,master,phd,other',
            'university' => 'required|string|max:255',
            'years_experience' => 'required|integer|min:0|max:50',
        ];

        if ($teacherType === 'academic_teacher') {
            $rules['subjects'] = 'required|array|min:1';
            $rules['grade_levels'] = 'required|array|min:1';
            $rules['available_days'] = 'required|array|min:1';
        }

        if ($teacherType === 'quran_teacher') {
            $rules['certifications'] = 'nullable|array';
            $rules['certifications.*'] = 'string|max:255';
        }

        $validator = Validator::make($request->all(), $rules, [
            'first_name.required' => __('auth.register.teacher.step2.validation.first_name_required', [], 'ar'),
            'last_name.required' => __('auth.register.teacher.step2.validation.last_name_required', [], 'ar'),
            'email.required' => __('auth.register.teacher.step2.validation.email_required', [], 'ar'),
            'email.unique' => __('auth.register.teacher.step2.validation.email_unique', [], 'ar'),
            'phone.required' => __('auth.register.teacher.step2.validation.phone_required', [], 'ar'),
            'gender.required' => __('auth.register.teacher.step2.validation.gender_required', [], 'ar'),
            'password.min' => __('supervisor.teachers.password_too_short'),
            'password_confirmation.same' => __('supervisor.teachers.passwords_dont_match'),
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
            'education_level' => $request->education_level,
            'university' => $request->university,
            'years_experience' => $request->years_experience,
        ]);
        $user->user_type = $teacherType;
        $user->active_status = true; // Admin-created teachers are activated immediately
        $user->save();

        // Handle avatar upload
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $directory = $teacherType === 'quran_teacher' ? 'avatars/quran-teachers' : 'avatars/academic-teachers';
            $avatarPath = $request->file('avatar')->store($directory, 'public');
            $user->avatar = $avatarPath;
            $user->save();
        }

        try {
            if ($teacherType === 'quran_teacher') {
                QuranTeacherProfile::create([
                    'user_id' => $user->id,
                    'academy_id' => $academy->id,
                    'gender' => $request->gender,
                    'educational_qualification' => $request->education_level,
                    'teaching_experience_years' => $request->years_experience,
                    'is_active' => true,
                    'certifications' => $request->certifications ?? [],
                    'avatar' => $avatarPath,
                ]);
            } else {
                AcademicTeacherProfile::create([
                    'user_id' => $user->id,
                    'academy_id' => $academy->id,
                    'gender' => $request->gender,
                    'education_level' => $request->education_level,
                    'university' => $request->university,
                    'teaching_experience_years' => $request->years_experience,
                    'subject_ids' => $request->subjects ?? [],
                    'grade_level_ids' => $request->grade_levels ?? [],
                    'available_days' => $request->available_days ?? [],
                    'certifications' => [],
                    'is_active' => true,
                    'avatar' => $avatarPath,
                ]);
            }
        } catch (QueryException $e) {
            Log::error('Teacher creation failed: '.$e->getMessage(), [
                'user_id' => $user->id,
                'academy_id' => $academy->id,
                'teacher_type' => $teacherType,
            ]);

            if ($avatarPath) {
                Storage::disk('public')->delete($avatarPath);
            }
            $user->delete();

            return back()->withErrors(['error' => __('supervisor.teachers.create_error')])->withInput();
        }

        return redirect()->route('manage.teachers.index', ['subdomain' => $subdomain])
            ->with('success', __('supervisor.teachers.teacher_created'));
    }

    public function edit($subdomain, User $teacher): View
    {
        if (! $this->canManageTeachers()) {
            abort(403);
        }

        $this->ensureTeacherBelongsToScope($teacher);

        $teacher->load(['quranTeacherProfile', 'academicTeacherProfile']);

        $academy = AcademyContextService::getCurrentAcademy();
        $subjects = AcademicSubject::where('academy_id', $academy->id)->where('is_active', true)->orderBy('name')->get();
        $gradeLevels = AcademicGradeLevel::where('academy_id', $academy->id)->where('is_active', true)->orderBy('name')->get();

        return view('supervisor.teachers.edit', compact('teacher', 'subjects', 'gradeLevels'));
    }

    public function update(Request $request, $subdomain, User $teacher): RedirectResponse
    {
        if (! $this->canManageTeachers()) {
            abort(403);
        }

        $this->ensureTeacherBelongsToScope($teacher);

        $rules = [
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($teacher->id)->where('academy_id', $teacher->academy_id)],
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:male,female',
            'education_level' => 'required|in:diploma,bachelor,master,phd,other',
            'university' => 'required|string|max:255',
            'years_experience' => 'required|integer|min:0|max:50',
        ];

        $isQuran = $teacher->user_type === 'quran_teacher';
        $isAcademic = $teacher->user_type === 'academic_teacher';

        if ($isAcademic) {
            $rules['subjects'] = 'required|array|min:1';
            $rules['grade_levels'] = 'required|array|min:1';
            $rules['available_days'] = 'required|array|min:1';
        }

        if ($isQuran) {
            $rules['certifications'] = 'nullable|array';
            $rules['certifications.*'] = 'string|max:255';
        }

        $rules['individual_session_prices'] = 'nullable|array';
        $rules['individual_session_prices.*'] = 'nullable|numeric|min:0';
        $rules['group_session_prices'] = 'nullable|array';
        $rules['group_session_prices.*'] = 'nullable|numeric|min:0';

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $teacher->first_name = $request->first_name;
        $teacher->last_name = $request->last_name;
        $teacher->email = $request->email;
        $teacher->phone = $request->phone;

        try {
            $teacher->save();
        } catch (UniqueConstraintViolationException $e) {
            Log::warning('Supervisor teacher update: duplicate email attempt', [
                'teacher_id' => $teacher->id,
                'email' => $request->email,
                'userId' => auth()->id(),
            ]);

            return back()->withErrors(['email' => __('supervisor.teachers.email_unique')])->withInput();
        }

        if ($request->hasFile('avatar')) {
            if ($teacher->avatar) {
                Storage::disk('public')->delete($teacher->avatar);
            }
            $directory = $isQuran ? 'avatars/quran-teachers' : 'avatars/academic-teachers';
            $avatarPath = $request->file('avatar')->store($directory, 'public');
            $teacher->avatar = $avatarPath;
            $teacher->save();
        }

        if ($isQuran && $teacher->quranTeacherProfile) {
            $teacher->quranTeacherProfile->update([
                'gender' => $request->gender,
                'educational_qualification' => $request->education_level,
                'teaching_experience_years' => $request->years_experience,
                'certifications' => $request->certifications ?? [],
                'individual_session_prices' => $this->cleanPricesArray($request->input('individual_session_prices', [])),
                'group_session_prices' => $this->cleanPricesArray($request->input('group_session_prices', [])),
            ]);
        }

        if ($isAcademic && $teacher->academicTeacherProfile) {
            $teacher->academicTeacherProfile->update([
                'gender' => $request->gender,
                'education_level' => $request->education_level,
                'university' => $request->university,
                'teaching_experience_years' => $request->years_experience,
                'subject_ids' => $request->subjects ?? [],
                'grade_level_ids' => $request->grade_levels ?? [],
                'available_days' => $request->available_days ?? [],
                'individual_session_prices' => $this->cleanPricesArray($request->input('individual_session_prices', [])),
            ]);
        }

        return redirect()->route('manage.teachers.show', ['subdomain' => $subdomain, 'teacher' => $teacher->id])
            ->with('success', __('supervisor.teachers.teacher_updated'));
    }

    public function show($subdomain, User $teacher): View
    {
        if (! $this->canManageTeachers()) {
            abort(403);
        }

        $this->ensureTeacherBelongsToScope($teacher);

        $teacher->load(['quranTeacherProfile', 'academicTeacherProfile']);

        $isQuranTeacher = $teacher->user_type === 'quran_teacher';
        $isAcademicTeacher = $teacher->user_type === 'academic_teacher';

        // Assigned entities
        $assignedCircles = collect();
        $assignedIndividuals = collect();
        $assignedLessons = collect();
        $assignedCourses = collect();

        if ($isQuranTeacher) {
            $assignedCircles = QuranCircle::where('quran_teacher_id', $teacher->id)
                ->withCount('students')
                ->get();
            $assignedIndividuals = QuranIndividualCircle::where('quran_teacher_id', $teacher->id)
                ->where('is_active', true)
                ->with('student')
                ->get();
        }

        if ($isAcademicTeacher) {
            $profileId = $teacher->academicTeacherProfile?->id;
            if ($profileId) {
                $assignedLessons = AcademicIndividualLesson::where('academic_teacher_id', $profileId)
                    ->where('status', 'active')
                    ->with(['student', 'subject'])
                    ->get();
                $assignedCourses = InteractiveCourse::where('assigned_teacher_id', $profileId)
                    ->with(['subject', 'enrollments'])
                    ->get();
            }
        }

        // Session stats this month
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $sessionsThisMonth = 0;
        $completedThisMonth = 0;
        $cancelledThisMonth = 0;

        if ($isQuranTeacher) {
            $sessionsThisMonth = QuranSession::where('quran_teacher_id', $teacher->id)
                ->whereBetween('scheduled_at', [$monthStart, $monthEnd])->count();
            $completedThisMonth = QuranSession::where('quran_teacher_id', $teacher->id)
                ->whereBetween('scheduled_at', [$monthStart, $monthEnd])
                ->where('status', 'completed')->count();
            $cancelledThisMonth = QuranSession::where('quran_teacher_id', $teacher->id)
                ->whereBetween('scheduled_at', [$monthStart, $monthEnd])
                ->where('status', 'cancelled')->count();
        }

        if ($isAcademicTeacher && $teacher->academicTeacherProfile) {
            $profileId = $teacher->academicTeacherProfile->id;
            $academicSessionsMonth = AcademicSession::where('academic_teacher_id', $profileId)
                ->whereBetween('scheduled_at', [$monthStart, $monthEnd])->count();
            $academicCompletedMonth = AcademicSession::where('academic_teacher_id', $profileId)
                ->whereBetween('scheduled_at', [$monthStart, $monthEnd])
                ->where('status', 'completed')->count();
            $academicCancelledMonth = AcademicSession::where('academic_teacher_id', $profileId)
                ->whereBetween('scheduled_at', [$monthStart, $monthEnd])
                ->where('status', 'cancelled')->count();

            $sessionsThisMonth += $academicSessionsMonth;
            $completedThisMonth += $academicCompletedMonth;
            $cancelledThisMonth += $academicCancelledMonth;
        }

        $completionRate = $sessionsThisMonth > 0
            ? round(($completedThisMonth / $sessionsThisMonth) * 100)
            : 0;

        // Total students count
        $totalStudents = 0;
        if ($isQuranTeacher) {
            $individualStudents = QuranIndividualCircle::where('quran_teacher_id', $teacher->id)
                ->where('is_active', true)->count();
            $circleStudents = QuranCircle::where('quran_teacher_id', $teacher->id)
                ->where('status', true)->sum('enrolled_students');
            $totalStudents = $individualStudents + $circleStudents;
        }
        if ($isAcademicTeacher && $teacher->academicTeacherProfile) {
            $totalStudents += AcademicIndividualLesson::where('academic_teacher_id', $teacher->academicTeacherProfile->id)
                ->where('status', 'active')->count();
        }

        // Recent sessions (last 10)
        $recentSessions = collect();

        if ($isQuranTeacher) {
            $recentQuran = QuranSession::where('quran_teacher_id', $teacher->id)
                ->with('student')
                ->latest('scheduled_at')
                ->limit(10)
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'type' => 'quran',
                    'date' => $s->scheduled_at,
                    'student_name' => $s->student?->name ?? '',
                    'status' => $s->status,
                    'title' => $s->title ?? __('supervisor.teachers.quran_session'),
                ]);
            $recentSessions = $recentSessions->merge($recentQuran);
        }

        if ($isAcademicTeacher && $teacher->academicTeacherProfile) {
            $recentAcademic = AcademicSession::where('academic_teacher_id', $teacher->academicTeacherProfile->id)
                ->with('student')
                ->latest('scheduled_at')
                ->limit(10)
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'type' => 'academic',
                    'date' => $s->scheduled_at,
                    'student_name' => $s->student?->name ?? '',
                    'status' => $s->status,
                    'title' => $s->title ?? __('supervisor.teachers.academic_session'),
                ]);
            $recentSessions = $recentSessions->merge($recentAcademic);
        }

        $recentSessions = $recentSessions->sortByDesc('date')->take(10)->values();

        $isAdmin = $this->isAdminUser();

        return view('supervisor.teachers.show', [
            'teacher' => $teacher,
            'isQuranTeacher' => $isQuranTeacher,
            'isAcademicTeacher' => $isAcademicTeacher,
            'assignedCircles' => $assignedCircles,
            'assignedIndividuals' => $assignedIndividuals,
            'assignedLessons' => $assignedLessons,
            'assignedCourses' => $assignedCourses,
            'sessionsThisMonth' => $sessionsThisMonth,
            'completedThisMonth' => $completedThisMonth,
            'cancelledThisMonth' => $cancelledThisMonth,
            'completionRate' => $completionRate,
            'totalStudents' => $totalStudents,
            'recentSessions' => $recentSessions,
            'isAdmin' => $isAdmin,
            'canManageTeachers' => $this->canManageTeachers(),
        ]);
    }

    private function cleanPricesArray(?array $prices): ?array
    {
        if (empty($prices)) {
            return null;
        }

        $validDurations = \App\Enums\SessionDuration::values();
        $cleaned = [];
        foreach ($prices as $duration => $price) {
            if (! in_array((int) $duration, $validDurations, true)) {
                continue;
            }
            if ($price !== null && $price !== '') {
                $cleaned[(string) $duration] = (float) $price;
            }
        }

        return empty($cleaned) ? null : $cleaned;
    }

    private function ensureTeacherBelongsToScope(User $teacher): void
    {
        $allIds = $this->getAllAssignedTeacherIds();
        if (! in_array($teacher->id, $allIds)) {
            abort(403);
        }
    }
}
