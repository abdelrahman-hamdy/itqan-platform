<?php

namespace App\Filament\Academy\Resources\StudentSessionReportResource\Pages;

use App\Filament\Academy\Resources\StudentSessionReportResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
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
