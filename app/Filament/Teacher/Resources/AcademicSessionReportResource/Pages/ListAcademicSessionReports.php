<?php

namespace App\Filament\Teacher\Resources\AcademicSessionReportResource\Pages;

use App\Filament\Teacher\Resources\AcademicSessionReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcademicSessionReports extends ListRecords
{
    protected static string $resource = AcademicSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
