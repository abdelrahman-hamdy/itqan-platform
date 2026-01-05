<?php

namespace App\Filament\Supervisor\Resources\ManagedTeachersResource\Pages;

use App\Filament\Supervisor\Resources\ManagedTeachersResource;
use Filament\Resources\Pages\ListRecords;

class ListManagedTeachers extends ListRecords
{
    protected static string $resource = ManagedTeachersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Supervisors cannot create teachers
        ];
    }
}
