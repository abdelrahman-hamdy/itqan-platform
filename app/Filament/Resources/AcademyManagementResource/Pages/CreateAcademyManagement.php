<?php

namespace App\Filament\Resources\AcademyManagementResource\Pages;

use App\Filament\Resources\AcademyManagementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAcademyManagement extends CreateRecord
{
    protected static string $resource = AcademyManagementResource::class;

    protected function handleRecordCreation(array $data): \App\Models\Academy
    {
        // Colors are now stored as enum values (e.g., 'sky', 'emerald'), not hex values
        // No need to add '#' prefix anymore

        return static::getModel()::create($data);
    }
}
