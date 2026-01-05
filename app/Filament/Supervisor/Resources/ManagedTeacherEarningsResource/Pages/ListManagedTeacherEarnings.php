<?php

namespace App\Filament\Supervisor\Resources\ManagedTeacherEarningsResource\Pages;

use App\Filament\Supervisor\Resources\ManagedTeacherEarningsResource;
use Filament\Resources\Pages\ListRecords;

class ListManagedTeacherEarnings extends ListRecords
{
    protected static string $resource = ManagedTeacherEarningsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Supervisors cannot create earnings
        ];
    }
}
