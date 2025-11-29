<?php

namespace App\Filament\Resources\AcademicSubjectResource\Pages;

use App\Filament\Resources\AcademicSubjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAcademicSubject extends CreateRecord
{
    protected static string $resource = AcademicSubjectResource::class;

    protected static ?string $title = 'إضافة مادة أكاديمية جديدة';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إضافة المادة الأكاديمية بنجاح';
    }
}
