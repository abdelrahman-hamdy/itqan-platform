<?php

namespace App\Filament\Supervisor\Resources\MonitoredInteractiveCourseSessionsResource\Pages;

use App\Filament\Pages\BaseCreateRecord as CreateRecord;
use App\Filament\Supervisor\Resources\MonitoredInteractiveCourseSessionsResource;

class CreateMonitoredInteractiveCourseSession extends CreateRecord
{
    protected static string $resource = MonitoredInteractiveCourseSessionsResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الجلسة بنجاح';
    }
}
