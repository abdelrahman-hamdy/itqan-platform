<?php

namespace App\Filament\Academy\Resources\RecordedCourseResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Models\RecordedCourse;
use App\Filament\Academy\Resources\RecordedCourseResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;
use Illuminate\Support\Facades\Auth;

/**
 * @property RecordedCourse $record
 */
class EditRecordedCourse extends EditRecord
{
    protected static string $resource = RecordedCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض الدورة'),
            DeleteAction::make()
                ->label('حذف الدورة'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Set the updated_by to the current user
        $data['updated_by'] = Auth::user()->id;

        // Update total duration in minutes
        $data['total_duration_minutes'] = ($data['duration_hours'] ?? 1) * 60;

        // Set published_at if course is being published for the first time
        if (($data['is_published'] ?? false) && ! $this->record->is_published) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث الدورة بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
