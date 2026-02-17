<?php

namespace App\Filament\Resources\AcademicSessionReportResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\AcademicSessionReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcademicSessionReports extends ListRecords
{
    protected static string $resource = AcademicSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
