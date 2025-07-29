<?php

namespace App\Filament\Resources\QuranTeacherResource\Pages;

use App\Filament\Resources\QuranTeacherResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditQuranTeacher extends EditRecord
{
    protected static string $resource = QuranTeacherResource::class;

    public function getTitle(): string
    {
        return 'تعديل معلم القرآن: ' . $this->record->full_name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('عرض'),
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث بيانات معلم القرآن بنجاح';
    }
}
