<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Http\Middleware\ChildSelectionMiddleware;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourse;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Services\ParentChildVerificationService;
use App\Services\ParentDataService;
use App\Services\Reports\AcademicReportService;
use App\Services\Reports\InteractiveCourseReportService;
use App\Services\Reports\QuranReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Parent Report Controller
 *
 * Handles viewing child progress and attendance reports.
 * Shows per-subscription data for each child with specific details.
 */
class ParentReportController extends Controller
{
    public function __construct(
        protected ParentDataService $dataService,
        protected ParentChildVerificationService $verificationService,
        protected QuranReportService $quranReportService,
        protected AcademicReportService $academicReportService,
        protected InteractiveCourseReportService $interactiveReportService
    ) {
        // Enforce read-only access
        $this->middleware('parent.readonly');
    }

    /**
     * Child progress report - shows per-subscription progress for each child
     * Includes both attendance and progress data in a unified view
     */
    public function progressReport(Request $request): View
    {
        $user = Auth::user();
        $parent = $user->parentProfile;
        $subdomain = $request->route('subdomain') ?? $user->academy?->subdomain ?? 'itqan-academy';

        // Get children with their subscriptions
        $children = $parent->students()->with('user')->get();

        // Get child IDs from middleware (session-based selection) for attendance report
        $childIds = ChildSelectionMiddleware::getChildIds();

        // Build attendance report for all children (aggregated)
        $attendanceReport = $this->buildAttendanceReport($parent, $childIds);

        // Build detailed per-child subscriptions data
        $childrenData = [];
        foreach ($children as $child) {
            $childData = $this->getChildSubscriptionsWithProgress($child, $parent->academy_id, $subdomain);
            $childrenData[] = [
                'child' => $child,
                'subscriptions' => $childData,
            ];
        }

        return view('parent.reports.progress', [
            'parent' => $parent,
            'user' => $user,
            'children' => $children,
            'childrenData' => $childrenData,
            'attendanceReport' => $attendanceReport,
            'subdomain' => $subdomain,
        ]);
    }

    /**
     * Get all subscriptions for a child with their progress data
     */
    protected function getChildSubscriptionsWithProgress($child, $academyId, $subdomain): array
    {
        $result = [
            'quran' => [],
            'academic' => [],
            'interactive' => [],
        ];

        // Get Quran subscriptions (individual circles)
        // Note: QuranSubscription.student_id references User.id, not StudentProfile.id
        $quranSubscriptions = QuranSubscription::with(['quranTeacher.user', 'individualCircle'])
            ->where('student_id', $child->user_id)
            ->where('academy_id', $academyId)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($quranSubscriptions as $subscription) {
            $circle = $subscription->individualCircle;
            // QuranSession.student_id also references User.id
            $sessions = QuranSession::where('student_id', $child->user_id)
                ->where('academy_id', $academyId)
                ->when($circle, fn ($q) => $q->where('individual_circle_id', $circle->id))
                ->get();

            $totalSessions = $sessions->count();
            $completedSessions = $sessions->where('status', SessionStatus::COMPLETED->value)->count();
            $attendanceRate = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100) : 0;

            // Get average performance from session reports
            $avgPerformance = $this->getQuranPerformance($child->user_id, $sessions->pluck('id')->toArray());

            $result['quran'][] = [
                'subscription' => $subscription,
                'circle' => $circle,
                'name' => $circle?->name ?? ($subscription->subscription_type === 'individual' ? 'حلقة فردية' : 'حلقة جماعية'),
                'teacher_name' => $subscription->quranTeacher?->user?->name ?? 'غير محدد',
                'status' => $subscription->status,
                'status_label' => $this->getStatusLabel($subscription->status),
                'started_at' => $subscription->starts_at,
                'total_sessions' => $totalSessions,
                'completed_sessions' => $completedSessions,
                'attendance_rate' => $attendanceRate,
                'performance_score' => $avgPerformance,
                'report_url' => $circle ? route('parent.reports.quran.individual', [
                    'subdomain' => $subdomain,
                    'circle' => $circle->id,
                ]) : null,
            ];
        }

