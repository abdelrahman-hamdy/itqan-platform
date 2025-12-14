<?php

namespace App\Filament\Supervisor\Resources\MonitoredSessionsResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredSessionsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonitoredSessions extends ListRecords
{
    protected static string $resource = MonitoredSessionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for supervisors
        ];
    }
}
