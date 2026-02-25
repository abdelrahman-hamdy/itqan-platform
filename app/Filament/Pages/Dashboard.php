<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AcademyContextWidget;
use App\Filament\Widgets\RecentActivitiesWidget;
use App\Filament\Widgets\PlatformOverviewWidget;
use App\Filament\Widgets\AcademyStatsWidget;
use App\Services\AcademyContextService;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected string $view = 'filament.pages.dashboard';

    public function getTitle(): string
    {
        $currentAcademy = AcademyContextService::getCurrentAcademy();

        if ($currentAcademy) {
            return "لوحة تحكم {$currentAcademy->name}";
        }

        return 'لوحة تحكم منصة إتقان';
    }

    public function getSubheading(): string
    {
        $currentAcademy = AcademyContextService::getCurrentAcademy();

        if ($currentAcademy) {
            return "مرحباً بك في لوحة تحكم {$currentAcademy->name}";
        }

        return 'مرحباً بك في لوحة تحكم منصة إتقان - إدارة جميع الأكاديميات';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('help_center')
                ->label(__('help.nav_label'))
                ->icon('heroicon-o-question-mark-circle')
                ->url('/help', shouldOpenInNewTab: true)
                ->color('gray')
                ->outlined(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AcademyContextWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        $currentAcademy = AcademyContextService::getCurrentAcademy();

        if ($currentAcademy) {
            return [
                RecentActivitiesWidget::class,
            ];
        }

        return [
            PlatformOverviewWidget::class,
            AcademyStatsWidget::class,
        ];
    }
}
