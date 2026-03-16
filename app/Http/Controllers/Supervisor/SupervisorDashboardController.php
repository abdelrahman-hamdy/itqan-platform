<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\PaymentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourseEnrollment;
use App\Models\Payment;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use Illuminate\View\View;

class SupervisorDashboardController extends BaseSupervisorWebController
{
    public function index($subdomain = null): View
    {
        $user = auth()->user();
        $isAdmin = $this->isAdminUser();

        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherProfileIds = $this->getAssignedAcademicTeacherProfileIds();

        // ====================================================================
        // Row 1 — Active Programs
        // ====================================================================
        $activeQuranSubscriptions = QuranSubscription::where('status', SessionSubscriptionStatus::ACTIVE)->count();
        $activeAcademicSubscriptions = AcademicSubscription::where('status', SessionSubscriptionStatus::ACTIVE)->count();
        $interactiveCourseEnrollments = InteractiveCourseEnrollment::count();
        $recordedCourseEnrollments = CourseSubscription::count();

        // ====================================================================
        // Row 2 — Sessions & Performance
        // ====================================================================
        $today = now()->startOfDay();
        $endOfDay = now()->endOfDay();
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $sessionsToday = 0;
        $sessionsThisWeek = 0;
        $completedThisMonth = 0;
        $totalThisMonth = 0;

        if (! empty($quranTeacherIds)) {
            $sessionsToday += QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                ->whereBetween('scheduled_at', [$today, $endOfDay])->count();
            $sessionsThisWeek += QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                ->whereBetween('scheduled_at', [$startOfWeek, $endOfWeek])->count();
            $completedThisMonth += QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                ->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])
                ->where('status', 'completed')->count();
            $totalThisMonth += QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                ->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])->count();
        }

        if (! empty($academicTeacherProfileIds)) {
            $sessionsToday += AcademicSession::whereIn('academic_teacher_id', $academicTeacherProfileIds)
                ->whereBetween('scheduled_at', [$today, $endOfDay])->count();
            $sessionsThisWeek += AcademicSession::whereIn('academic_teacher_id', $academicTeacherProfileIds)
                ->whereBetween('scheduled_at', [$startOfWeek, $endOfWeek])->count();
            $completedThisMonth += AcademicSession::whereIn('academic_teacher_id', $academicTeacherProfileIds)
                ->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])
                ->where('status', 'completed')->count();
            $totalThisMonth += AcademicSession::whereIn('academic_teacher_id', $academicTeacherProfileIds)
                ->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])->count();
        }

        $completionRate = $totalThisMonth > 0
            ? round(($completedThisMonth / $totalThisMonth) * 100, 1)
            : 0;

        $revenueThisMonth = Payment::where('status', PaymentStatus::COMPLETED)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

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
            'activeQuranSubscriptions',
            'activeAcademicSubscriptions',
            'interactiveCourseEnrollments',
            'recordedCourseEnrollments',
            'sessionsToday',
            'sessionsThisWeek',
            'completedThisMonth',
            'completionRate',
            'revenueThisMonth',
            'upcomingSessions',
        ));
    }
}
