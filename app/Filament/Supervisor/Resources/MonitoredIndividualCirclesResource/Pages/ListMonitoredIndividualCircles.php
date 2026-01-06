<?php

namespace App\Filament\Supervisor\Resources\MonitoredIndividualCirclesResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredIndividualCirclesResource;
use Filament\Resources\Pages\ListRecords;

class ListMonitoredIndividualCircles extends ListRecords
{
    protected static string $resource = MonitoredIndividualCirclesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for supervisors
        ];
    }
}
