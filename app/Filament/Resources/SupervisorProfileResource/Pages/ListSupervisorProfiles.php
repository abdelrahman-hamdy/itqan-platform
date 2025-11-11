<?php

namespace App\Filament\Resources\SupervisorProfileResource\Pages;

use App\Filament\Resources\SupervisorProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupervisorProfiles extends ListRecords
{
    protected static string $resource = SupervisorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
