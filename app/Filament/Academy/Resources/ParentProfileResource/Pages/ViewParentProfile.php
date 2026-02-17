<?php

namespace App\Filament\Academy\Resources\ParentProfileResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Academy\Resources\ParentProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewParentProfile extends ViewRecord
{
    protected static string $resource = ParentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
