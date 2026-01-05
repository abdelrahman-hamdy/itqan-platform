<?php

namespace App\Filament\Supervisor\Resources\MonitoredGroupCirclesResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredGroupCirclesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonitoredGroupCircles extends ListRecords
{
    protected static string $resource = MonitoredGroupCirclesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for supervisors
        ];
    }
}
