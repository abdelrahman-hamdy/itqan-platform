<?php

namespace App\Filament\Supervisor\Resources\MonitoredGroupCirclesResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredGroupCirclesResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

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
