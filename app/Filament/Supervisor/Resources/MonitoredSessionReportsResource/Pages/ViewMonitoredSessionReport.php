<?php

namespace App\Filament\Supervisor\Resources\MonitoredSessionReportsResource\Pages;

use App\Filament\Supervisor\Resources\MonitoredSessionReportsResource;
use App\Models\AcademicSessionReport;
use App\Models\StudentSessionReport;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewMonitoredSessionReport extends ViewRecord
{
    protected static string $resource = MonitoredSessionReportsResource::class;

    /**
     * Resolve the record based on type query parameter.
     */
    public function resolveRecord(int|string $key): Model
    {
        $type = request()->query('type', 'quran');

        if ($type === 'academic') {
            return AcademicSessionReport::with(['session', 'student', 'teacher', 'academy'])
                ->findOrFail($key);
        }

        return StudentSessionReport::with(['session', 'student', 'teacher', 'academy'])
            ->findOrFail($key);
    }

    public function getTitle(): string
    {
        $studentName = $this->record->student?->name ?? 'طالب';
        $sessionTitle = $this->record->session?->title ?? 'جلسة';

        return "تقرير: {$studentName} - {$sessionTitle}";
    }

    protected function getHeaderActions(): array
    {
        return [
            // No edit action - supervisors view only
        ];
    }
}
