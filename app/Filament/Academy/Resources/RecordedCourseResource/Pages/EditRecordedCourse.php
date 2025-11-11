<?php

namespace App\Filament\Academy\Resources\RecordedCourseResource\Pages;

use App\Filament\Academy\Resources\RecordedCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditRecordedCourse extends EditRecord
{
    protected static string $resource = RecordedCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('عرض الدورة'),
            Actions\DeleteAction::make()
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
        if (($data['is_published'] ?? false) && !$this->record->is_published) {
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