<?php

namespace App\Filament\Supervisor\Resources\MonitoredAcademicSessionsResource\Pages;

use App\Filament\Pages\BaseCreateRecord as CreateRecord;
use App\Filament\Supervisor\Resources\MonitoredAcademicSessionsResource;

class CreateMonitoredAcademicSession extends CreateRecord
{
    protected static string $resource = MonitoredAcademicSessionsResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الجلسة بنجاح';
    }
}
