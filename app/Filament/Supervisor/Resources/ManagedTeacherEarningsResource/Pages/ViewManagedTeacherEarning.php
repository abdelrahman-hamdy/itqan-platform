<?php

namespace App\Filament\Supervisor\Resources\ManagedTeacherEarningsResource\Pages;

use App\Filament\Supervisor\Resources\ManagedTeacherEarningsResource;
use Filament\Resources\Pages\ViewRecord;

class ViewManagedTeacherEarning extends ViewRecord
{
    protected static string $resource = ManagedTeacherEarningsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // View only - no edit action
        ];
    }
}
