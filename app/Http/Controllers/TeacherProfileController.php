<?php

namespace App\Http\Controllers;

use Storage;
use App\Models\QuranTrialRequest;
use App\Models\QuranSubscription;
use App\Models\QuranSession;
use App\Models\AcademicSubscription;
use App\Models\QuranIndividualCircle;
use App\Models\InteractiveCourseEnrollment;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Constants\DefaultAcademy;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\TrialRequestStatus;
use App\Http\Requests\UpdateTeacherProfileRequest;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use App\Services\AcademyContextService;
use App\Services\TeacherEarningsDisplayService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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
        $user = Auth::user();
        $teacherProfile = $this->getTeacherProfile($user);
        $academy = $user->academy;

        if (! $teacherProfile) {
            abort(404, 'Teacher profile not found');
        }

        // Use full class name for polymorphic teacher_type column
        $teacherType = $user->isQuranTeacher() ? QuranTeacherProfile::class : AcademicTeacherProfile::class;
        $teacherId = $teacherProfile->id;
        $academyId = $user->academy_id;

        $currencyLabel = $academy->currency?->label() ?? 'ريال سعودي (SAR)';
        $timezone = $academy->timezone?->value ?? AcademyContextService::getTimezone();

        $selectedMonth = $request->get('month', now()->format('Y-m'));
        $isAllTime = $selectedMonth === 'all';

        if (! $isAllTime) {
            [$year, $month] = explode('-', $selectedMonth);
            $year = (int) $year;
            $month = (int) $month;
        } else {
            $year = null;
            $month = null;
        }

        $availableMonths = $this->earningsDisplayService->getAvailableMonths($teacherType, $teacherId, $academyId);
        $earningsStats = $this->earningsDisplayService->getEarningsStats($teacherType, $teacherId, $academyId, $year, $month);
        $earningsBySource = $this->earningsDisplayService->getEarningsGroupedBySource($teacherType, $teacherId, $academyId, $user, $year, $month);

        return view('teacher.earnings', [
            'teacherProfile' => $teacherProfile,
            'teacherType' => $teacherType,
            'academy' => $academy,
            'currency' => $currencyLabel,
            'timezone' => $timezone,
            'stats' => $earningsStats,
            'earningsBySource' => $earningsBySource,
            'availableMonths' => $availableMonths,
            'selectedMonth' => $selectedMonth,
            'isAllTime' => $isAllTime,
        ]);
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
        // Use full class name for polymorphic teacher_type column
        $teacherType = $teacherProfile instanceof QuranTeacherProfile
            ? QuranTeacherProfile::class
            : AcademicTeacherProfile::class;

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
                ->where('scheduled_date', '>=', now()->toDateString())
                ->orderBy('scheduled_date')
                ->orderBy('scheduled_time')
                ->limit(5)
                ->get()
                ->map(fn ($session) => [
                    'id' => $session->id,
                    'type' => 'interactive',
                    'title' => $session->title ?? $session->course?->title ?? 'دورة تفاعلية',
                    'scheduled_at' => $session->scheduled_date?->setTimeFromTimeString($session->scheduled_time ?? '00:00:00'),
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
