<?php

namespace App\Filament\Supervisor\Resources\ManagedTeacherPayoutsResource\Pages;

use App\Filament\Supervisor\Resources\ManagedTeacherPayoutsResource;
use Filament\Resources\Pages\ViewRecord;

class ViewManagedTeacherPayout extends ViewRecord
{
    protected static string $resource = ManagedTeacherPayoutsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // View only - no edit action
        ];
    }
}
