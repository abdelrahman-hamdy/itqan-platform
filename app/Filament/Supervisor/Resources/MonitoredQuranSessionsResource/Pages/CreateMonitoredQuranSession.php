<?php

namespace App\Filament\Supervisor\Resources\MonitoredQuranSessionsResource\Pages;

use App\Filament\Pages\BaseCreateRecord as CreateRecord;
use App\Filament\Supervisor\Resources\MonitoredQuranSessionsResource;

class CreateMonitoredQuranSession extends CreateRecord
{
    protected static string $resource = MonitoredQuranSessionsResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الجلسة بنجاح';
    }
}
