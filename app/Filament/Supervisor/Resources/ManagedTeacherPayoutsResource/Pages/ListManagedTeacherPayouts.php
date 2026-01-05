<?php

namespace App\Filament\Supervisor\Resources\ManagedTeacherPayoutsResource\Pages;

use App\Filament\Supervisor\Resources\ManagedTeacherPayoutsResource;
use Filament\Resources\Pages\ListRecords;

class ListManagedTeacherPayouts extends ListRecords
{
    protected static string $resource = ManagedTeacherPayoutsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Supervisors cannot create payouts
        ];
    }
}
