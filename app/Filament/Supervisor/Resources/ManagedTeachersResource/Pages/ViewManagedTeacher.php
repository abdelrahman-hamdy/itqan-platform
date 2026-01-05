<?php

namespace App\Filament\Supervisor\Resources\ManagedTeachersResource\Pages;

use App\Filament\Supervisor\Resources\ManagedTeachersResource;
use Filament\Resources\Pages\ViewRecord;

class ViewManagedTeacher extends ViewRecord
{
    protected static string $resource = ManagedTeachersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // View only - no edit action
        ];
    }
}
