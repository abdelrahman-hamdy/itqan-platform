<?php

namespace App\Filament\Supervisor\Resources\MonitoredGroupCirclesResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredGroupCirclesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMonitoredGroupCircle extends EditRecord
{
    protected static string $resource = MonitoredGroupCirclesResource::class;

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
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ التغييرات بنجاح';
    }
}
