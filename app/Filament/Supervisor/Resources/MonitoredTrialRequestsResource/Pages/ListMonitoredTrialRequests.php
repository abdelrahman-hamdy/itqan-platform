<?php

namespace App\Filament\Supervisor\Resources\MonitoredTrialRequestsResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredTrialRequestsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonitoredTrialRequests extends ListRecords
{
    protected static string $resource = MonitoredTrialRequestsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
