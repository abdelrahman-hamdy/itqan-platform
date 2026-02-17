<?php

namespace App\Filament\Resources\ParentProfileResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\ParentProfileResource;
use Filament\Actions;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

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
