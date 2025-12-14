<?php

namespace App\Filament\AcademicTeacher\Widgets;

use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSession;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AcademicTeacherOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

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

        // This week's sessions
        $weekIndividualSessions = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $weekInteractiveSessions = InteractiveCourseSession::whereHas('course', function ($q) use ($teacherProfile) {
            $q->where('assigned_teacher_id', $teacherProfile->id);
        })
            ->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $weekSessions = $weekIndividualSessions + $weekInteractiveSessions;

        // Active individual lessons
        $activeIndividualLessons = AcademicIndividualLesson::where('academic_teacher_id', $teacherProfile->id)
            ->where('status', 'active')
            ->count();

        // Active interactive courses
        $activeCourses = InteractiveCourse::where('assigned_teacher_id', $teacherProfile->id)
            ->whereIn('status', ['published', 'in_progress'])
            ->count();

        return [
            Stat::make('جلسات اليوم', $todaySessions)
                ->description($todayIndividualSessions.' فردية، '.$todayInteractiveSessions.' دورات')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($todaySessions > 0 ? 'primary' : 'gray')
                ->chart($this->getWeekSessionsChart($teacherProfile)),

            Stat::make('جلسات الأسبوع', $weekSessions)
                ->description('هذا الأسبوع')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('الدروس الفردية النشطة', $activeIndividualLessons)
                ->description('طلاب نشطين')
                ->descriptionIcon('heroicon-m-user')
                ->color('success'),

            Stat::make('الدورات التفاعلية', $activeCourses)
                ->description('دورات جارية')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),
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
