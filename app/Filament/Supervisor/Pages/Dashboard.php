<?php

namespace App\Filament\Supervisor\Pages;

use App\Filament\Supervisor\Widgets\ConversationStatsWidget;
use App\Filament\Supervisor\Widgets\SessionsChartWidget;
use App\Filament\Supervisor\Widgets\SupervisorInboxWidget;
use App\Filament\Supervisor\Widgets\SupervisorStatsWidget;
use App\Filament\Supervisor\Widgets\TodaySessionsWidget;
use App\Filament\Supervisor\Widgets\TrialAnalyticsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-eye';

    protected static ?string $navigationLabel = 'لوحة التحكم';

    protected static ?string $title = 'لوحة التحكم';

    public function mount(): void
    {
        // Authorization is handled by VerifySupervisorRole middleware
        // No additional check needed here - supervisors without profiles
        // should still access the dashboard
    }

    public function getColumns(): int|string|array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 2,
            'xl' => 2,
        ];
    }

    public function getWidgets(): array
    {
        return [
            SupervisorInboxWidget::class,
            ConversationStatsWidget::class,
            SupervisorStatsWidget::class,
            SessionsChartWidget::class,
            TodaySessionsWidget::class,
            TrialAnalyticsWidget::class,
        ];
    }
}
