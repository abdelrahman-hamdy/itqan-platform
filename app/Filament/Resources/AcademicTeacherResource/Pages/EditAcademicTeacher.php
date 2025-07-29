<?php

namespace App\Filament\Resources\AcademicTeacherResource\Pages;

use App\Filament\Resources\AcademicTeacherResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademicTeacher extends EditRecord
{
    protected static string $resource = AcademicTeacherResource::class;

    public function getTitle(): string
    {
        $teacherName = $this->record->user->name ?? 'معلم أكاديمي';
        return "تعديل المعلم الأكاديمي: {$teacherName}";
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث بيانات المعلم الأكاديمي بنجاح';
    }
}
