<?php

namespace App\Filament\AcademicTeacher\Pages;

use App\Filament\AcademicTeacher\Widgets\AcademicQuickActionsWidget;
use App\Filament\AcademicTeacher\Widgets\AcademicTeacherOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

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

    public function getColumns(): int|string|array
    {
        return 1;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AcademicTeacherOverviewWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            AcademicQuickActionsWidget::class,
        ];
    }
}
