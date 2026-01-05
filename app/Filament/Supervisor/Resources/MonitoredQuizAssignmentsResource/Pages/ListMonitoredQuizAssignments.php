<?php

namespace App\Filament\Supervisor\Resources\MonitoredQuizAssignmentsResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredQuizAssignmentsResource;
use Filament\Resources\Pages\ListRecords;

class ListMonitoredQuizAssignments extends ListRecords
{
    protected static string $resource = MonitoredQuizAssignmentsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - supervisors view only
        ];
    }
}
