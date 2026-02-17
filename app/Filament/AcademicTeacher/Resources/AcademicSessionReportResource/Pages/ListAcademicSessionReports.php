<?php

namespace App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcademicSessionReports extends ListRecords
{
    protected static string $resource = AcademicSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة تقرير'),
        ];
    }
}
