<?php

namespace App\Filament\Widgets;

use App\Enums\SessionSubscriptionStatus;
use App\Models\Academy;
use Filament\Widgets\ChartWidget;

class AcademyStatsWidget extends ChartWidget
{
    protected ?string $heading = 'إحصائيات الأكاديميات';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isDiscoverable = false;

    protected function getData(): array
    {
        $activeCount = Academy::where('is_active', true)->where('maintenance_mode', false)->count();
        $inactiveCount = Academy::where('is_active', false)->count();
        $maintenanceCount = Academy::where('maintenance_mode', true)->count();

        $statusLabels = [
            SessionSubscriptionStatus::ACTIVE->value => 'نشطة',
            'inactive' => 'غير نشطة',
            'maintenance' => 'تحت الصيانة',
        ];

        $labels = [];
        $data = [];
        $colors = [];

        // Add data for each status that has academies
        if ($activeCount > 0) {
            $labels[] = $statusLabels['active'];
            $data[] = $activeCount;
            $colors[] = '#10b981';
        }

        if ($inactiveCount > 0) {
            $labels[] = $statusLabels['inactive'];
            $data[] = $inactiveCount;
            $colors[] = '#6b7280';
        }

        if ($maintenanceCount > 0) {
            $labels[] = $statusLabels['maintenance'];
            $data[] = $maintenanceCount;
            $colors[] = '#f59e0b';
        }

        return [
            'datasets' => [
                [
                    'label' => 'عدد الأكاديميات',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
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
                    'bodyFont' => [
                        'family' => 'Tajawal',
                    ],
                    'titleFont' => [
                        'family' => 'Tajawal',
                    ],
                ],
            ],
        ];
    }

    public static function canView(): bool
    {
        return false;
    }
}
