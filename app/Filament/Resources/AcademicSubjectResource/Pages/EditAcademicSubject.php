<?php

namespace App\Filament\Resources\AcademicSubjectResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\AcademicSubjectResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditAcademicSubject extends EditRecord
{
    protected static string $resource = AcademicSubjectResource::class;

    protected static ?string $title = 'تعديل المادة الأكاديمية';

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض'),
            DeleteAction::make()
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
