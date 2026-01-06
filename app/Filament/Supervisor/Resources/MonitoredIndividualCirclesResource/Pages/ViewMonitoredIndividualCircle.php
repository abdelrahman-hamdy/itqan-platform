<?php

namespace App\Filament\Supervisor\Resources\MonitoredIndividualCirclesResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredIndividualCirclesResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMonitoredIndividualCircle extends ViewRecord
{
    protected static string $resource = MonitoredIndividualCirclesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Supervisors can view but not edit
        ];
    }
}
