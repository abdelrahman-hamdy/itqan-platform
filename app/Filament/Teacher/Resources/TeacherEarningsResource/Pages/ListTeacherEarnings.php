<?php

namespace App\Filament\Teacher\Resources\TeacherEarningsResource\Pages;

use App\Filament\Teacher\Resources\TeacherEarningsResource;
use App\Filament\Teacher\Resources\TeacherEarningsResource\Widgets\EarningsStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListTeacherEarnings extends ListRecords
{
    protected static string $resource = TeacherEarningsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - earnings are calculated by the system
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EarningsStatsWidget::class,
        ];
    }

    /**
     * Make widgets full width and appear above the table.
     */
    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
