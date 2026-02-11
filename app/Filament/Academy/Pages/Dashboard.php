<?php

namespace App\Filament\Academy\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.academy-dashboard';

    public function getTitle(): string
    {
        $tenant = Filament::getTenant();

        if ($tenant) {
            return "لوحة تحكم {$tenant->name}";
        }

        return 'لوحة تحكم الأكاديمية';
    }

    public function getSubheading(): string
    {
        $tenant = Filament::getTenant();

        if ($tenant) {
            return "مرحباً بك في لوحة تحكم {$tenant->name}";
        }

        return 'مرحباً بك في لوحة تحكم الأكاديمية';
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Academy\Widgets\AcademyStatsWidget::class,
            \App\Filament\Academy\Widgets\RenewalMetricsWidget::class,
            \App\Filament\Academy\Widgets\AcademyMonthlyStatsWidget::class,
            \App\Filament\Academy\Widgets\AcademyUserAnalyticsChartWidget::class,
            \App\Filament\Academy\Widgets\AcademySessionAnalyticsChartWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 1;
    }
}
