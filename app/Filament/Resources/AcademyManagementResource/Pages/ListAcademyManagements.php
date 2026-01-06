<?php

namespace App\Filament\Resources\AcademyManagementResource\Pages;

use App\Filament\Resources\AcademyManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcademyManagements extends ListRecords
{
    protected static string $resource = AcademyManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
