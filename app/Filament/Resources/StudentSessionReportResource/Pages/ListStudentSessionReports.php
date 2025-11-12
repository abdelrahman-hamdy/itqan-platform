<?php

namespace App\Filament\Resources\StudentSessionReportResource\Pages;

use App\Filament\Resources\StudentSessionReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentSessionReports extends ListRecords
{
    protected static string $resource = StudentSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
