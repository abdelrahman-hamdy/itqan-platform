<?php

namespace App\Filament\Supervisor\Resources\MonitoredAcademicSessionsResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredAcademicSessionsResource;
use Filament\Resources\Pages\ListRecords;

class ListMonitoredAcademicSessions extends ListRecords
{
    protected static string $resource = MonitoredAcademicSessionsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
