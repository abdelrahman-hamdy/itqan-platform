<?php

namespace App\Filament\Widgets;

use App\Services\AcademyContextService;
use Filament\Widgets\Widget;

class AcademyContextWidget extends Widget
{
    protected static string $view = 'filament.widgets.academy-context-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = -10; // Show at the top

    public function getViewData(): array
    {
        $user = auth()->user();
        $isSuperAdmin = AcademyContextService::isSuperAdmin($user);
        $isGlobalView = AcademyContextService::isGlobalViewMode();
        $currentAcademy = AcademyContextService::getCurrentAcademy();
        $hasAcademySelected = AcademyContextService::hasAcademySelected();

        return [
            'is_super_admin' => $isSuperAdmin,
            'is_global_view' => $isGlobalView,
            'current_academy' => $currentAcademy,
            'has_academy_selected' => $hasAcademySelected,
            'user' => $user,
            'available_academies_count' => $isSuperAdmin ? AcademyContextService::getAvailableAcademies()->count() : 0,
        ];
    }

    public static function canView(): bool
    {
        // Hide this widget - no longer needed on dashboard
        return false;
    }
} 