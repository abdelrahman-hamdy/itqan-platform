<?php

namespace App\Filament\Teacher\Pages;

use App\Filament\Teacher\Widgets\QuickActionsWidget;
use App\Filament\Teacher\Widgets\QuranTeacherOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'لوحة التحكم';

    public function mount(): void
    {
        $user = Auth::user();

        // Only allow Quran teachers
        if (! $user->isQuranTeacher()) {
            abort(403, 'غير مصرح لك بالوصول إلى لوحة معلم القرآن');
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [
            QuranTeacherOverviewWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            QuickActionsWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 1;
    }
}