<?php

namespace App\Filament\Teacher\Resources\TeacherEarningsResource\Pages;

use App\Filament\Teacher\Resources\TeacherEarningsResource;
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
            // Could add earnings summary widget here
        ];
    }
}
