<?php

namespace App\Filament\AcademicTeacher\Pages;

use App\Filament\AcademicTeacher\Widgets\AcademicTeacherOverviewWidget;
use App\Filament\AcademicTeacher\Widgets\PendingHomeworkWidget;
use App\Filament\AcademicTeacher\Widgets\RecentAcademicSessionsWidget;
use App\Filament\AcademicTeacher\Widgets\StudentPerformanceChartWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'لوحة التحكم';

    protected static ?string $title = 'لوحة التحكم';

    public function mount(): void
    {
        $user = Auth::user();

        // Only allow academic teachers
        if (! $user->isAcademicTeacher()) {
            abort(403, 'غير مصرح لك بالوصول إلى لوحة المعلم الأكاديمي');
        }
    }

    public function getColumns(): int|array
    {
        return 2;
    }

    // Return only the widgets we want - override parent to prevent auto-discovered widgets
    public function getWidgets(): array
    {
        return [
            AcademicTeacherOverviewWidget::class,
            RecentAcademicSessionsWidget::class,
            PendingHomeworkWidget::class,
            StudentPerformanceChartWidget::class,
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
