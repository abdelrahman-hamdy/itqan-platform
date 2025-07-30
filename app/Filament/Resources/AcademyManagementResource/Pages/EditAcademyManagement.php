<?php

namespace App\Filament\Resources\AcademyManagementResource\Pages;

use App\Filament\Resources\AcademyManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditAcademyManagement extends EditRecord
{
    protected static string $resource = AcademyManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Ensure color fields are properly formatted
        if (isset($data['brand_color']) && !str_starts_with($data['brand_color'], '#')) {
            $data['brand_color'] = '#' . ltrim($data['brand_color'], '#');
        }
        
        // Secondary color field removed
        
        $record->update($data);
        
        return $record;
    }
} 