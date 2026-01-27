<?php

namespace App\Filament\Supervisor\Widgets;

use App\Enums\SessionStatus;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SupervisorStatsWidget extends BaseWidget
{
    protected static string $view = 'filament.widgets.collapsible-stats-overview-widget';

    protected static bool $isDiscoverable = false;

    protected static ?int $sort = 1;

    protected function getHeading(): ?string
    {
        return 'إحصائيات المشرف';
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $profile = $user?->supervisorProfile;

        if (! $profile) {
            return [
                Stat::make('غير مصرح', 'لا يوجد ملف مشرف')
                    ->description('يرجى التواصل مع المسؤول')
                    ->color('danger'),
            ];
        }

        $stats = [];

        // Get assigned teacher counts
        $quranTeacherIds = $profile->getAssignedQuranTeacherIds();
        $academicTeacherIds = $profile->getAssignedAcademicTeacherIds();
        $interactiveCourseIds = $profile->getDerivedInteractiveCourseIds();

        // Get academic teacher profile IDs
        $academicProfileIds = [];
        if (! empty($academicTeacherIds)) {
            $academicProfileIds = AcademicTeacherProfile::whereIn('user_id', $academicTeacherIds)
                ->pluck('id')->toArray();
        }

        // Total Teachers Stat
        $totalTeachers = count($quranTeacherIds) + count($academicTeacherIds);
        if ($totalTeachers > 0) {
            $stats[] = Stat::make('المعلمون', $totalTeachers)
                ->description('قرآن: '.count($quranTeacherIds).' | أكاديمي: '.count($academicTeacherIds))
                ->descriptionIcon('heroicon-o-users')
                ->color('primary')
                ->chart($this->getTeacherActivityTrend($quranTeacherIds, $academicProfileIds));
        }

        // Sessions Today with status breakdown
        $todayStats = $this->getTodaySessionsStats($quranTeacherIds, $academicProfileIds, $interactiveCourseIds);
        $stats[] = Stat::make('جلسات اليوم', $todayStats['total'])
            ->description($this->formatSessionsDescription($todayStats))
            ->descriptionIcon('heroicon-o-calendar-days')
            ->color($todayStats['total'] > 0 ? 'success' : 'gray')
            ->chart($this->getSessionsTrend($quranTeacherIds, $academicProfileIds, $interactiveCourseIds));

        // This Week's Sessions
        $weekStats = $this->getWeekSessionsStats($quranTeacherIds, $academicProfileIds, $interactiveCourseIds);
        $completionRate = $weekStats['total'] > 0
            ? round(($weekStats['completed'] / $weekStats['total']) * 100)
            : 0;
        $stats[] = Stat::make('جلسات هذا الأسبوع', $weekStats['total'])
            ->description('مكتملة: '.$weekStats['completed'].' ('.$completionRate.'%)')
            ->descriptionIcon('heroicon-o-chart-bar')
            ->color($completionRate >= 70 ? 'success' : ($completionRate >= 40 ? 'warning' : 'danger'));

        // Active Resources Count
        $resourcesStats = $this->getActiveResourcesStats($quranTeacherIds, $academicProfileIds, $interactiveCourseIds);
        $stats[] = Stat::make('الموارد النشطة', $resourcesStats['total'])
            ->description($resourcesStats['description'])
            ->descriptionIcon('heroicon-o-book-open')
            ->color('info');

        // If no teachers assigned
        if (empty($quranTeacherIds) && empty($academicTeacherIds)) {
            return [
                Stat::make('لا توجد مسؤوليات', '0')
                    ->description('لم يتم تعيين أي معلمين بعد')
                    ->descriptionIcon('heroicon-o-exclamation-triangle')
                    ->color('warning'),
            ];
        }

        return $stats;
    }

    private function getTodaySessionsStats(array $quranTeacherIds, array $academicProfileIds, array $interactiveCourseIds): array
    {
        $quranToday = 0;
        $academicToday = 0;
        $interactiveToday = 0;
        $live = 0;
        $scheduled = 0;
        $completed = 0;

        if (! empty($quranTeacherIds)) {
            $quranToday = QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                ->whereDate('scheduled_at', today())
                ->count();

            $live += QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                ->whereDate('scheduled_at', today())
                ->where('status', SessionStatus::ONGOING)
                ->count();
        }

        if (! empty($academicProfileIds)) {
            $academicToday = AcademicSession::whereIn('academic_teacher_id', $academicProfileIds)
                ->whereDate('scheduled_at', today())
                ->count();

            $live += AcademicSession::whereIn('academic_teacher_id', $academicProfileIds)
                ->whereDate('scheduled_at', today())
                ->where('status', SessionStatus::ONGOING)
                ->count();
        }

        if (! empty($interactiveCourseIds)) {
            $interactiveToday = InteractiveCourseSession::whereIn('course_id', $interactiveCourseIds)
                ->whereDate('scheduled_at', today())
                ->count();

            $live += InteractiveCourseSession::whereIn('course_id', $interactiveCourseIds)
                ->whereDate('scheduled_at', today())
                ->where('status', SessionStatus::ONGOING)
                ->count();
        }

        return [
            'total' => $quranToday + $academicToday + $interactiveToday,
            'quran' => $quranToday,
            'academic' => $academicToday,
            'interactive' => $interactiveToday,
            'live' => $live,
        ];
    }

    private function getWeekSessionsStats(array $quranTeacherIds, array $academicProfileIds, array $interactiveCourseIds): array
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $total = 0;
        $completed = 0;

        if (! empty($quranTeacherIds)) {
            $total += QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                ->whereBetween('scheduled_at', [$startOfWeek, $endOfWeek])
                ->count();

            $completed += QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                ->whereBetween('scheduled_at', [$startOfWeek, $endOfWeek])
                ->where('status', SessionStatus::COMPLETED->value)
                ->count();
        }

        if (! empty($academicProfileIds)) {
            $total += AcademicSession::whereIn('academic_teacher_id', $academicProfileIds)
                ->whereBetween('scheduled_at', [$startOfWeek, $endOfWeek])
                ->count();

            $completed += AcademicSession::whereIn('academic_teacher_id', $academicProfileIds)
                ->whereBetween('scheduled_at', [$startOfWeek, $endOfWeek])
                ->where('status', SessionStatus::COMPLETED->value)
                ->count();
        }

        if (! empty($interactiveCourseIds)) {
            $total += InteractiveCourseSession::whereIn('course_id', $interactiveCourseIds)
                ->whereBetween('scheduled_at', [$startOfWeek, $endOfWeek])
                ->count();

            $completed += InteractiveCourseSession::whereIn('course_id', $interactiveCourseIds)
                ->whereBetween('scheduled_at', [$startOfWeek, $endOfWeek])
                ->where('status', SessionStatus::COMPLETED->value)
                ->count();
        }

        return [
            'total' => $total,
            'completed' => $completed,
        ];
    }

    private function getActiveResourcesStats(array $quranTeacherIds, array $academicProfileIds, array $interactiveCourseIds): array
    {
        $groupCircles = 0;
        $individualCircles = 0;
        $lessons = 0;
        $courses = 0;

        if (! empty($quranTeacherIds)) {
            $groupCircles = QuranCircle::whereIn('quran_teacher_id', $quranTeacherIds)
                ->where('status', true)
                ->count();

            $individualCircles = QuranIndividualCircle::whereIn('quran_teacher_id', $quranTeacherIds)
                ->where('is_active', true)
                ->count();
        }

        if (! empty($academicProfileIds)) {
            $lessons = AcademicIndividualLesson::whereIn('academic_teacher_id', $academicProfileIds)
                ->where('status', 'active')
                ->count();
        }

        if (! empty($interactiveCourseIds)) {
            $courses = InteractiveCourse::whereIn('id', $interactiveCourseIds)
                ->whereIn('status', ['published', 'active'])
                ->count();
        }

        $parts = [];
        if ($groupCircles > 0 || $individualCircles > 0) {
            $parts[] = 'حلقات: '.($groupCircles + $individualCircles);
        }
        if ($lessons > 0) {
            $parts[] = 'دروس: '.$lessons;
        }
        if ($courses > 0) {
            $parts[] = 'دورات: '.$courses;
        }

        return [
            'total' => $groupCircles + $individualCircles + $lessons + $courses,
            'description' => implode(' | ', $parts) ?: 'لا توجد موارد',
        ];
    }

    private function formatSessionsDescription(array $stats): string
    {
        $parts = [];

        if ($stats['live'] > 0) {
            $parts[] = 'مباشرة: '.$stats['live'];
        }
        if ($stats['quran'] > 0) {
            $parts[] = 'قرآن: '.$stats['quran'];
        }
        if ($stats['academic'] > 0) {
            $parts[] = 'أكاديمي: '.$stats['academic'];
        }
        if ($stats['interactive'] > 0) {
            $parts[] = 'دورات: '.$stats['interactive'];
        }

        return implode(' | ', $parts) ?: 'لا توجد جلسات';
    }

    private function getSessionsTrend(array $quranTeacherIds, array $academicProfileIds, array $interactiveCourseIds): array
    {
        $trend = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = 0;

            if (! empty($quranTeacherIds)) {
                $count += QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                    ->whereDate('scheduled_at', $date)
                    ->count();
            }

            if (! empty($academicProfileIds)) {
                $count += AcademicSession::whereIn('academic_teacher_id', $academicProfileIds)
                    ->whereDate('scheduled_at', $date)
                    ->count();
            }

            if (! empty($interactiveCourseIds)) {
                $count += InteractiveCourseSession::whereIn('course_id', $interactiveCourseIds)
                    ->whereDate('scheduled_at', $date)
                    ->count();
            }

            $trend[] = $count;
        }

        return $trend;
    }

    private function getTeacherActivityTrend(array $quranTeacherIds, array $academicProfileIds): array
    {
        // Simple activity trend based on sessions per day
        return $this->getSessionsTrend($quranTeacherIds, $academicProfileIds, []);
    }
}
