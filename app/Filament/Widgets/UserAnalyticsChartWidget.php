<?php

namespace App\Filament\Widgets;

use App\Enums\UserType;
use App\Models\User;
use App\Services\AcademyContextService;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class UserAnalyticsChartWidget extends ChartWidget
{
    protected ?string $heading = 'تحليل نمو المستخدمين';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

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
        $days = (int) $this->filter;
        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

        $isGlobalView = AcademyContextService::isGlobalViewMode();
        $currentAcademy = AcademyContextService::getCurrentAcademy();

        $userTypes = [
            UserType::STUDENT->value,
            UserType::QURAN_TEACHER->value,
            UserType::ACADEMIC_TEACHER->value,
            UserType::PARENT->value,
        ];

        $scopeQuery = fn ($q) => (! $isGlobalView && $currentAcademy)
            ? $q->where('academy_id', $currentAcademy->id)
            : $q;

        // Base counts: users created BEFORE the date range (1 query)
        $baseCounts = $scopeQuery(User::query())
            ->select('user_type', DB::raw('COUNT(*) as total'))
            ->where('created_at', '<', $startDate)
            ->whereIn('user_type', $userTypes)
            ->groupBy('user_type')
            ->pluck('total', 'user_type');

        // Daily new signups in a single batch query, keyed by date for O(1) lookups
        $dailyCounts = $scopeQuery(User::query())
            ->select(DB::raw('DATE(created_at) as signup_date'), 'user_type', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('user_type', $userTypes)
            ->groupBy('signup_date', 'user_type')
            ->get()
            ->groupBy('user_type')
            ->map(fn ($records) => $records->keyBy('signup_date'));

        // Build cumulative arrays
        $labels = [];
        $series = array_fill_keys($userTypes, []);
        $running = [];
        foreach ($userTypes as $type) {
            $running[$type] = (int) ($baseCounts[$type] ?? 0);
        }

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $dateStr = $date->format('Y-m-d');

            if ($days <= 7) {
                $labels[] = $date->translatedFormat('D');
            } elseif ($days <= 90) {
                $labels[] = $date->format('d/m');
            } else {
                $labels[] = $date->translatedFormat('M');
            }

            foreach ($userTypes as $type) {
                $dayCount = $dailyCounts->get($type)?->get($dateStr)?->total ?? 0;
                $running[$type] += $dayCount;
                $series[$type][] = $running[$type];
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'الطلاب',
                    'data' => $series[UserType::STUDENT->value],
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'pointBackgroundColor' => '#10B981',
                    'pointBorderColor' => '#10B981',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'معلمو القرآن',
                    'data' => $series[UserType::QURAN_TEACHER->value],
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'pointBackgroundColor' => '#3B82F6',
                    'pointBorderColor' => '#3B82F6',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'المعلمون الأكاديميون',
                    'data' => $series[UserType::ACADEMIC_TEACHER->value],
                    'borderColor' => '#8B5CF6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
                    'pointBackgroundColor' => '#8B5CF6',
                    'pointBorderColor' => '#8B5CF6',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'أولياء الأمور',
                    'data' => $series[UserType::PARENT->value],
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

    public static function canView(): bool
    {
        return AcademyContextService::isSuperAdmin();
    }
}
