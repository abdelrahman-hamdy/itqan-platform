<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\PaymentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\UserType;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourseEnrollment;
use App\Models\InteractiveCourseSession;
use App\Models\Payment;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\User;
use App\Services\AcademyContextService;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SupervisorDashboardController extends BaseSupervisorWebController
{
    public function index($subdomain = null): View
    {
        $user = auth()->user();
        $isAdmin = $this->isAdminUser();
        $academyId = $this->getAcademyId();
        $academy = AcademyContextService::getCurrentAcademy();

        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherProfileIds = $this->getAssignedAcademicTeacherProfileIds();

        // ====================================================================
        // General Stats (mirrors AcademyStatsWidget)
        // ====================================================================
        $generalStats = Cache::remember("supervisor_general_stats_{$academyId}", 60, function () use ($academyId) {
            $totalStudents = User::where('academy_id', $academyId)->where('user_type', UserType::STUDENT->value)->count();
            $totalQuranTeachers = User::where('academy_id', $academyId)->where('user_type', UserType::QURAN_TEACHER->value)->count();
            $totalAcademicTeachers = User::where('academy_id', $academyId)->where('user_type', UserType::ACADEMIC_TEACHER->value)->count();
            $totalParents = User::where('academy_id', $academyId)->where('user_type', UserType::PARENT->value)->count();
            $totalSupervisors = User::where('academy_id', $academyId)->where('user_type', UserType::SUPERVISOR->value)->count();
            $totalUsers = $totalStudents + $totalQuranTeachers + $totalAcademicTeachers + $totalParents + $totalSupervisors;
            $activeUsers = User::where('academy_id', $academyId)
                ->whereIn('user_type', [UserType::STUDENT->value, UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value, UserType::PARENT->value, UserType::SUPERVISOR->value])
                ->where('active_status', true)->count();

            $totalQuranSessions = QuranSession::where('academy_id', $academyId)->count();
            $totalAcademicSessions = AcademicSession::where('academy_id', $academyId)->count();
            $totalInteractiveSessions = InteractiveCourseSession::whereHas('course', fn ($q) => $q->where('academy_id', $academyId))->count();
            $totalSessions = $totalQuranSessions + $totalAcademicSessions + $totalInteractiveSessions;

            $passedSessions = QuranSession::where('academy_id', $academyId)->where('scheduled_at', '<', now())->count()
                + AcademicSession::where('academy_id', $academyId)->where('scheduled_at', '<', now())->count()
                + InteractiveCourseSession::whereHas('course', fn ($q) => $q->where('academy_id', $academyId))->where('scheduled_at', '<', now())->count();

            return compact(
                'totalUsers', 'activeUsers',
                'totalSessions', 'passedSessions',
                'totalStudents', 'totalQuranTeachers', 'totalAcademicTeachers', 'totalParents', 'totalSupervisors'
            );
        });

        $generalStats['inactiveUsers'] = $generalStats['totalUsers'] - $generalStats['activeUsers'];
        $generalStats['scheduledSessions'] = $generalStats['totalSessions'] - $generalStats['passedSessions'];

        // ====================================================================
        // Monthly Stats (mirrors AcademyMonthlyStatsWidget)
        // ====================================================================
        $activeQuranSubs = QuranSubscription::where('academy_id', $academyId)->where('status', SessionSubscriptionStatus::ACTIVE->value)->count();
        $activeAcademicSubs = AcademicSubscription::where('academy_id', $academyId)->where('status', SessionSubscriptionStatus::ACTIVE->value)->count();
        $totalActiveSubs = $activeQuranSubs + $activeAcademicSubs;

        $monthQuranSessions = QuranSession::where('academy_id', $academyId)
            ->whereMonth('scheduled_at', now()->month)->whereYear('scheduled_at', now()->year)->count();
        $monthAcademicSessions = AcademicSession::where('academy_id', $academyId)
            ->whereMonth('scheduled_at', now()->month)->whereYear('scheduled_at', now()->year)->count();
        $monthInteractiveSessions = InteractiveCourseSession::whereHas('course', fn ($q) => $q->where('academy_id', $academyId))
            ->whereMonth('scheduled_at', now()->month)->whereYear('scheduled_at', now()->year)->count();
        $monthSessions = $monthQuranSessions + $monthAcademicSessions + $monthInteractiveSessions;

        $newStudents = User::where('academy_id', $academyId)->where('user_type', UserType::STUDENT->value)
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $newTeachers = User::where('academy_id', $academyId)
            ->whereIn('user_type', [UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value])
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $newParents = User::where('academy_id', $academyId)->where('user_type', UserType::PARENT->value)
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $newUsers = $newStudents + $newTeachers + $newParents;

        $monthlyStats = compact(
            'totalActiveSubs', 'activeQuranSubs', 'activeAcademicSubs',
            'monthSessions', 'monthQuranSessions', 'monthAcademicSessions', 'monthInteractiveSessions',
            'newUsers', 'newStudents', 'newTeachers', 'newParents'
        );

        // ====================================================================
        // Admin: Charts data | Both: Today's sessions
        // ====================================================================
        $chartData = null;
        $todaySessions = collect();

        if ($isAdmin) {
            $chartData = $this->buildChartData($academyId);
        }

        // Today's sessions for all roles
        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();

        if (! empty($quranTeacherIds)) {
            $quranToday = QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                ->whereBetween('scheduled_at', [$today, $tomorrow])
                ->with(['quranTeacher', 'student', 'circle', 'individualCircle'])
                ->orderBy('scheduled_at')
                ->get()
                ->map(fn ($s) => [
                    'type' => $s->session_type === 'individual' ? 'quran_individual' : 'quran_group',
                    'session_type' => 'quran',
                    'id' => $s->id,
                    'title' => $s->circle?->name ?? $s->student?->name ?? __('supervisor.dashboard.session_with', ['name' => '']),
                    'teacher_name' => $s->quranTeacher?->name ?? '',
                    'scheduled_at' => $s->scheduled_at,
                    'status' => $s->status,
                ]);
            $todaySessions = $todaySessions->merge($quranToday);
        }

        if (! empty($academicTeacherProfileIds)) {
            $academicToday = AcademicSession::whereIn('academic_teacher_id', $academicTeacherProfileIds)
                ->whereBetween('scheduled_at', [$today, $tomorrow])
                ->with(['academicTeacher.user', 'student'])
                ->orderBy('scheduled_at')
                ->get()
                ->map(fn ($s) => [
                    'type' => 'academic',
                    'session_type' => 'academic',
                    'id' => $s->id,
                    'title' => $s->student?->name ?? __('supervisor.dashboard.session_with', ['name' => '']),
                    'teacher_name' => $s->academicTeacher?->user?->name ?? '',
                    'scheduled_at' => $s->scheduled_at,
                    'status' => $s->status,
                ]);
            $todaySessions = $todaySessions->merge($academicToday);
        }

        $todaySessions = $todaySessions->sortBy('scheduled_at')->values();

        return view('supervisor.dashboard', compact(
            'user',
            'isAdmin',
            'generalStats',
            'monthlyStats',
            'chartData',
            'todaySessions',
        ));
    }

    /**
     * Build chart datasets for user growth and session activity (admin only).
     * Mirrors AcademyUserAnalyticsChartWidget + AcademySessionAnalyticsChartWidget.
     */
    private function buildChartData(int $academyId): array
    {
        $days = 30;
        $labels = [];
        $students = [];
        $quranTeachers = [];
        $academicTeachers = [];
        $parents = [];
        $quranSessions = [];
        $academicSessions = [];
        $interactiveSessions = [];

        $quranSubEarnings = [];
        $academicSubEarnings = [];
        $interactiveCourseEarnings = [];
        $recordedCourseEarnings = [];

        $completedStatus = PaymentStatus::COMPLETED->value;

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d/m');

            // Cumulative user growth
            $students[] = User::where('academy_id', $academyId)
                ->where('user_type', UserType::STUDENT->value)
                ->whereDate('created_at', '<=', $date)->count();
            $quranTeachers[] = User::where('academy_id', $academyId)
                ->where('user_type', UserType::QURAN_TEACHER->value)
                ->whereDate('created_at', '<=', $date)->count();
            $academicTeachers[] = User::where('academy_id', $academyId)
                ->where('user_type', UserType::ACADEMIC_TEACHER->value)
                ->whereDate('created_at', '<=', $date)->count();
            $parents[] = User::where('academy_id', $academyId)
                ->where('user_type', UserType::PARENT->value)
                ->whereDate('created_at', '<=', $date)->count();

            // Daily session counts
            $quranSessions[] = QuranSession::where('academy_id', $academyId)
                ->whereDate('scheduled_at', $date)->count();
            $academicSessions[] = AcademicSession::where('academy_id', $academyId)
                ->whereDate('scheduled_at', $date)->count();
            $interactiveSessions[] = InteractiveCourseSession::whereHas('course', fn ($q) => $q->where('academy_id', $academyId))
                ->whereDate('scheduled_at', $date)->count();

            // Daily earnings by source
            $basePayment = Payment::where('academy_id', $academyId)
                ->where('status', $completedStatus)
                ->whereDate('created_at', $date);

            $quranSubEarnings[] = (float) (clone $basePayment)
                ->where('payable_type', QuranSubscription::class)->sum('amount');
            $academicSubEarnings[] = (float) (clone $basePayment)
                ->where('payable_type', AcademicSubscription::class)->sum('amount');
            $interactiveCourseEarnings[] = (float) (clone $basePayment)
                ->where('payable_type', InteractiveCourseEnrollment::class)->sum('amount');
            $recordedCourseEarnings[] = (float) (clone $basePayment)
                ->where('payable_type', CourseSubscription::class)->sum('amount');
        }

        return [
            'labels' => $labels,
            'userGrowth' => [
                ['label' => __('supervisor.dashboard.stat_students'), 'data' => $students, 'color' => '#10B981'],
                ['label' => __('supervisor.dashboard.stat_quran_teachers'), 'data' => $quranTeachers, 'color' => '#3B82F6'],
                ['label' => __('supervisor.dashboard.stat_academic_teachers'), 'data' => $academicTeachers, 'color' => '#8B5CF6'],
                ['label' => __('supervisor.dashboard.stat_parents'), 'data' => $parents, 'color' => '#F59E0B'],
            ],
            'sessionActivity' => [
                ['label' => __('supervisor.dashboard.quran_sessions'), 'data' => $quranSessions, 'color' => '#059669'],
                ['label' => __('supervisor.dashboard.academic_sessions'), 'data' => $academicSessions, 'color' => '#2563EB'],
                ['label' => __('supervisor.dashboard.interactive_sessions'), 'data' => $interactiveSessions, 'color' => '#DC2626'],
            ],
            'earningsBreakdown' => [
                ['label' => __('supervisor.dashboard.earnings_quran_subs'), 'data' => $quranSubEarnings, 'color' => '#10B981'],
                ['label' => __('supervisor.dashboard.earnings_academic_subs'), 'data' => $academicSubEarnings, 'color' => '#8B5CF6'],
                ['label' => __('supervisor.dashboard.earnings_interactive'), 'data' => $interactiveCourseEarnings, 'color' => '#F59E0B'],
                ['label' => __('supervisor.dashboard.earnings_recorded'), 'data' => $recordedCourseEarnings, 'color' => '#06B6D4'],
            ],
        ];
    }
}
