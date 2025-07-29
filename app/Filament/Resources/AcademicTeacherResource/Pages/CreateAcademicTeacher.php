<?php

namespace App\Filament\Resources\AcademicTeacherResource\Pages;

use App\Filament\Resources\AcademicTeacherResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAcademicTeacher extends CreateRecord
{
    protected static string $resource = AcademicTeacherResource::class;

    public function getTitle(): string
    {
        return 'إضافة معلم أكاديمي جديد';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Automatically set academy_id based on current user's academy
        $data['academy_id'] = auth()->user()->academy_id ?? 1;
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إضافة المعلم الأكاديمي بنجاح';
    }
}
