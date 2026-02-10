<?php

namespace App\Filament\Resources\RecordedCourseResource\Pages;

use App\Filament\Resources\RecordedCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * @property \App\Models\RecordedCourse $record
 */
class EditRecordedCourse extends EditRecord
{
    protected static string $resource = RecordedCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function afterSave(): void
    {
        // Update course statistics after saving
        $this->record->updateStats();
    }
}
