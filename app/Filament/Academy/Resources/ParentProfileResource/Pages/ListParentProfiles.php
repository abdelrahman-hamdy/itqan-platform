<?php

namespace App\Filament\Academy\Resources\ParentProfileResource\Pages;

use App\Filament\Academy\Resources\ParentProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListParentProfiles extends ListRecords
{
    protected static string $resource = ParentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
