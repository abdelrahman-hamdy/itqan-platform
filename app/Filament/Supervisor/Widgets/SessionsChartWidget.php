<?php

namespace App\Filament\Supervisor\Widgets;

use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SessionsChartWidget extends ChartWidget
{
    protected static bool $isDiscoverable = false;

    protected ?string $heading = 'الجلسات خلال الأسبوع';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $user = Auth::user();
        $profile = $user?->supervisorProfile;

        if (! $profile) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        return Cache::remember("supervisor_chart_{$user->id}", 600, function () use ($profile) {
            $quranTeacherIds = $profile->getAssignedQuranTeacherIds();
            $academicTeacherIds = $profile->getAssignedAcademicTeacherIds();
            $interactiveCourseIds = $profile->getDerivedInteractiveCourseIds();

            // Get academic teacher profile IDs
            $academicProfileIds = [];
            if (! empty($academicTeacherIds)) {
                $academicProfileIds = AcademicTeacherProfile::whereIn('user_id', $academicTeacherIds)
                    ->pluck('id')->toArray();
            }

            // Initialize arrays with 0 for all 7 days
            $labels = [];
            $dates = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $labels[] = $date->translatedFormat('D');
                $dates[] = $date->toDateString();
            }

            $quranData = array_fill(0, 7, 0);
            $academicData = array_fill(0, 7, 0);
            $interactiveData = array_fill(0, 7, 0);

            $startDateTime = Carbon::today()->subDays(6)->startOfDay()->toDateTimeString();
            $endDateTime = Carbon::today()->endOfDay()->toDateTimeString();

            // Single query for Quran sessions (use datetime range to allow index usage)
            if (! empty($quranTeacherIds)) {
                $counts = QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                    ->whereBetween('scheduled_at', [$startDateTime, $endDateTime])
                    ->selectRaw('DATE(scheduled_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->pluck('count', 'date');
                foreach ($dates as $i => $date) {
                    $quranData[$i] = (int) ($counts[$date] ?? 0);
                }
            }

            // Single query for Academic sessions
            if (! empty($academicProfileIds)) {
                $counts = AcademicSession::whereIn('academic_teacher_id', $academicProfileIds)
                    ->whereBetween('scheduled_at', [$startDateTime, $endDateTime])
                    ->selectRaw('DATE(scheduled_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->pluck('count', 'date');
                foreach ($dates as $i => $date) {
                    $academicData[$i] = (int) ($counts[$date] ?? 0);
                }
            }

            // Single query for Interactive course sessions
            if (! empty($interactiveCourseIds)) {
                $counts = InteractiveCourseSession::whereIn('course_id', $interactiveCourseIds)
                    ->whereBetween('scheduled_at', [$startDateTime, $endDateTime])
                    ->selectRaw('DATE(scheduled_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->pluck('count', 'date');
                foreach ($dates as $i => $date) {
                    $interactiveData[$i] = (int) ($counts[$date] ?? 0);
                }
            }

            $datasets = [];

            if (! empty($quranTeacherIds)) {
                $datasets[] = [
                    'label' => 'جلسات القرآن',
                    'data' => $quranData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'fill' => true,
                ];
            }

            if (! empty($academicProfileIds)) {
                $datasets[] = [
                    'label' => 'جلسات أكاديمية',
                    'data' => $academicData,
                    'backgroundColor' => 'rgba(234, 179, 8, 0.5)',
                    'borderColor' => 'rgb(234, 179, 8)',
                    'fill' => true,
                ];
            }

            if (! empty($interactiveCourseIds)) {
                $datasets[] = [
                    'label' => 'جلسات الدورات',
                    'data' => $interactiveData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'fill' => true,
                ];
            }

            return [
                'datasets' => $datasets,
                'labels' => $labels,
            ];
        });
    }

    public static function clearCache(int $userId): void
    {
        Cache::forget("supervisor_chart_{$userId}");
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'rtl' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
