<?php

namespace App\Filament\Supervisor\Resources\MonitoredTrialRequestsResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredTrialRequestsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMonitoredTrialRequest extends EditRecord
{
    protected static string $resource = MonitoredTrialRequestsResource::class;

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
}
