<?php

namespace App\Filament\Supervisor\Resources\MonitoredCertificatesResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredCertificatesResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMonitoredCertificate extends ViewRecord
{
    protected static string $resource = MonitoredCertificatesResource::class;

    public function getTitle(): string
    {
        $studentName = $this->record->student?->name ?? 'طالب';
        $certificateNumber = $this->record->certificate_number ?? '';

        return "شهادة: {$studentName} ({$certificateNumber})";
    }

    protected function getHeaderActions(): array
    {
        return [
            // No edit action - supervisors view only
        ];
    }
}
