<?php

namespace App\Filament\Academy\Pages;

use App\Filament\Academy\Widgets\AcademyStatsWidget;
use App\Filament\Academy\Widgets\RenewalMetricsWidget;
use App\Filament\Academy\Widgets\AcademyMonthlyStatsWidget;
use App\Filament\Academy\Widgets\AcademyUserAnalyticsChartWidget;
use App\Filament\Academy\Widgets\AcademySessionAnalyticsChartWidget;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected string $view = 'filament.pages.academy-dashboard';

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
            AcademyStatsWidget::class,
            RenewalMetricsWidget::class,
            AcademyMonthlyStatsWidget::class,
            AcademyUserAnalyticsChartWidget::class,
            AcademySessionAnalyticsChartWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
