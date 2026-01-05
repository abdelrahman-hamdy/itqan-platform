<?php

namespace App\Filament\Supervisor\Resources\MonitoredAcademicLessonsResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredAcademicLessonsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonitoredAcademicLessons extends ListRecords
{
    protected static string $resource = MonitoredAcademicLessonsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for supervisors
        ];
    }
}
