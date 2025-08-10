<?php

namespace App\Filament\Teacher\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets;
use App\Filament\Teacher\Widgets\QuranTeacherOverviewWidget;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static string $view = 'filament.teacher.pages.dashboard';
    
    protected static ?string $title = 'لوحة التحكم';
    
    protected function getHeaderWidgets(): array
    {
        return [
            QuranTeacherOverviewWidget::class,
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Teacher\Widgets\TeacherAnalyticsWidget::class,
            \App\Filament\Teacher\Widgets\RecentSessionsWidget::class,
        ];
    }
    
    public function getColumns(): int | string | array
    {
        return 2;
    }
}