<?php

namespace App\Filament\Academy\Resources\StudentSessionReportResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Academy\Resources\StudentSessionReportResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewStudentSessionReport extends ViewRecord
{
    protected static string $resource = StudentSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }
}
