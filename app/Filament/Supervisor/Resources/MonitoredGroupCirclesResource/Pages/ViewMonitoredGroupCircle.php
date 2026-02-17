<?php

namespace App\Filament\Supervisor\Resources\MonitoredGroupCirclesResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredGroupCirclesResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewMonitoredGroupCircle extends ViewRecord
{
    protected static string $resource = MonitoredGroupCirclesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Supervisors can view but not edit
        ];
    }
}
