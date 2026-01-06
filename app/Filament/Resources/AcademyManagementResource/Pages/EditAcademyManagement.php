<?php

namespace App\Filament\Resources\AcademyManagementResource\Pages;

use App\Filament\Resources\AcademyManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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
        // Colors are now stored as enum values (e.g., 'sky', 'emerald'), not hex values
        // No need to add '#' prefix anymore

        $record->update($data);

        return $record;
    }
}
