<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\PaymentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\UserType;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
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
        $academy = AcademyContextService::getCurrentAcademy();
        $academyId = $academy?->id;

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

            $totalIncome = Payment::where('academy_id', $academyId)->where('status', PaymentStatus::COMPLETED->value)->sum('amount');

            $totalQuranSessions = QuranSession::where('academy_id', $academyId)->count();
            $totalAcademicSessions = AcademicSession::where('academy_id', $academyId)->count();
            $totalInteractiveSessions = InteractiveCourseSession::whereHas('course', fn ($q) => $q->where('academy_id', $academyId))->count();
            $totalSessions = $totalQuranSessions + $totalAcademicSessions + $totalInteractiveSessions;

            $passedSessions = QuranSession::where('academy_id', $academyId)->where('scheduled_at', '<', now())->count()
                + AcademicSession::where('academy_id', $academyId)->where('scheduled_at', '<', now())->count()
                + InteractiveCourseSession::whereHas('course', fn ($q) => $q->where('academy_id', $academyId))->where('scheduled_at', '<', now())->count();

            return compact(
                'totalUsers', 'activeUsers',
                'totalIncome',
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

        $thisMonthRevenue = Payment::where('academy_id', $academyId)->where('status', PaymentStatus::COMPLETED->value)
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('amount');
        $lastMonthRevenue = Payment::where('academy_id', $academyId)->where('status', PaymentStatus::COMPLETED->value)
            ->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->sum('amount');
        $revenueGrowth = $lastMonthRevenue > 0
            ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

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
            'thisMonthRevenue', 'revenueGrowth',
            'newUsers', 'newStudents', 'newTeachers', 'newParents'
        );

        // ====================================================================
        // Upcoming sessions (next 5)
        // ====================================================================
        $upcomingSessions = collect();

        if (! empty($quranTeacherIds)) {
            $quranUpcoming = QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                ->where('scheduled_at', '>', now())
                ->with(['quranTeacher', 'student', 'circle', 'individualCircle'])
                ->orderBy('scheduled_at')
                ->limit(5)
                ->get()
                ->map(fn ($s) => [
                    'type' => 'quran',
                    'title' => $s->circle?->name ?? $s->student?->name ?? __('supervisor.dashboard.session_with', ['name' => '']),
                    'teacher_name' => $s->quranTeacher?->name ?? '',
                    'scheduled_at' => $s->scheduled_at,
                    'status' => $s->status,
                ]);
            $upcomingSessions = $upcomingSessions->merge($quranUpcoming);
        }

        if (! empty($academicTeacherProfileIds)) {
            $academicUpcoming = AcademicSession::whereIn('academic_teacher_id', $academicTeacherProfileIds)
                ->where('scheduled_at', '>', now())
                ->with(['academicTeacher.user', 'student'])
                ->orderBy('scheduled_at')
                ->limit(5)
                ->get()
                ->map(fn ($s) => [
                    'type' => 'academic',
                    'title' => $s->student?->name ?? __('supervisor.dashboard.session_with', ['name' => '']),
                    'teacher_name' => $s->academicTeacher?->user?->name ?? '',
                    'scheduled_at' => $s->scheduled_at,
                    'status' => $s->status,
                ]);
            $upcomingSessions = $upcomingSessions->merge($academicUpcoming);
        }

        $upcomingSessions = $upcomingSessions->sortBy('scheduled_at')->take(5);

        return view('supervisor.dashboard', compact(
            'user',
            'isAdmin',
            'generalStats',
            'monthlyStats',
            'upcomingSessions',
        ));
    }
}
