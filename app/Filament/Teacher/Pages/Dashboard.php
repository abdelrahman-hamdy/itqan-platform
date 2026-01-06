<?php

namespace App\Filament\Teacher\Pages;

use App\Filament\Teacher\Widgets\PendingQuranHomeworkWidget;
use App\Filament\Teacher\Widgets\QuranStudentPerformanceChartWidget;
use App\Filament\Teacher\Widgets\QuranTeacherOverviewWidget;
use App\Filament\Teacher\Widgets\UpcomingQuranSessionsWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'لوحة التحكم';

    protected static ?string $title = 'لوحة التحكم';

    public function mount(): void
    {
        $user = Auth::user();

        // Only allow Quran teachers
        if (! $user->isQuranTeacher()) {
            abort(403, 'غير مصرح لك بالوصول إلى لوحة معلم القرآن');
        }
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }

    // Return only the widgets we want - override parent to prevent auto-discovered widgets
    public function getWidgets(): array
    {
        return [
            QuranTeacherOverviewWidget::class,
            UpcomingQuranSessionsWidget::class,
            PendingQuranHomeworkWidget::class,
            QuranStudentPerformanceChartWidget::class,
        ];
    }

    // Empty header - all widgets go through getWidgets()
    protected function getHeaderWidgets(): array
    {
        return [];
    }

    // Empty footer - all widgets go through getWidgets()
    protected function getFooterWidgets(): array
    {
        return [];
    }
}
