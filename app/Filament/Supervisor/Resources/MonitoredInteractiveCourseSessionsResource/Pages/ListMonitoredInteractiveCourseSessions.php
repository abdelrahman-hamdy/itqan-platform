<?php

namespace App\Filament\Supervisor\Resources\MonitoredInteractiveCourseSessionsResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredInteractiveCourseSessionsResource;
use Filament\Resources\Pages\ListRecords;

class ListMonitoredInteractiveCourseSessions extends ListRecords
{
    protected static string $resource = MonitoredInteractiveCourseSessionsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
