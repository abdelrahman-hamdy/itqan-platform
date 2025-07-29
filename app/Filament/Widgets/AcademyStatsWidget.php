<?php

namespace App\Filament\Widgets;

use App\Models\Academy;
use Filament\Widgets\ChartWidget;

class AcademyStatsWidget extends ChartWidget
{
    protected static ?string $heading = 'إحصائيات الأكاديميات';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $academies = Academy::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statusLabels = [
            'active' => 'نشطة',
            'inactive' => 'غير نشطة',
            'suspended' => 'معلقة',
            'maintenance' => 'تحت الصيانة'
        ];

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($academies as $status => $count) {
            $labels[] = $statusLabels[$status] ?? $status;
            $data[] = $count;
            
            // Color coding for different statuses
            $colors[] = match($status) {
                'active' => '#10b981',
                'inactive' => '#6b7280',
                'suspended' => '#ef4444',
                'maintenance' => '#f59e0b',
                default => '#3b82f6'
            };
        }

        return [
            'datasets' => [
                [
                    'label' => 'عدد الأكاديميات',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 2,
                ]
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
                            'size' => 12
                        ]
                    ]
                ],
                'tooltip' => [
                    'bodyFont' => [
                        'family' => 'Tajawal'
                    ],
                    'titleFont' => [
                        'family' => 'Tajawal'
                    ]
                ]
            ]
        ];
    }
} 