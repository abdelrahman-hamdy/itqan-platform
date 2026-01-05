<?php

namespace App\Filament\Supervisor\Resources\MonitoredInteractiveCoursesResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredInteractiveCoursesResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMonitoredInteractiveCourse extends CreateRecord
{
    protected static string $resource = MonitoredInteractiveCoursesResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الدورة التفاعلية بنجاح';
    }
}
