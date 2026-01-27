<?php

namespace App\Filament\Teacher\Widgets;

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\QuranTrialRequest;
use App\Models\TeacherEarning;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class QuranTeacherOverviewWidget extends BaseWidget
{
    protected static string $view = 'filament.widgets.collapsible-stats-overview-widget';

    // Prevent auto-discovery - Dashboard explicitly adds this widget
    protected static bool $isDiscoverable = false;

    protected static ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    protected function getHeading(): ?string
    {
        return 'نظرة عامة';
    }

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    public function getStats(): array
    {
        $user = Auth::user();

        if (! $user->isQuranTeacher()) {
            return [];
        }

        $teacher = $user->quranTeacherProfile;

        if (! $teacher) {
            return [];
        }

        // Today's sessions
        $todaySessions = QuranSession::where('quran_teacher_id', $teacher->id)
            ->whereDate('scheduled_at', today())
            ->count();

        // Today's completed sessions
        $todayCompleted = QuranSession::where('quran_teacher_id', $teacher->id)
            ->whereDate('scheduled_at', today())
            ->where('status', SessionStatus::COMPLETED)
            ->count();

        // This month's completed sessions
        $monthCompletedSessions = QuranSession::where('quran_teacher_id', $teacher->id)
            ->whereMonth('scheduled_at', now()->month)
            ->whereYear('scheduled_at', now()->year)
            ->where('status', SessionStatus::COMPLETED)
            ->count();

        // Active circles count
        $activeGroupCircles = QuranCircle::where('quran_teacher_id', $teacher->id)
            ->where('status', true)
            ->count();

        $activeIndividualCircles = QuranIndividualCircle::where('quran_teacher_id', $teacher->id)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->count();

        // Total active students (from circles)
        $groupCircleStudents = QuranCircle::where('quran_teacher_id', $teacher->id)
            ->where('status', true)
            ->sum('enrolled_students');

        $individualCircleStudents = $activeIndividualCircles;

        $totalActiveStudents = $groupCircleStudents + $individualCircleStudents;

        // Pending trial requests
        $pendingTrials = QuranTrialRequest::where('teacher_id', $teacher->id)
            ->where('status', SessionSubscriptionStatus::PENDING->value)
            ->count();

        // Upcoming sessions (next 7 days)
        $upcomingSessions = QuranSession::where('quran_teacher_id', $teacher->id)
            ->whereBetween('scheduled_at', [now(), now()->addDays(7)])
            ->whereIn('status', [SessionStatus::SCHEDULED, SessionStatus::READY])
            ->count();

        // Earnings - All time
        $allTimeEarnings = TeacherEarning::where('teacher_type', QuranTeacherProfile::class)
            ->where('teacher_id', $teacher->id)
            ->sum('amount');

        // Earnings - This month
        $thisMonthEarnings = TeacherEarning::where('teacher_type', QuranTeacherProfile::class)
            ->where('teacher_id', $teacher->id)
            ->whereMonth('earning_month', now()->month)
            ->whereYear('earning_month', now()->year)
            ->sum('amount');

        return [
            Stat::make('جلسات اليوم', $todaySessions)
                ->description($todayCompleted > 0 ? "{$todayCompleted} مكتملة" : 'لا جلسات مكتملة بعد')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($todaySessions > 0 ? 'primary' : 'gray')
                ->chart($this->getWeekSessionsChart()),

            Stat::make('جلسات مكتملة هذا الشهر', $monthCompletedSessions)
                ->description('الشهر الحالي')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('الطلاب النشطين', $totalActiveStudents)
                ->description($individualCircleStudents.' فردي، '.$groupCircleStudents.' جماعي')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('حلقات فردية نشطة', $activeIndividualCircles)
                ->description('حلقات فردية')
                ->descriptionIcon('heroicon-m-user')
                ->color($activeIndividualCircles > 0 ? 'success' : 'gray'),

            Stat::make('حلقات جماعية نشطة', $activeGroupCircles)
                ->description($groupCircleStudents.' طالب')
                ->descriptionIcon('heroicon-m-user-group')
                ->color($activeGroupCircles > 0 ? 'success' : 'gray'),

            Stat::make('طلبات تجريبية', $pendingTrials)
                ->description($pendingTrials > 0 ? 'في انتظار الرد' : 'لا طلبات معلقة')
                ->descriptionIcon($pendingTrials > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($pendingTrials > 0 ? 'warning' : 'success'),

            Stat::make('الجلسات القادمة', $upcomingSessions)
                ->description('خلال الأسبوع القادم')
                ->descriptionIcon('heroicon-m-clock')
                ->color('primary'),

            Stat::make('أرباح هذا الشهر', number_format($thisMonthEarnings, 2).' ر.س')
                ->description('الشهر الحالي')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($thisMonthEarnings > 0 ? 'success' : 'gray'),

            Stat::make('إجمالي الأرباح', number_format($allTimeEarnings, 2).' ر.س')
                ->description('كل الأوقات')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),
        ];
    }

    private function getWeekSessionsChart(): array
    {
        $user = Auth::user();
        if (! $user->isQuranTeacher() || ! $user->quranTeacherProfile) {
            return [];
        }

        $teacher = $user->quranTeacherProfile;

        // Get sessions for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = QuranSession::where('quran_teacher_id', $teacher->id)
                ->whereDate('scheduled_at', $date)
                ->count();
            $data[] = $count;
        }

        return $data;
    }
}
