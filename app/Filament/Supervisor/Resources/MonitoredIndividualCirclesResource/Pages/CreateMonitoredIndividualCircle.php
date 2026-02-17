<?php

namespace App\Filament\Supervisor\Resources\MonitoredIndividualCirclesResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredIndividualCirclesResource;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;

class CreateMonitoredIndividualCircle extends CreateRecord
{
    protected static string $resource = MonitoredIndividualCirclesResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الحلقة الفردية بنجاح';
    }
}
