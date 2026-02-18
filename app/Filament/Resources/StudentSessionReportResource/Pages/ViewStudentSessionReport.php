<?php

namespace App\Filament\Resources\StudentSessionReportResource\Pages;

use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Resources\StudentSessionReportResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewStudentSessionReport extends ViewRecord
{
    protected static string $resource = StudentSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
