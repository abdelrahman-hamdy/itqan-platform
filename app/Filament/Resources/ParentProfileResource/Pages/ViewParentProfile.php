<?php

namespace App\Filament\Resources\ParentProfileResource\Pages;

use App\Filament\Resources\ParentProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewParentProfile extends ViewRecord
{
    protected static string $resource = ParentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
