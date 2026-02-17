<?php

namespace App\Filament\AcademicTeacher\Widgets;

use App\Enums\HomeworkSubmissionStatus;
use App\Enums\InteractiveCourseStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicHomework;
use App\Models\AcademicHomeworkSubmission;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Models\TeacherEarning;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AcademicTeacherOverviewWidget extends BaseWidget
{
    protected string $view = 'filament.widgets.collapsible-stats-overview-widget';

    // Prevent auto-display on dashboard - Dashboard explicitly adds this widget
    protected static bool $isDiscoverable = false;

    protected function getHeading(): ?string
    {
        return 'نظرة عامة';
    }

    protected ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        if (! $teacherProfile) {
            return [];
        }

        // Today's sessions (both individual and interactive course sessions)
        $todayIndividualSessions = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->whereDate('scheduled_at', today())
            ->count();

        $todayInteractiveSessions = InteractiveCourseSession::whereHas('course', function ($q) use ($teacherProfile) {
            $q->where('assigned_teacher_id', $teacherProfile->id);
        })
            ->whereDate('scheduled_at', today())
            ->count();

        $todaySessions = $todayIndividualSessions + $todayInteractiveSessions;

        // This month's completed sessions
        $monthCompletedIndividual = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->whereMonth('scheduled_at', now()->month)
            ->whereYear('scheduled_at', now()->year)
            ->where('status', SessionStatus::COMPLETED)
            ->count();

        $monthCompletedCourses = InteractiveCourseSession::whereHas('course', function ($q) use ($teacherProfile) {
            $q->where('assigned_teacher_id', $teacherProfile->id);
        })
            ->whereMonth('scheduled_at', now()->month)
            ->whereYear('scheduled_at', now()->year)
            ->where('status', SessionStatus::COMPLETED)
            ->count();

        $monthCompletedSessions = $monthCompletedIndividual + $monthCompletedCourses;

        // Active individual lessons (students)
        $activeIndividualLessons = AcademicIndividualLesson::where('academic_teacher_id', $teacherProfile->id)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->count();

        // Active interactive courses
        $activeCourses = InteractiveCourse::where('assigned_teacher_id', $teacherProfile->id)
            ->whereIn('status', [InteractiveCourseStatus::PUBLISHED->value, InteractiveCourseStatus::ACTIVE->value])
            ->count();

        // Pending homework submissions to review
        // Get homework IDs belonging to this teacher
        $teacherHomeworkIds = AcademicHomework::where('teacher_id', $teacherProfile->user_id)
            ->pluck('id')
            ->toArray();

        $pendingHomework = AcademicHomeworkSubmission::whereIn('academic_homework_id', $teacherHomeworkIds)
            ->where('submission_status', HomeworkSubmissionStatus::SUBMITTED->value)
            ->count();

        // Upcoming sessions (next 7 days)
        $upcomingIndividual = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->whereBetween('scheduled_at', [now(), now()->addDays(7)])
            ->upcoming()
            ->count();

        $upcomingCourses = InteractiveCourseSession::whereHas('course', function ($q) use ($teacherProfile) {
            $q->where('assigned_teacher_id', $teacherProfile->id);
        })
            ->whereBetween('scheduled_at', [now(), now()->addDays(7)])
            ->upcoming()
            ->count();

        $upcomingSessions = $upcomingIndividual + $upcomingCourses;

        // Earnings - All time
        $allTimeEarnings = TeacherEarning::where('teacher_type', AcademicTeacherProfile::class)
            ->where('teacher_id', $teacherProfile->id)
            ->sum('amount');

        // Earnings - This month
        $thisMonthEarnings = TeacherEarning::where('teacher_type', AcademicTeacherProfile::class)
            ->where('teacher_id', $teacherProfile->id)
            ->whereMonth('earning_month', now()->month)
            ->whereYear('earning_month', now()->year)
            ->sum('amount');

        return [
            Stat::make('جلسات اليوم', $todaySessions)
                ->description($todayIndividualSessions.' فردية، '.$todayInteractiveSessions.' دورات')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($todaySessions > 0 ? 'primary' : 'gray')
                ->chart($this->getWeekSessionsChart($teacherProfile)),

            Stat::make('جلسات مكتملة هذا الشهر', $monthCompletedSessions)
                ->description($monthCompletedIndividual.' فردية، '.$monthCompletedCourses.' دورات')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('الدروس الفردية النشطة', $activeIndividualLessons)
                ->description('طلاب مسجلين')
                ->descriptionIcon('heroicon-m-user')
                ->color('info'),

            Stat::make('الدورات التفاعلية', $activeCourses)
                ->description('دورات جارية')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('warning'),

            Stat::make('الجلسات القادمة', $upcomingSessions)
                ->description('خلال الأسبوع القادم')
                ->descriptionIcon('heroicon-m-clock')
                ->color('primary'),

            Stat::make('واجبات تحتاج مراجعة', $pendingHomework)
                ->description($pendingHomework > 0 ? 'بانتظار التقييم' : 'لا توجد واجبات معلقة')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color($pendingHomework > 0 ? 'danger' : 'gray'),

            Stat::make('أرباح هذا الشهر', number_format($thisMonthEarnings, 2).' '.getCurrencySymbol())
                ->description('الشهر الحالي')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($thisMonthEarnings > 0 ? 'success' : 'gray'),

            Stat::make('إجمالي الأرباح', number_format($allTimeEarnings, 2).' '.getCurrencySymbol())
                ->description('كل الأوقات')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),
        ];
    }

    private function getWeekSessionsChart($teacherProfile): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $individualCount = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
                ->whereDate('scheduled_at', $date)
                ->count();

            $interactiveCount = InteractiveCourseSession::whereHas('course', function ($q) use ($teacherProfile) {
                $q->where('assigned_teacher_id', $teacherProfile->id);
            })
                ->whereDate('scheduled_at', $date)
                ->count();

            $data[] = $individualCount + $interactiveCount;
        }

        return $data;
    }
}