        // Get Academic subscriptions
        // Note: AcademicSubscription.student_id references User.id, not StudentProfile.id
        $academicSubscriptions = AcademicSubscription::with(['academicTeacher.user', 'subject'])
            ->where('student_id', $child->user_id)
            ->where('academy_id', $academyId)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($academicSubscriptions as $subscription) {
            // AcademicSession.student_id also references User.id
            $sessions = AcademicSession::where('student_id', $child->user_id)
                ->where('academy_id', $academyId)
                ->where('academic_subscription_id', $subscription->id)
                ->get();

            $totalSessions = $sessions->count();
            $completedSessions = $sessions->where('status', SessionStatus::COMPLETED->value)->count();
            $attendanceRate = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100) : 0;

            // Get average performance from homework
            $avgPerformance = $this->getAcademicPerformance($child->user_id, $sessions->pluck('id')->toArray());

            $result['academic'][] = [
                'subscription' => $subscription,
                'name' => $subscription->subject?->name ?? $subscription->subject_name ?? 'مادة دراسية',
                'teacher_name' => $subscription->academicTeacher?->user?->name ?? 'غير محدد',
                'status' => $subscription->status,
                'status_label' => $this->getStatusLabel($subscription->status),
                'started_at' => $subscription->starts_at,
                'total_sessions' => $totalSessions,
                'completed_sessions' => $completedSessions,
                'attendance_rate' => $attendanceRate,
                'performance_score' => $avgPerformance,
                'report_url' => route('parent.reports.academic.subscription', [
                    'subdomain' => $subdomain,
                    'subscription' => $subscription->id,
                ]),
            ];
        }

        // Get Interactive course enrollments
        // Note: CourseSubscription.student_id references User.id, not StudentProfile.id
        $courseEnrollments = CourseSubscription::with(['course.assignedTeacher.user'])
            ->where('student_id', $child->user_id)
            ->where('academy_id', $academyId)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($courseEnrollments as $enrollment) {
            $course = $enrollment->course;
            if (! $course) {
                continue;
            }

            $totalSessions = $course->sessions()->count();
            $attendedSessions = $course->sessions()
                ->whereHas('attendances', fn ($q) => $q->where('student_id', $child->user_id))
                ->count();
            $attendanceRate = $totalSessions > 0 ? round(($attendedSessions / $totalSessions) * 100) : 0;

            // Get performance from course homework
            $avgPerformance = $this->getInteractivePerformance($child->user_id, $course->id);

            $result['interactive'][] = [
                'enrollment' => $enrollment,
                'course' => $course,
                'name' => $course->title,
                'teacher_name' => $course->assignedTeacher?->user?->name ?? 'غير محدد',
                'status' => $enrollment->status,
                'status_label' => $this->getStatusLabel($enrollment->status),
                'started_at' => $enrollment->created_at,
                'total_sessions' => $totalSessions,
                'attended_sessions' => $attendedSessions,
                'attendance_rate' => $attendanceRate,
                'progress_percentage' => $enrollment->progress_percentage ?? 0,
                'performance_score' => $avgPerformance,
                'report_url' => route('parent.reports.interactive.course', [
                    'subdomain' => $subdomain,
                    'course' => $course->id,
                ]),
            ];
        }

        return $result;
    }

    /**
     * Get Quran performance from session reports
     */
    protected function getQuranPerformance($userId, array $sessionIds): float
    {
        if (empty($sessionIds)) {
            return 0;
        }

        $reports = \App\Models\StudentSessionReport::whereIn('session_id', $sessionIds)
            ->where('student_id', $userId)
            ->get();

        if ($reports->isEmpty()) {
            return 0;
        }

        $scores = [];
        foreach ($reports as $report) {
            if ($report->new_memorization_degree > 0) {
                $scores[] = $report->new_memorization_degree;
            }
            if ($report->reservation_degree > 0) {
                $scores[] = $report->reservation_degree;
            }
        }

        return count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0;
    }

    /**
     * Get Academic performance from homework submissions
     * Uses AcademicHomeworkSubmission with direct foreign key
     */
    protected function getAcademicPerformance($userId, array $sessionIds): float
    {
        if (empty($sessionIds)) {
            return 0;
        }

        $avgScore = \App\Models\AcademicHomeworkSubmission::where('student_id', $userId)
            ->whereIn('academic_session_id', $sessionIds)
            ->whereNotNull('score')
            ->avg('score');

        return $avgScore ? round($avgScore, 1) : 0;
    }

    /**
     * Get Interactive course performance
     * Note: Interactive courses don't have a homework submission model currently
     */
    protected function getInteractivePerformance($userId, $courseId): float
    {
        // Interactive courses don't have a submission tracking model
        // Return 0 until interactive course submissions are implemented
        return 0;
    }

    /**
     * Get Arabic status label - handles both string and enum types
     */
    protected function getStatusLabel(mixed $status): string
    {
        // If it's a SessionSubscriptionStatus enum, use its label() method
        if ($status instanceof \App\Enums\SessionSubscriptionStatus) {
            return $status->label();
        }

        // If it's a BackedEnum with a value, get the string value
        if ($status instanceof \BackedEnum) {
            $status = $status->value;
        }

        // Handle string values
        return match ((string) $status) {
            'active' => 'نشط',
            'inactive', 'expired' => 'منتهي',
            'paused' => 'متوقف مؤقتاً',
            'pending' => 'قيد الانتظار',
            'cancelled' => 'ملغي',
            'completed' => 'مكتمل',
            default => (string) $status,
        };
    }

    /**
     * Child attendance report - shows comprehensive attendance report
     */
    public function attendanceReport(Request $request): View
    {
        $user = Auth::user();
        $parent = $user->parentProfile;

        // Get child IDs from middleware (session-based selection)
        $childIds = ChildSelectionMiddleware::getChildIds();

        // Build attendance report
        $attendanceReport = $this->buildAttendanceReport($parent, $childIds);

        // Get children for display
        $children = $parent->students()->with('user')->get();

        // Get per-child attendance data
        $childrenAttendance = [];
        foreach ($children as $child) {
            $childAttendance = $this->buildAttendanceReport($parent, [$child->id]);
            $childrenAttendance[] = [
                'child' => $child,
                'attendance' => $childAttendance,
            ];
        }

        return view('parent.reports.attendance', [
            'parent' => $parent,
            'user' => $user,
            'children' => $children,
            'attendanceReport' => $attendanceReport,
            'childrenAttendance' => $childrenAttendance,
        ]);
    }

    /**
     * Build progress report for given children
     */
    protected function buildProgressReport($parent, array $childIds): array
    {
        // Convert StudentProfile IDs to User IDs (for models that reference User.id)
        $userIds = \App\Models\StudentProfile::whereIn('id', $childIds)
            ->pluck('user_id')
            ->toArray();

        // Get certificate count (Certificate.student_id references User.id)
        $certificatesCount = \App\Models\Certificate::whereIn('student_id', $userIds)
            ->where('academy_id', $parent->academy_id)
            ->count();

        // Get Quran sessions (QuranSession.student_id references User.id)
        $quranSessions = \App\Models\QuranSession::whereIn('student_id', $userIds)
            ->where('academy_id', $parent->academy_id)
            ->get();

        $quranTotal = $quranSessions->count();
        $quranCompleted = $quranSessions->where('status', SessionStatus::COMPLETED->value)->count();
        $quranAttendanceRate = $quranTotal > 0 ? round(($quranCompleted / $quranTotal) * 100) : 0;

        // Get Academic sessions (AcademicSession.student_id references User.id)
        $academicSessions = \App\Models\AcademicSession::whereIn('student_id', $userIds)
            ->where('academy_id', $parent->academy_id)
            ->get();

        $academicTotal = $academicSessions->count();
        $academicCompleted = $academicSessions->where('status', SessionStatus::COMPLETED->value)->count();
        $academicAttendanceRate = $academicTotal > 0 ? round(($academicCompleted / $academicTotal) * 100) : 0;

        // Get active subscription counts (subscription.student_id references User.id)
        $quranActiveSubscriptions = \App\Models\QuranSubscription::whereIn('student_id', $userIds)
            ->where('academy_id', $parent->academy_id)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->count();

        $academicActiveSubscriptions = \App\Models\AcademicSubscription::whereIn('student_id', $userIds)
            ->where('academy_id', $parent->academy_id)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->count();

        // Get course enrollments (CourseSubscription.student_id references User.id)
        $courseEnrollments = \App\Models\CourseSubscription::whereIn('student_id', $userIds)
            ->where('academy_id', $parent->academy_id)
            ->get();

        // Calculate overall stats
        $totalSessions = $quranTotal + $academicTotal;
        $completedSessions = $quranCompleted + $academicCompleted;
        $overallAttendanceRate = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100) : 0;

        // Get homework performance data from AcademicHomeworkSubmission
        $gradedHomework = \App\Models\AcademicHomeworkSubmission::whereIn('student_id', $userIds)
            ->where('academy_id', $parent->academy_id)
            ->graded()
            ->get();

        $homeworkCount = $gradedHomework->count();
        $averageHomeworkScore = $homeworkCount > 0 ? $gradedHomework->avg('score') : 0; // 0-10 scale
        $averageHomeworkPercentage = $homeworkCount > 0 ? $gradedHomework->avg('score_percentage') : 0;

        // Calculate performance by type (only academic submissions tracked currently)
        $academicHomework = $gradedHomework;
        $academicAvgScore = $academicHomework->count() > 0 ? $academicHomework->avg('score') : 0;
        // Quran and Interactive don't have submission models yet
        $quranAvgScore = 0;
        $interactiveAvgScore = 0;

        return [
            'overall' => [
                'total_sessions' => $totalSessions,
                'completed_sessions' => $completedSessions,
                'attendance_rate' => $overallAttendanceRate,
                'certificates_count' => $certificatesCount,
            ],
            'quran' => [
                'total_sessions' => $quranTotal,
                'total' => $quranTotal,
                'completed_sessions' => $quranCompleted,
                'completed' => $quranCompleted,
                'active_subscriptions' => $quranActiveSubscriptions,
                'attendance_rate' => $quranAttendanceRate,
                'memorization_progress' => [
                    'total_pages' => 0,
                    'average_score' => 0,
                ],
            ],
            'academic' => [
                'total_sessions' => $academicTotal,
                'total' => $academicTotal,
                'completed_sessions' => $academicCompleted,
                'completed' => $academicCompleted,
                'active_subscriptions' => $academicActiveSubscriptions,
                'attendance_rate' => $academicAttendanceRate,
                'subjects' => [],
            ],
            'courses' => [
                'total_enrollments' => $courseEnrollments->count(),
                'completed_courses' => $courseEnrollments->where('status', SessionStatus::COMPLETED->value)->count(),
                'average_progress' => $courseEnrollments->avg('progress_percentage') ?? 0,
            ],
            'recent_activity' => [],
            'performance' => [
                'total_graded' => $homeworkCount,
                'average_overall' => round($averageHomeworkScore, 1), // 0-10 scale
                'average_percentage' => round($averageHomeworkPercentage, 1),
                'academic' => [
                    'count' => $academicHomework->count(),
                    'average' => round($academicAvgScore, 1),
                ],
                'quran' => [
                    'count' => 0,
                    'average' => round($quranAvgScore, 1),
                ],
                'interactive' => [
                    'count' => 0,
                    'average' => round($interactiveAvgScore, 1),
                ],
            ],
        ];
    }

    /**
     * Build attendance report for given children
     *
     * Note: Attendance is calculated based on:
     * - Completed sessions = sessions that happened (student present)
     * - Sessions with attendance_status = 'absent' or similar
     * - Cancelled sessions are NOT counted (they didn't happen)
     */
    protected function buildAttendanceReport($parent, array $childIds): array
    {
        // Convert StudentProfile IDs to User IDs (session models reference User.id)
        $userIds = \App\Models\StudentProfile::whereIn('id', $childIds)
            ->pluck('user_id')
            ->toArray();

        if (empty($userIds)) {
            return $this->getEmptyAttendanceReport();
        }

        // Get Quran sessions that happened (completed) or have attendance marked
        $quranSessions = \App\Models\QuranSession::whereIn('student_id', $userIds)
            ->where('academy_id', $parent->academy_id)
            ->where(function ($query) {
                $query->where('status', SessionStatus::COMPLETED->value)
                    ->orWhereNotNull('attendance_status');
            })
            ->get();

        // Get Academic sessions that happened (completed) or have attendance marked
        $academicSessions = \App\Models\AcademicSession::whereIn('student_id', $userIds)
            ->where('academy_id', $parent->academy_id)
            ->where(function ($query) {
                $query->where('status', SessionStatus::COMPLETED->value)
                    ->orWhereNotNull('attendance_status');
            })
            ->get();

        // Helper to get session status value (handles both enum and string)
        $getStatusValue = fn ($status) => $status instanceof \BackedEnum ? $status->value : (string) $status;

        // Calculate Quran attendance stats
        $quranTotal = $quranSessions->count();
        $quranPresent = $quranSessions->filter(function ($session) use ($getStatusValue) {
            // Present if: completed AND (no attendance_status OR attendance_status is attended/late)
            $statusValue = $getStatusValue($session->status);
            if ($statusValue === SessionStatus::COMPLETED->value) {
                $attStatus = strtolower($session->attendance_status ?? AttendanceStatus::ATTENDED->value);

                return in_array($attStatus, [AttendanceStatus::ATTENDED->value, AttendanceStatus::LATE->value, '']) || is_null($session->attendance_status);
            }

            return false;
        })->count();
        $quranAbsent = $quranSessions->filter(function ($session) use ($getStatusValue) {
            $statusValue = $getStatusValue($session->status);
            // Absent if session status is 'absent' OR attendance_status indicates absence
            if ($statusValue === SessionStatus::ABSENT->value) {
                return true;
            }
            $attStatus = strtolower($session->attendance_status ?? '');

            return $attStatus === AttendanceStatus::ABSENT->value;
        })->count();
        $quranLate = $quranSessions->filter(function ($session) {
            $attStatus = strtolower($session->attendance_status ?? '');

            return $attStatus === AttendanceStatus::LATE->value;
        })->count();
        $quranAttendanceRate = $quranTotal > 0 ? round(($quranPresent / $quranTotal) * 100) : 0;

        // Calculate Academic attendance stats
        $academicTotal = $academicSessions->count();
        $academicPresent = $academicSessions->filter(function ($session) use ($getStatusValue) {
            // Present if: completed AND (no attendance_status OR attendance_status is attended/late)
            $statusValue = $getStatusValue($session->status);
            if ($statusValue === SessionStatus::COMPLETED->value) {
                $attStatus = strtolower($session->attendance_status ?? AttendanceStatus::ATTENDED->value);

                return in_array($attStatus, [AttendanceStatus::ATTENDED->value, AttendanceStatus::LATE->value, '']) || is_null($session->attendance_status);
            }

            return false;
        })->count();
        $academicAbsent = $academicSessions->filter(function ($session) use ($getStatusValue) {
            $statusValue = $getStatusValue($session->status);
            // Absent if session status is 'absent' OR attendance_status indicates absence
            if ($statusValue === SessionStatus::ABSENT->value) {
                return true;
            }
            $attStatus = strtolower($session->attendance_status ?? '');

            return $attStatus === AttendanceStatus::ABSENT->value;
        })->count();
        $academicLate = $academicSessions->filter(function ($session) {
            $attStatus = strtolower($session->attendance_status ?? '');

            return $attStatus === AttendanceStatus::LATE->value;
        })->count();
        $academicAttendanceRate = $academicTotal > 0 ? round(($academicPresent / $academicTotal) * 100) : 0;

        // Calculate overall stats
        $totalSessions = $quranTotal + $academicTotal;
        $presentCount = $quranPresent + $academicPresent;
        $absentCount = $quranAbsent + $academicAbsent;
        $lateCount = $quranLate + $academicLate;
        $overallAttendanceRate = $totalSessions > 0 ? round(($presentCount / $totalSessions) * 100) : 0;

        return [
            'overall' => [
                'total_sessions' => $totalSessions,
                'completed_sessions' => $presentCount,
                'attendance_rate' => $overallAttendanceRate,
                'present_count' => $presentCount,
                'absent_count' => $absentCount,
                'late_count' => $lateCount,
            ],
            'quran' => [
                'attendance_rate' => $quranAttendanceRate,
                'present' => $quranPresent,
                'absent' => $quranAbsent,
                'late' => $quranLate,
            ],
            'academic' => [
                'attendance_rate' => $academicAttendanceRate,
                'present' => $academicPresent,
                'absent' => $academicAbsent,
                'late' => $academicLate,
            ],
        ];
    }

    /**
     * Get empty attendance report structure
     */
    protected function getEmptyAttendanceReport(): array
    {
        return [
            'overall' => [
                'total_sessions' => 0,
                'completed_sessions' => 0,
                'attendance_rate' => 0,
                'present_count' => 0,
                'absent_count' => 0,
                'late_count' => 0,
            ],
            'quran' => [
                'attendance_rate' => 0,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
            ],
            'academic' => [
                'attendance_rate' => 0,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
            ],
        ];
    }

    /**
     * Helper: Get student IDs for children based on filter
     */
    protected function getChildIds($children, $selectedChildId): array
    {
        if ($selectedChildId === 'all') {
            return $children->pluck('id')->toArray();
        }

        // Find the specific child
        $child = $children->firstWhere('id', $selectedChildId);
        if ($child) {
            return [$child->id];
        }

        // Fallback to all children if invalid selection
        return $children->pluck('id')->toArray();
    }

    /**
     * Quran Individual Circle Report - detailed report for parent viewing child's circle
     */
    public function quranIndividualReport(Request $request, $subdomain, QuranIndividualCircle $circle): View
    {
        $this->authorize('viewReport', $circle);

        // Use the same report service as student reports
        $reportService = $this->quranReportService;
        $dateRange = $this->getDateRangeFromRequest($request);
        $reportData = $reportService->getIndividualCircleReport($circle, $dateRange);

        return view('reports.quran.circle-report', array_merge(
            $reportData,
            $this->getDateRangeViewData($request),
            [
                'layoutType' => 'parent',
                'circleType' => 'individual',
            ]
        ));
    }

    /**
     * Academic Subscription Report - detailed report for parent viewing child's subscription
     */
    public function academicSubscriptionReport(Request $request, $subdomain, AcademicSubscription $subscription): View
    {
        $user = Auth::user();
        $parent = $user->parentProfile;

        $this->authorize('view', $subscription);

        // Verify parent has access to this child's subscription
        $this->verificationService->verifySubscriptionBelongsToChild($parent, $subscription);

        // Use the same report service as student reports
        $reportService = $this->academicReportService;
        $dateRange = $this->getDateRangeFromRequest($request);
        $reportData = $reportService->getSubscriptionReport($subscription, $dateRange);

        return view('reports.academic.subscription-report-student', array_merge(
            $reportData,
            $this->getDateRangeViewData($request),
            [
                'layoutType' => 'parent',
            ]
        ));
    }

    /**
     * Interactive Course Report - detailed report for parent viewing child's course
     */
    public function interactiveCourseReport(Request $request, $subdomain, InteractiveCourse $course): View
    {
        $user = Auth::user();
        $parent = $user->parentProfile;
        $childUserIds = $this->verificationService->getChildUserIds($parent);

        // Find the child enrolled in this course
        $enrollment = CourseSubscription::where('course_id', $course->id)
            ->whereIn('student_id', $childUserIds)
            ->with('student')
            ->firstOrFail();

        $this->authorize('view', $enrollment);

        // Use the same report service as student reports
        $reportService = $this->interactiveReportService;
        $dateRange = $this->getDateRangeFromRequest($request);
        $reportData = $reportService->getStudentReport($course, $enrollment->student, $dateRange);

        return view('reports.interactive-course.student-report', array_merge(
            $reportData,
            $this->getDateRangeViewData($request),
            [
                'layoutType' => 'parent',
            ]
        ));
    }

    /**
     * Get date range from request
     */
    protected function getDateRangeFromRequest(Request $request): ?array
    {
        $period = $request->get('period', 'all');

        if ($period === 'custom') {
            $start = $request->get('start_date');
            $end = $request->get('end_date');

            if ($start && $end) {
                return [
                    'start' => \Carbon\Carbon::parse($start)->startOfDay(),
                    'end' => \Carbon\Carbon::parse($end)->endOfDay(),
                ];
            }
        }

        if ($period === 'week') {
            return [
                'start' => now()->subWeek()->startOfDay(),
                'end' => now()->endOfDay(),
            ];
        }

        if ($period === 'month') {
            return [
                'start' => now()->subMonth()->startOfDay(),
                'end' => now()->endOfDay(),
            ];
        }

        if ($period === 'quarter') {
            return [
                'start' => now()->subQuarter()->startOfDay(),
                'end' => now()->endOfDay(),
            ];
        }

        return null;
    }

    /**
     * Get date range view data
     */
    protected function getDateRangeViewData(Request $request): array
    {
        return [
            'filterPeriod' => $request->get('period', 'all'),
            'customStartDate' => $request->get('start_date', ''),
            'customEndDate' => $request->get('end_date', ''),
        ];
    }
}
