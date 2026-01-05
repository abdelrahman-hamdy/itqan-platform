<?php

namespace App\Filament\Resources\StudentSessionReportResource\Pages;

use App\Filament\Resources\StudentSessionReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStudentSessionReport extends ViewRecord
{
    protected static string $resource = StudentSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }
}
