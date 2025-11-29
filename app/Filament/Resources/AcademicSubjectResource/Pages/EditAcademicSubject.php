<?php

namespace App\Filament\Resources\AcademicSubjectResource\Pages;

use App\Filament\Resources\AcademicSubjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademicSubject extends EditRecord
{
    protected static string $resource = AcademicSubjectResource::class;

    protected static ?string $title = 'تعديل المادة الأكاديمية';

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('عرض'),
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث المادة الأكاديمية بنجاح';
    }
}
