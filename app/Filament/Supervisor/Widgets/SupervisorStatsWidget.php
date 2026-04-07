<?php

namespace App\Filament\Supervisor\Widgets;

use App\Enums\InteractiveCourseStatus;
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
use Illuminate\Support\Facades\Cache;

class SupervisorStatsWidget extends BaseWidget
{
    protected string $view = 'filament.widgets.collapsible-stats-overview-widget';

    protected static bool $isDiscoverable = false;

    protected static ?int $sort = 1;

    protected function getHeading(): ?string
    {
        return 'إحصائيات المشرف';
    }

    protected function getColumns(): int|array|null
    {
        return ['default' => 2, 'sm' => 2, 'lg' => 4];
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

        // Cache raw data only — Stat objects contain closures and cannot be serialized
        $data = Cache::remember("supervisor_stats_{$user->id}", 300, function () use ($profile) {
            $quranTeacherIds = $profile->getAssignedQuranTeacherIds();
            $academicTeacherIds = $profile->getAssignedAcademicTeacherIds();
            $interactiveCourseIds = $profile->getDerivedInteractiveCourseIds();

            $academicProfileIds = [];
            if (! empty($academicTeacherIds)) {
                $academicProfileIds = AcademicTeacherProfile::whereIn('user_id', $academicTeacherIds)
                    ->pluck('id')->toArray();
            }

            return [
                'quranCount' => count($quranTeacherIds),
                'academicCount' => count($academicTeacherIds),
                'today' => $this->getTodaySessionsStats($quranTeacherIds, $academicProfileIds, $interactiveCourseIds),
                'week' => $this->getWeekSessionsStats($quranTeacherIds, $academicProfileIds, $interactiveCourseIds),
                'resources' => $this->getActiveResourcesStats($quranTeacherIds, $academicProfileIds, $interactiveCourseIds),
            ];
        });

        // No teachers assigned
        if ($data['quranCount'] === 0 && $data['academicCount'] === 0) {
            return [
                Stat::make('لا توجد مسؤوليات', '0')
                    ->description('لم يتم تعيين أي معلمين بعد')
                    ->descriptionIcon('heroicon-o-exclamation-triangle')
                    ->color('warning'),
            ];
        }

        $stats = [];

        $totalTeachers = $data['quranCount'] + $data['academicCount'];
        if ($totalTeachers > 0) {
            $stats[] = Stat::make('المعلمون', $totalTeachers)
                ->description('قرآن: '.$data['quranCount'].' | أكاديمي: '.$data['academicCount'])
                ->descriptionIcon('heroicon-o-users')
                ->color('primary');
        }

        $todayStats = $data['today'];
        $stats[] = Stat::make('جلسات اليوم', $todayStats['total'])
            ->description($this->formatSessionsDescription($todayStats))
            ->descriptionIcon('heroicon-o-calendar-days')
            ->color($todayStats['total'] > 0 ? 'success' : 'gray');

        $weekStats = $data['week'];
        $completionRate = $weekStats['total'] > 0
            ? round(($weekStats['completed'] / $weekStats['total']) * 100)
            : 0;
        $stats[] = Stat::make('جلسات هذا الأسبوع', $weekStats['total'])
            ->description('مكتملة: '.$weekStats['completed'].' ('.$completionRate.'%)')
            ->descriptionIcon('heroicon-o-chart-bar')
            ->color($completionRate >= 70 ? 'success' : ($completionRate >= 40 ? 'warning' : 'danger'));

        $resourcesStats = $data['resources'];
        $stats[] = Stat::make('الموارد النشطة', $resourcesStats['total'])
            ->description($resourcesStats['description'])
            ->descriptionIcon('heroicon-o-book-open')
            ->color('info');

        return $stats;
    }

    private function getTodaySessionsStats(array $quranTeacherIds, array $academicProfileIds, array $interactiveCourseIds): array
    {
        $quranToday = 0;
        $academicToday = 0;
        $interactiveToday = 0;
        $live = 0;

        if (! empty($quranTeacherIds)) {
            $stats = QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                ->whereDate('scheduled_at', today())
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as ongoing", [SessionStatus::ONGOING->value])
                ->first();
            $quranToday = (int) $stats->total;
            $live += (int) $stats->ongoing;
        }

        if (! empty($academicProfileIds)) {
            $stats = AcademicSession::whereIn('academic_teacher_id', $academicProfileIds)
                ->whereDate('scheduled_at', today())
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as ongoing", [SessionStatus::ONGOING->value])
                ->first();
            $academicToday = (int) $stats->total;
            $live += (int) $stats->ongoing;
        }

        if (! empty($interactiveCourseIds)) {
            $stats = InteractiveCourseSession::whereIn('course_id', $interactiveCourseIds)
                ->whereDate('scheduled_at', today())
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as ongoing", [SessionStatus::ONGOING->value])
                ->first();
            $interactiveToday = (int) $stats->total;
            $live += (int) $stats->ongoing;
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
            $stats = QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                ->whereBetween('scheduled_at', [$startOfWeek, $endOfWeek])
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed", [SessionStatus::COMPLETED->value])
                ->first();
            $total += (int) $stats->total;
            $completed += (int) $stats->completed;
        }

        if (! empty($academicProfileIds)) {
            $stats = AcademicSession::whereIn('academic_teacher_id', $academicProfileIds)
                ->whereBetween('scheduled_at', [$startOfWeek, $endOfWeek])
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed", [SessionStatus::COMPLETED->value])
                ->first();
            $total += (int) $stats->total;
            $completed += (int) $stats->completed;
        }

        if (! empty($interactiveCourseIds)) {
            $stats = InteractiveCourseSession::whereIn('course_id', $interactiveCourseIds)
                ->whereBetween('scheduled_at', [$startOfWeek, $endOfWeek])
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed", [SessionStatus::COMPLETED->value])
                ->first();
            $total += (int) $stats->total;
            $completed += (int) $stats->completed;
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
                ->whereIn('status', [InteractiveCourseStatus::PUBLISHED, InteractiveCourseStatus::ACTIVE])
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

    public static function clearCache(int $userId): void
    {
        Cache::forget("supervisor_stats_{$userId}");
    }
}
