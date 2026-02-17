<?php

namespace App\Filament\Resources\UserResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
