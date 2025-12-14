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

    protected int | string | array $columnSpan = 'full';

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
        $quranSessionsData = [];
        $academicSessionsData = [];
        $interactiveSessionsData = [];

        $isGlobalView = AcademyContextService::isGlobalViewMode();
        $currentAcademy = AcademyContextService::getCurrentAcademy();

        // Generate data points
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);

            // Format label based on filter
            if ($days <= 7) {
                $labels[] = $date->translatedFormat('D');
            } elseif ($days <= 30) {
                $labels[] = $date->format('d/m');
            } else {
                $labels[] = $date->format('d/m');
            }

            // Quran Sessions
            $quranQuery = QuranSession::whereDate('scheduled_at', $date);
            if (!$isGlobalView && $currentAcademy) {
                $quranQuery->where('academy_id', $currentAcademy->id);
            }
            $quranSessionsData[] = $quranQuery->count();

            // Academic Sessions
            $academicQuery = AcademicSession::whereDate('scheduled_at', $date);
            if (!$isGlobalView && $currentAcademy) {
                $academicQuery->where('academy_id', $currentAcademy->id);
            }
            $academicSessionsData[] = $academicQuery->count();

            // Interactive Course Sessions (uses scheduled_at from BaseSession, academy through course)
            $interactiveQuery = InteractiveCourseSession::whereDate('scheduled_at', $date);
            if (!$isGlobalView && $currentAcademy) {
                $interactiveQuery->whereHas('course', function ($q) use ($currentAcademy) {
                    $q->where('academy_id', $currentAcademy->id);
                });
            }
            $interactiveSessionsData[] = $interactiveQuery->count();
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
