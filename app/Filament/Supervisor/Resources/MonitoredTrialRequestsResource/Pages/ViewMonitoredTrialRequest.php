<?php

namespace App\Filament\Supervisor\Resources\MonitoredTrialRequestsResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Supervisor\Resources\MonitoredTrialRequestsResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMonitoredTrialRequest extends ViewRecord
{
    protected static string $resource = MonitoredTrialRequestsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }
}
