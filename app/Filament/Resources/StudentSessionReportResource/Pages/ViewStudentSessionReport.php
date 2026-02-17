<?php

namespace App\Filament\Resources\StudentSessionReportResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\StudentSessionReportResource;
use Filament\Actions;
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
