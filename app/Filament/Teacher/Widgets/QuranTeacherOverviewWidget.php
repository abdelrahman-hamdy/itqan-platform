<?php

namespace App\Filament\Teacher\Widgets;

use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranTrialRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class QuranTeacherOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

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
            ->where('status', 'completed')
            ->count();

        // This week's sessions
        $weekSessions = QuranSession::where('quran_teacher_id', $teacher->id)
            ->whereBetween('scheduled_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])
            ->count();

        // Total active students (from circles)
        $groupCircleStudents = QuranCircle::where('quran_teacher_id', $teacher->id)
            ->where('status', true)
            ->sum('enrolled_students');

        $individualCircleStudents = QuranIndividualCircle::where('quran_teacher_id', $teacher->id)
            ->where('status', 'active')
            ->count();

        $totalActiveStudents = $groupCircleStudents + $individualCircleStudents;

        // Pending trial requests
        $pendingTrials = QuranTrialRequest::where('teacher_id', $teacher->id)
            ->where('status', 'pending')
            ->count();

        return [
            Stat::make('جلسات اليوم', $todaySessions)
                ->description($todayCompleted > 0 ? "{$todayCompleted} مكتملة" : 'لا جلسات مكتملة بعد')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($todaySessions > 0 ? 'primary' : 'gray')
                ->chart($this->getWeekSessionsChart()),

            Stat::make('جلسات الأسبوع', $weekSessions)
                ->description('هذا الأسبوع')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('الطلاب النشطين', $totalActiveStudents)
                ->description($individualCircleStudents.' فردي، '.$groupCircleStudents.' جماعي')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('طلبات تجريبية', $pendingTrials)
                ->description($pendingTrials > 0 ? 'في انتظار الرد' : 'لا طلبات معلقة')
                ->descriptionIcon($pendingTrials > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($pendingTrials > 0 ? 'warning' : 'success'),
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
