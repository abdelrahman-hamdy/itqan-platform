<?php

namespace App\Filament\Supervisor\Resources\MonitoredQuranSessionsResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredQuranSessionsResource;
use Filament\Resources\Pages\ListRecords;

class ListMonitoredQuranSessions extends ListRecords
{
    protected static string $resource = MonitoredQuranSessionsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
