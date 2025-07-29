<?php

namespace App\Filament\Resources\AcademicSettingsResource\Pages;

use App\Filament\Resources\AcademicSettingsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademicSettings extends EditRecord
{
    protected static string $resource = AcademicSettingsResource::class;

    public function getTitle(): string
    {
        return 'تعديل الإعدادات الأكاديمية';
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
        $data['updated_by'] = auth()->id();
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث الإعدادات الأكاديمية بنجاح';
    }
}
