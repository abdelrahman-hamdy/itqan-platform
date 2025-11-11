<?php

namespace App\Filament\Resources\BusinessServiceRequestResource\Pages;

use App\Filament\Resources\BusinessServiceRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBusinessServiceRequests extends ListRecords
{
    protected static string $resource = BusinessServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
