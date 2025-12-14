<?php

namespace App\Filament\Supervisor\Resources\MonitoredCirclesResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredCirclesResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMonitoredCircle extends ViewRecord
{
    protected static string $resource = MonitoredCirclesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Supervisors can view but not edit
        ];
    }
}
