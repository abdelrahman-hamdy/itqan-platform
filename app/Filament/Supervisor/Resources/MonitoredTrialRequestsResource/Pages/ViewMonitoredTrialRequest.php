<?php

namespace App\Filament\Supervisor\Resources\MonitoredTrialRequestsResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredTrialRequestsResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMonitoredTrialRequest extends ViewRecord
{
    protected static string $resource = MonitoredTrialRequestsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }
}
