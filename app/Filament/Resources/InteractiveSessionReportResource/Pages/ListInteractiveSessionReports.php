<?php

namespace App\Filament\Resources\InteractiveSessionReportResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\InteractiveSessionReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInteractiveSessionReports extends ListRecords
{
    protected static string $resource = InteractiveSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
