<?php

namespace App\Filament\Resources\ParentProfileResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ParentProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListParentProfiles extends ListRecords
{
    protected static string $resource = ParentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
