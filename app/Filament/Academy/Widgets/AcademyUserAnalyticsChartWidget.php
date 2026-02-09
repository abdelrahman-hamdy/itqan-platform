<?php

namespace App\Filament\Academy\Widgets;

use App\Enums\UserType;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class AcademyUserAnalyticsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'تحليل نمو المستخدمين';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7' => 'آخر 7 أيام',
            '30' => 'آخر 30 يوم',
            '90' => 'آخر 3 أشهر',
            '365' => 'آخر سنة',
        ];
    }

    protected function getData(): array
    {
        $academy = Filament::getTenant();

        if (! $academy) {
            return ['datasets' => [], 'labels' => []];
        }

        $days = (int) $this->filter;
        $labels = [];
        $studentsData = [];
        $quranTeachersData = [];
        $academicTeachersData = [];
        $parentsData = [];

        // Generate data points
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);

            // Format label based on filter
            if ($days <= 7) {
                $labels[] = $date->translatedFormat('D');
            } elseif ($days <= 30) {
                $labels[] = $date->format('d/m');
            } elseif ($days <= 90) {
                $labels[] = $date->format('d/m');
            } else {
                $labels[] = $date->translatedFormat('M');
            }

            // Build query scoped to academy
            $baseQuery = User::where('academy_id', $academy->id);

            // Count users by type up to this date (cumulative)
            $studentsData[] = (clone $baseQuery)->where('user_type', UserType::STUDENT->value)
                ->whereDate('created_at', '<=', $date)->count();

            $quranTeachersData[] = (clone $baseQuery)->where('user_type', UserType::QURAN_TEACHER->value)
                ->whereDate('created_at', '<=', $date)->count();

            $academicTeachersData[] = (clone $baseQuery)->where('user_type', UserType::ACADEMIC_TEACHER->value)
                ->whereDate('created_at', '<=', $date)->count();

            $parentsData[] = (clone $baseQuery)->where('user_type', UserType::PARENT->value)
                ->whereDate('created_at', '<=', $date)->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'الطلاب',
                    'data' => $studentsData,
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'pointBackgroundColor' => '#10B981',
                    'pointBorderColor' => '#10B981',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'معلمو القرآن',
                    'data' => $quranTeachersData,
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'pointBackgroundColor' => '#3B82F6',
                    'pointBorderColor' => '#3B82F6',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'المعلمون الأكاديميون',
                    'data' => $academicTeachersData,
                    'borderColor' => '#8B5CF6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
                    'pointBackgroundColor' => '#8B5CF6',
                    'pointBorderColor' => '#8B5CF6',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'أولياء الأمور',
                    'data' => $parentsData,
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'pointBackgroundColor' => '#F59E0B',
                    'pointBorderColor' => '#F59E0B',
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
                        'precision' => 0,
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
}
