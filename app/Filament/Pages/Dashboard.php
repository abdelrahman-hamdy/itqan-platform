<?php

namespace App\Filament\Pages;

use App\Services\AcademyContextService;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

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

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\AcademyContextWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        $currentAcademy = AcademyContextService::getCurrentAcademy();

        if ($currentAcademy) {
            return [
                \App\Filament\Widgets\RecentActivitiesWidget::class,
            ];
        }

        return [
            \App\Filament\Widgets\PlatformOverviewWidget::class,
            \App\Filament\Widgets\AcademyStatsWidget::class,
        ];
    }
}
