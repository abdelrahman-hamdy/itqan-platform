<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\AcademicGradeLevel;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSubject;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use App\Services\AcademyContextService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRules;
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
                        'gender' => $user->quranTeacherProfile?->gender ?? null,
                        'phone' => $user->phone ?? '',
                        'is_active' => (bool) ($user->active_status ?? false),
                        'rating' => (float) ($user->quranTeacherProfile?->rating ?? 0),
                        'created_at' => $user->created_at,
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
                        'gender' => $user->academicTeacherProfile?->gender ?? null,
                        'phone' => $user->phone ?? '',
                        'is_active' => (bool) ($user->active_status ?? false),
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
        ]);
    }

    public function toggleStatus(Request $request, $subdomain, User $teacher): RedirectResponse
    {
        if (!$this->isAdminUser()) {
            abort(403);
        }

        $this->ensureTeacherBelongsToScope($teacher);

        $teacher->active_status = !$teacher->active_status;
        $teacher->save();

        return redirect()->back()->with('success', __('supervisor.teachers.status_updated'));
    }

    public function resetPassword(Request $request, $subdomain, User $teacher): RedirectResponse
    {
        if (!$this->isAdminUser()) {
            abort(403);
        }

        $this->ensureTeacherBelongsToScope($teacher);

        $newPassword = $request->input('new_password');
        $confirmation = $request->input('new_password_confirmation');

        if (!$newPassword || mb_strlen($newPassword) < 6) {
            return redirect()->back()->with('error', __('supervisor.teachers.password_too_short'));
        }

        if ($newPassword !== $confirmation) {
            return redirect()->back()->with('error', __('supervisor.teachers.passwords_dont_match'));
        }

        $teacher->password = Hash::make($newPassword);
        $teacher->save();

        return redirect()->back()->with('success', __('supervisor.teachers.password_reset_success'));
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

    public function create(Request $request, $subdomain = null): View
    {
        if (!$this->isAdminUser()) {
            abort(403);
        }

        $academy = AcademyContextService::getCurrentAcademy();
        $subjects = AcademicSubject::where('academy_id', $academy->id)->where('is_active', true)->orderBy('name')->get();
        $gradeLevels = AcademicGradeLevel::where('academy_id', $academy->id)->where('is_active', true)->orderBy('name')->get();

        return view('supervisor.teachers.create', compact('academy', 'subjects', 'gradeLevels'));
    }

    public function store(Request $request, $subdomain = null): RedirectResponse
    {
        if (!$this->isAdminUser()) {
            abort(403);
        }

        $teacherType = $request->input('teacher_type', 'quran_teacher');

        $rules = [
            'teacher_type' => 'required|in:quran_teacher,academic_teacher',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
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

        $user = new User([
            'academy_id' => $academy->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'education_level' => $request->education_level,
            'university' => $request->university,
            'years_experience' => $request->years_experience,
        ]);
        $user->user_type = $teacherType;
        $user->active_status = true; // Admin-created teachers are activated immediately
        $user->save();

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
                ]);
            }
        } catch (QueryException $e) {
            Log::error('Teacher creation failed: '.$e->getMessage(), [
                'user_id' => $user->id,
                'academy_id' => $academy->id,
                'teacher_type' => $teacherType,
            ]);

            $user->delete();

            return back()->withErrors(['error' => __('supervisor.teachers.create_error')])->withInput();
        }

        return redirect()->route('manage.teachers.index', ['subdomain' => $subdomain])
            ->with('success', __('supervisor.teachers.teacher_created'));
    }

    private function ensureTeacherBelongsToScope(User $teacher): void
    {
        $allIds = $this->getAllAssignedTeacherIds();
        if (!in_array($teacher->id, $allIds)) {
            abort(403);
        }
    }
}
