<?php

namespace App\Filament\Supervisor\Pages;

use App\Filament\Supervisor\Widgets\SupervisorQuickActionsWidget;
use App\Filament\Supervisor\Widgets\SupervisorStatsWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-eye';

    protected static ?string $navigationLabel = 'لوحة التحكم';

    protected static ?string $title = 'لوحة التحكم';

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user->supervisorProfile) {
            abort(403, 'غير مصرح لك بالوصول إلى لوحة المشرف');
        }
    }

    public function getColumns(): int|string|array
    {
        return 1;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SupervisorStatsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            SupervisorQuickActionsWidget::class,
        ];
    }
}
