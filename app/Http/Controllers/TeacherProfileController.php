<?php

namespace App\Http\Controllers;

use App\Constants\DefaultAcademy;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\TrialRequestStatus;
use App\Http\Requests\UpdateTeacherProfileRequest;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\InteractiveCourseSession;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\QuranTrialRequest;
use App\Models\TeacherEarning;
use App\Models\User;
use App\Services\AcademyContextService;
use App\Services\TeacherEarningsDisplayService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Storage;

class TeacherProfileController extends Controller
{
    public function __construct(
        protected TeacherEarningsDisplayService $earningsDisplayService
    ) {}

    public function index(): View
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Determine teacher type and get profile
        $teacherProfile = $this->getTeacherProfile($user);

        if (! $teacherProfile) {
            abort(404, 'Teacher profile not found');
        }

        // Get teacher-specific data based on type
        if ($user->isQuranTeacher()) {
            $data = $this->getQuranTeacherData($user, $teacherProfile);
        } else {
            $data = $this->getAcademicTeacherData($user, $teacherProfile);
        }

        // Calculate statistics
        $stats = $this->calculateTeacherStats($user, $teacherProfile);

        return view('teacher.profile', array_merge($data, [
            'teacherProfile' => $teacherProfile,
            'stats' => $stats,
            'academy' => $academy,
        ]));
    }

    public function earnings(Request $request): View
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $user = Auth::user();
        $teacherProfile = $this->getTeacherProfile($user);
        $academy = $user->academy;

        if (! $teacherProfile) {
            abort(404, 'Teacher profile not found');
        }

        $teacherType = $user->isQuranTeacher() ? 'quran_teacher' : 'academic_teacher';
        $teacherId = $teacherProfile->id;
        $academyId = $user->academy_id;

        $currencySymbol = getTeacherEarningsCurrencySymbol($academy);
        $timezone = $academy->timezone?->value ?? AcademyContextService::getTimezone();

        // Filter values
        $currentMonth = $request->input('month');
        $currentSource = $request->input('source');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Build base query with all filters applied
        $baseQuery = TeacherEarning::forTeacher($teacherType, $teacherId)->where('academy_id', $academyId);
        $this->applyEarningsFilters($baseQuery, $currentMonth, $startDate, $endDate, $currentSource);

        // Stats from filtered query
        $stats = [
            'totalEarnings' => (clone $baseQuery)->sum('amount'),
            'finalizedAmount' => (clone $baseQuery)->finalized()->sum('amount'),
            'unpaidAmount' => (clone $baseQuery)->unpaid()->sum('amount'),
            'sessionsCount' => (clone $baseQuery)->count(),
        ];

        // Paginated earnings query (same filters + eager loading)
        $query = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->with([
                'session' => function ($morphTo) {
                    $morphTo->morphWith([
                        QuranSession::class => ['individualCircle', 'circle', 'student'],
                        AcademicSession::class => ['academicIndividualLesson.subject', 'student'],
                        InteractiveCourseSession::class => ['course'],
                    ]);
                },
            ]);
        $this->applyEarningsFilters($query, $currentMonth, $startDate, $endDate, $currentSource);

        $earnings = $query->orderByDesc('session_completed_at')->paginate(15);

        // Available months and sources for filter dropdowns
        $availableMonths = $this->earningsDisplayService->getAvailableMonths($teacherType, $teacherId, $academyId);
        $sources = $this->buildSourcesList($teacherType, $teacherId, $academyId, $user);

        return view('teacher.earnings', [
            'academy' => $academy,
            'currencySymbol' => $currencySymbol,
            'timezone' => $timezone,
            'stats' => $stats,
            'earnings' => $earnings,
            'availableMonths' => $availableMonths,
            'sources' => $sources,
            'currentMonth' => $currentMonth,
            'currentSource' => $currentSource,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Apply all earnings filters (date, month, source) to a query.
     */
    private function applyEarningsFilters($query, ?string $month, ?string $startDate, ?string $endDate, ?string $source): void
    {
        if ($startDate || $endDate) {
            if ($startDate) {
                $query->where('session_completed_at', '>=', Carbon::parse($startDate)->startOfDay());
            }
            if ($endDate) {
                $query->where('session_completed_at', '<=', Carbon::parse($endDate)->endOfDay());
            }
        } elseif ($month) {
            $parts = explode('-', $month);
            if (count($parts) === 2) {
                $query->forMonth((int) $parts[0], (int) $parts[1]);
            }
        }

        if ($source) {
            $this->applySourceFilter($query, $source);
        }
    }

    /**
     * Apply source filter to earnings query.
     * Source format: {type}_{id} e.g. "individual_circle_5", "interactive_course_12"
     */
    private function applySourceFilter($query, string $source): void
    {
        // Parse source: last segment is the ID, everything before is the type
        $lastUnderscore = strrpos($source, '_');
        if ($lastUnderscore === false) {
            return;
        }

        $sourceType = substr($source, 0, $lastUnderscore);
        $sourceId = (int) substr($source, $lastUnderscore + 1);

        if ($sourceId <= 0) {
            return;
        }

        match ($sourceType) {
            'individual_circle' => $query->where('session_type', QuranSession::class)
                ->whereHas('session', fn ($q) => $q->where('individual_circle_id', $sourceId)),
            'group_circle' => $query->where('session_type', QuranSession::class)
                ->whereHas('session', fn ($q) => $q->where('circle_id', $sourceId)),
            'academic_lesson' => $query->where('session_type', AcademicSession::class)
                ->whereHas('session', fn ($q) => $q->where('academic_individual_lesson_id', $sourceId)),
            'interactive_course' => $query->where('session_type', InteractiveCourseSession::class)
                ->whereHas('session', fn ($q) => $q->where('course_id', $sourceId)),
            default => null,
        };
    }

    /**
     * Build dynamic sources list from existing earnings for filter dropdown.
     */
    private function buildSourcesList(string $teacherType, int $teacherId, int $academyId, $user): array
    {
        $allEarnings = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->with([
                'session' => function ($morphTo) {
                    $morphTo->morphWith([
                        QuranSession::class => ['individualCircle', 'circle', 'student'],
                        AcademicSession::class => ['academicIndividualLesson.subject', 'student'],
                        InteractiveCourseSession::class => ['course'],
                    ]);
                },
            ])
            ->get();

        $sources = [];
        foreach ($allEarnings as $earning) {
            $source = $this->earningsDisplayService->determineEarningSourcePublic($earning, $user);
            if (! isset($sources[$source['key']])) {
                $sources[$source['key']] = [
                    'value' => $source['key'],
                    'label' => $source['name'],
                    'type' => $source['type'],
                ];
            }
        }

        return array_values($sources);
    }

    public function schedule(): RedirectResponse
    {
        // Redirect to the teacher profile page
        $subdomain = request()->route('subdomain') ?? DefaultAcademy::subdomain();

        return redirect()->route('teacher.profile', ['subdomain' => $subdomain]);
    }

    public function edit(): View
    {
        $user = Auth::user();
        $teacherProfile = $this->getTeacherProfile($user);

        if (! $teacherProfile) {
            abort(404, 'Teacher profile not found');
        }

        return view('teacher.edit-profile', [
            'teacherProfile' => $teacherProfile,
        ]);
    }

    public function update(UpdateTeacherProfileRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $teacherProfile = $this->getTeacherProfile($user);

        if (! $teacherProfile) {
            abort(404, 'Teacher profile not found');
        }

        $validated = $request->validated();

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($teacherProfile->avatar && Storage::disk('public')->exists($teacherProfile->avatar)) {
                Storage::disk('public')->delete($teacherProfile->avatar);
            }

            $path = $request->file('avatar')->store(
                $user->isQuranTeacher() ? 'avatars/quran-teachers' : 'avatars/academic-teachers',
                'public'
            );
            $validated['avatar'] = $path;
        }

        // Handle preview video removal
        if ($request->boolean('remove_preview_video')) {
            if ($teacherProfile->preview_video && Storage::disk('public')->exists($teacherProfile->preview_video)) {
                Storage::disk('public')->delete($teacherProfile->preview_video);
            }
            $validated['preview_video'] = null;
        }

        // Handle preview video upload
        if ($request->hasFile('preview_video')) {
            // Delete old video if exists
            if ($teacherProfile->preview_video && Storage::disk('public')->exists($teacherProfile->preview_video)) {
                Storage::disk('public')->delete($teacherProfile->preview_video);
            }

            $path = $request->file('preview_video')->store(
                $user->isQuranTeacher() ? 'videos/quran-teachers' : 'videos/academic-teachers',
                'public'
            );
            $validated['preview_video'] = $path;
        }

        // Remove non-model fields before update
        unset($validated['remove_preview_video']);

        // Remove null values for NOT NULL columns to avoid SQL errors
        // These fields may come as null when not present in the form submission
        $notNullFields = ['educational_qualification', 'education_level'];
        foreach ($notNullFields as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] === null) {
                unset($validated[$field]);
            }
        }

        // Update teacher profile
        $teacherProfile->update($validated);

        // Also update user info
        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'] ?? $user->phone,
        ]);

        return back()->with('success', 'تم تحديث الملف الشخصي بنجاح');
    }

    /**
     * Get teacher profile based on user type
     */
    private function getTeacherProfile($user)
    {
        if ($user->isQuranTeacher()) {
            return QuranTeacherProfile::where('user_id', $user->id)->first();
        } elseif ($user->isAcademicTeacher()) {
            return AcademicTeacherProfile::where('user_id', $user->id)->first();
        }

        return null;
    }

    /**
     * Get Quran teacher specific data
     */
    private function getQuranTeacherData($user, $teacherProfile)
    {
        $academy = $user->academy;

        // Get assigned Quran circles (admin creates and assigns)
        $assignedCircles = QuranCircle::where('quran_teacher_id', $user->id)
            ->where('academy_id', $user->academy_id)
            ->with(['students', 'academy'])
            ->get();

        // Get pending and scheduled trial requests
        $pendingTrialRequests = QuranTrialRequest::where('teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->whereIn('status', [
                TrialRequestStatus::PENDING->value,
                TrialRequestStatus::SCHEDULED->value,
            ])
            ->with(['student', 'academy', 'trialSession'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get active subscriptions
        $activeSubscriptions = QuranSubscription::where('quran_teacher_id', $user->id)
            ->where('academy_id', $academy->id)
            ->whereIn('status', [
                SessionSubscriptionStatus::ACTIVE->value,
                SessionSubscriptionStatus::PENDING->value,
            ])
            ->whereIn('payment_status', ['paid', 'pending'])
            ->with(['student', 'package', 'individualCircle'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get recent sessions
        $recentSessions = QuranSession::where('quran_teacher_id', $user->id)
            ->where('academy_id', $academy->id)
            ->whereIn('status', [
                SessionStatus::SCHEDULED->value,
                SessionStatus::COMPLETED->value,
            ])
            ->with(['student', 'subscription'])
            ->orderBy('scheduled_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'assignedCircles' => $assignedCircles,
            'pendingTrialRequests' => $pendingTrialRequests,
            'activeSubscriptions' => $activeSubscriptions,
            'recentSessions' => $recentSessions,
            'teacherType' => 'quran',
        ];
    }

    /**
     * Get Academic teacher specific data
     */
    private function getAcademicTeacherData($user, $teacherProfile)
    {
        $academy = $user->academy;

        // Get created courses by teacher
        // Note: InteractiveCourses are only assigned by admin (no teacher creation)
        $createdInteractiveCourses = collect(); // Empty for now - interactive courses are admin-assigned only

        $createdRecordedCourses = collect(); // Recorded courses are not linked to specific teachers

        // Get assigned courses (admin creates and assigns)
        $assignedInteractiveCourses = InteractiveCourse::where('assigned_teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->with(['enrollments', 'academy'])
            ->orderBy('created_at', 'desc')
            ->get();

        // For now, recorded courses don't have assignment - only creation
        $assignedRecordedCourses = collect(); // Empty for now

        // Get private academic lessons (subscriptions)
        $privateLessons = AcademicSubscription::where('teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->with(['student', 'subject', 'gradeLevel'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'createdInteractiveCourses' => $createdInteractiveCourses,
            'createdRecordedCourses' => $createdRecordedCourses,
            'assignedInteractiveCourses' => $assignedInteractiveCourses,
            'assignedRecordedCourses' => $assignedRecordedCourses,
            'privateLessons' => $privateLessons,
            'teacherType' => 'academic',
        ];
    }

    /**
     * Calculate teacher statistics
     */
    private function calculateTeacherStats($user, $teacherProfile)
    {
        $academy = $user->academy;
        $currentMonth = Carbon::now();

        if ($user->isQuranTeacher()) {
            return $this->calculateQuranTeacherStats($user, $teacherProfile, $currentMonth);
        } else {
            return $this->calculateAcademicTeacherStats($user, $teacherProfile, $currentMonth);
        }
    }

    /**
     * Calculate Quran teacher stats
     */
    private function calculateQuranTeacherStats($user, $teacherProfile, $currentMonth)
    {
        // Total students from assigned circles - use Eloquent with automatic tenant scoping
        $totalStudents = QuranCircle::where('quran_teacher_id', $user->id)
            ->with('students')
            ->get()
            ->pluck('students')
            ->flatten()
            ->unique('id')
            ->count();

        // Also count individual circle students
        $individualStudents = QuranIndividualCircle::where('quran_teacher_id', $teacherProfile->id)
            ->distinct('student_id')
            ->count('student_id');

        $totalStudents = $totalStudents + $individualStudents;

        // Active circles
        $activeCircles = QuranCircle::where('quran_teacher_id', $user->id)
            ->where('status', true)
            ->count();

        // This month sessions - count completed and ongoing sessions
        $thisMonthSessions = QuranSession::where('quran_teacher_id', $user->id)
            ->whereMonth('scheduled_at', $currentMonth->month)
            ->whereYear('scheduled_at', $currentMonth->year)
            ->whereIn('status', [
                SessionStatus::COMPLETED,
                SessionStatus::ONGOING,
            ])
            ->count();

        // Monthly earnings
        $monthlyEarnings = $this->calculateMonthlyEarnings($user, $teacherProfile, $currentMonth);

        return [
            'totalStudents' => $totalStudents,
            'activeCircles' => $activeCircles,
            'thisMonthSessions' => $thisMonthSessions,
            'monthlyEarnings' => $monthlyEarnings,
            'teacherRating' => $teacherProfile->rating ?? 0,
        ];
    }

    /**
     * Calculate Academic teacher stats
     */
    private function calculateAcademicTeacherStats($user, $teacherProfile, $currentMonth)
    {
        // Total students from courses - use DB query to avoid loading all users
        $courseIds = InteractiveCourse::where('assigned_teacher_id', $teacherProfile->id)
            ->pluck('id');

        $totalStudents = InteractiveCourseEnrollment::whereIn('course_id', $courseIds)
            ->distinct('student_id')
            ->count('student_id');

        // Also count private lesson students
        $privateLessonStudents = AcademicSubscription::where('teacher_id', $teacherProfile->id)
            ->distinct('student_id')
            ->count('student_id');

        $totalStudents = $totalStudents + $privateLessonStudents;

        // Active courses (both created and assigned)
        $activeCourses = InteractiveCourse::where('assigned_teacher_id', $teacherProfile->id)
            ->where('status', 'active')
            ->count();

        // This month sessions - count academic sessions and interactive course sessions
        $academicSessionCount = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->whereMonth('scheduled_at', $currentMonth->month)
            ->whereYear('scheduled_at', $currentMonth->year)
            ->whereIn('status', [
                SessionStatus::COMPLETED,
                SessionStatus::ONGOING,
            ])
            ->count();

        $interactiveSessionCount = InteractiveCourseSession::whereIn('course_id', $courseIds)
            ->whereMonth('scheduled_at', $currentMonth->month)
            ->whereYear('scheduled_at', $currentMonth->year)
            ->whereIn('status', [
                SessionStatus::COMPLETED,
                SessionStatus::ONGOING,
            ])
            ->count();

        $thisMonthSessions = $academicSessionCount + $interactiveSessionCount;

        // Monthly earnings
        $monthlyEarnings = $this->calculateMonthlyEarnings($user, $teacherProfile, $currentMonth);

        return [
            'totalStudents' => $totalStudents,
            'activeCourses' => $activeCourses,
            'thisMonthSessions' => $thisMonthSessions,
            'monthlyEarnings' => $monthlyEarnings,
            'teacherRating' => $teacherProfile->rating ?? 0,
        ];
    }

    /**
     * Calculate monthly earnings for a teacher
     */
    private function calculateMonthlyEarnings($user, $teacherProfile, $currentMonth): float
    {
        $academyId = $user->academy_id ?? 1;
        $teacherType = $teacherProfile instanceof QuranTeacherProfile
            ? 'quran_teacher'
            : 'academic_teacher';

        return $this->earningsDisplayService->calculateMonthlyEarnings(
            $teacherType,
            $teacherProfile->id,
            $academyId,
            $currentMonth
        );
    }

    /**
     * Get upcoming sessions for the teacher
     */
    private function getUpcomingSessions($user, $teacherProfile)
    {
        $upcomingSessions = collect();

        // Determine teacher type and get appropriate sessions
        if ($teacherProfile instanceof QuranTeacherProfile) {
            // Quran teacher - get QuranSessions
            $upcomingSessions = QuranSession::where('quran_teacher_id', $user->id)
                ->whereIn('status', [
                    SessionStatus::SCHEDULED,
                    SessionStatus::READY,
                ])
                ->where('scheduled_at', '>=', now())
                ->orderBy('scheduled_at')
                ->limit(10)
                ->get()
                ->map(fn ($session) => [
                    'id' => $session->id,
                    'type' => 'quran',
                    'title' => $session->title ?? 'جلسة قرآن',
                    'scheduled_at' => $session->scheduled_at,
                    'duration' => $session->duration_minutes,
                    'status' => $session->status->value,
                ]);
        } elseif ($teacherProfile instanceof AcademicTeacherProfile) {
            // Academic teacher - get AcademicSessions and InteractiveCourseSessions
            $academicSessions = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
                ->whereIn('status', [
                    SessionStatus::SCHEDULED,
                    SessionStatus::READY,
                ])
                ->where('scheduled_at', '>=', now())
                ->orderBy('scheduled_at')
                ->limit(5)
                ->get()
                ->map(fn ($session) => [
                    'id' => $session->id,
                    'type' => 'academic',
                    'title' => $session->title ?? 'درس أكاديمي',
                    'scheduled_at' => $session->scheduled_at,
                    'duration' => $session->duration_minutes,
                    'status' => $session->status->value,
                ]);

            $interactiveSessions = InteractiveCourseSession::whereHas('course', function ($query) use ($teacherProfile) {
                $query->where('assigned_teacher_id', $teacherProfile->id);
            })
                ->whereIn('status', [
                    SessionStatus::SCHEDULED,
                    SessionStatus::READY,
                ])
                ->where('scheduled_at', '>=', now())
                ->orderBy('scheduled_at')
                ->limit(5)
                ->get()
                ->map(fn ($session) => [
                    'id' => $session->id,
                    'type' => 'interactive',
                    'title' => $session->title ?? $session->course?->title ?? 'دورة تفاعلية',
                    'scheduled_at' => $session->scheduled_at,
                    'duration' => $session->duration_minutes,
                    'status' => $session->status->value,
                ]);

            $upcomingSessions = $academicSessions->concat($interactiveSessions)
                ->sortBy('scheduled_at')
                ->take(10)
                ->values();
        }

        return $upcomingSessions;
    }
}
