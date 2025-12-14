<?php

namespace App\Filament\Supervisor\Resources\MonitoredSessionsResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredSessionsResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMonitoredSession extends ViewRecord
{
    protected static string $resource = MonitoredSessionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Supervisors can view but not edit
        ];
    }
}
