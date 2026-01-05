<?php

namespace App\Filament\Supervisor\Resources\MonitoredAcademicLessonsResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredAcademicLessonsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMonitoredAcademicLesson extends CreateRecord
{
    protected static string $resource = MonitoredAcademicLessonsResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الدرس بنجاح';
    }
}
