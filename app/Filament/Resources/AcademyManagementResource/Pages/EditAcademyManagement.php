<?php

namespace App\Filament\Resources\AcademyManagementResource\Pages;

use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\AcademyManagementResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditAcademyManagement extends EditRecord
{
    protected static string $resource = AcademyManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Colors are now stored as enum values (e.g., 'sky', 'emerald'), not hex values
        // No need to add '#' prefix anymore

        $record->update($data);

        return $record;
    }
}
