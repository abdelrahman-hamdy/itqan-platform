<?php

namespace App\Filament\Widgets;

use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use Filament\Widgets\ChartWidget;

class SessionAnalyticsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'تحليل الجلسات التعليمية';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7' => 'آخر 7 أيام',
            '30' => 'آخر 30 يوم',
            '90' => 'آخر 3 أشهر',
        ];
    }

    protected function getData(): array
    {
        $days = (int) $this->filter;
        $labels = [];

        $isGlobalView = AcademyContextService::isGlobalViewMode();
        $currentAcademy = AcademyContextService::getCurrentAcademy();

        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

        // Batch query: get all counts grouped by date in 3 queries instead of 3*N
        $quranCounts = QuranSession::selectRaw('DATE(scheduled_at) as session_date, COUNT(*) as total')
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->when(! $isGlobalView && $currentAcademy, fn ($q) => $q->where('academy_id', $currentAcademy->id))
            ->groupByRaw('DATE(scheduled_at)')
            ->pluck('total', 'session_date');

        $academicCounts = AcademicSession::selectRaw('DATE(scheduled_at) as session_date, COUNT(*) as total')
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->when(! $isGlobalView && $currentAcademy, fn ($q) => $q->where('academy_id', $currentAcademy->id))
            ->groupByRaw('DATE(scheduled_at)')
            ->pluck('total', 'session_date');

        $interactiveQuery = InteractiveCourseSession::selectRaw('DATE(scheduled_at) as session_date, COUNT(*) as total')
            ->whereBetween('scheduled_at', [$startDate, $endDate]);
        if (! $isGlobalView && $currentAcademy) {
            $interactiveQuery->whereHas('course', fn ($q) => $q->where('academy_id', $currentAcademy->id));
        }
        $interactiveCounts = $interactiveQuery->groupByRaw('DATE(scheduled_at)')
            ->pluck('total', 'session_date');

        // Map results to date labels
        $quranSessionsData = [];
        $academicSessionsData = [];
        $interactiveSessionsData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateKey = $date->format('Y-m-d');

            $labels[] = $days <= 7 ? $date->translatedFormat('D') : $date->format('d/m');

            $quranSessionsData[] = $quranCounts[$dateKey] ?? 0;
            $academicSessionsData[] = $academicCounts[$dateKey] ?? 0;
            $interactiveSessionsData[] = $interactiveCounts[$dateKey] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'جلسات القرآن',
                    'data' => $quranSessionsData,
                    'borderColor' => '#059669',
                    'backgroundColor' => 'rgba(5, 150, 105, 0.2)',
                    'pointBackgroundColor' => '#059669',
                    'pointBorderColor' => '#059669',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'الجلسات الأكاديمية',
                    'data' => $academicSessionsData,
                    'borderColor' => '#2563EB',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.2)',
                    'pointBackgroundColor' => '#2563EB',
                    'pointBorderColor' => '#2563EB',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'الدورات التفاعلية',
                    'data' => $interactiveSessionsData,
                    'borderColor' => '#DC2626',
                    'backgroundColor' => 'rgba(220, 38, 38, 0.2)',
                    'pointBackgroundColor' => '#DC2626',
                    'pointBorderColor' => '#DC2626',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                        'font' => [
                            'family' => 'Tajawal',
                            'size' => 12,
                        ],
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'bodyFont' => [
                        'family' => 'Tajawal',
                    ],
                    'titleFont' => [
                        'family' => 'Tajawal',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'font' => [
                            'family' => 'Tajawal',
                        ],
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)',
                    ],
                    'ticks' => [
                        'stepSize' => 1,
                        'font' => [
                            'family' => 'Tajawal',
                        ],
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }

    public static function canView(): bool
    {
        return AcademyContextService::isSuperAdmin();
    }
}
