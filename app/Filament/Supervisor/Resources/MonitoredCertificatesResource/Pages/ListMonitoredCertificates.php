<?php

namespace App\Filament\Supervisor\Resources\MonitoredCertificatesResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredCertificatesResource;
use Filament\Resources\Pages\ListRecords;

class ListMonitoredCertificates extends ListRecords
{
    protected static string $resource = MonitoredCertificatesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - supervisors view only
        ];
    }
}
