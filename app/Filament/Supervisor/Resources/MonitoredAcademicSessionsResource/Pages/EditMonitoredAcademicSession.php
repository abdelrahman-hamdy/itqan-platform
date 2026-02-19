<?php

namespace App\Filament\Supervisor\Resources\MonitoredAcademicSessionsResource\Pages;

use App\Filament\Pages\BaseEditRecord as EditRecord;
use App\Filament\Supervisor\Resources\MonitoredAcademicSessionsResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;

class EditMonitoredAcademicSession extends EditRecord
{
    protected static string $resource = MonitoredAcademicSessionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('عرض'),
            DeleteAction::make()->label('حذف'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ التغييرات بنجاح';
    }
}
