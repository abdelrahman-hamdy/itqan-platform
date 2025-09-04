<?php

namespace App\Filament\Resources\AcademicSessionReportResource\Pages;

use App\Filament\Resources\AcademicSessionReportResource;
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
