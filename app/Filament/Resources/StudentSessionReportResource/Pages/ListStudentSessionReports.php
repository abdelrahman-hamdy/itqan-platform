<?php

namespace App\Filament\Resources\StudentSessionReportResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\StudentSessionReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentSessionReports extends ListRecords
{
    protected static string $resource = StudentSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
