<?php

namespace App\Filament\Supervisor\Resources\MonitoredIndividualCirclesResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredIndividualCirclesResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewMonitoredIndividualCircle extends ViewRecord
{
    protected static string $resource = MonitoredIndividualCirclesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Supervisors can view but not edit
        ];
    }

    public function getHeading(): string
    {
        return $this->getRecord()->name ?? 'حلقة فردية';
    }

    public function getBreadcrumb(): string
    {
        return $this->getRecord()->name ?? 'حلقة فردية';
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl() => 'الحلقات الفردية',
            '' => $this->getBreadcrumb(),
        ];
    }
}
