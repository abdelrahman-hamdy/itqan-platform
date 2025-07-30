<?php

namespace App\Filament\Resources\AcademyManagementResource\Pages;

use App\Filament\Resources\AcademyManagementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAcademyManagement extends CreateRecord
{
    protected static string $resource = AcademyManagementResource::class;

    protected function handleRecordCreation(array $data): \App\Models\Academy
    {
        // Ensure color fields are properly formatted
        if (isset($data['brand_color']) && !str_starts_with($data['brand_color'], '#')) {
            $data['brand_color'] = '#' . ltrim($data['brand_color'], '#');
        }
        
        // Secondary color field removed
        
        return static::getModel()::create($data);
    }
} 