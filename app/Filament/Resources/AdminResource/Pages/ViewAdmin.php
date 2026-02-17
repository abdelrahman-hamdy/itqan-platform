<?php

namespace App\Filament\Resources\AdminResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\AdminResource;
use Filament\Actions;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewAdmin extends ViewRecord
{
    protected static string $resource = AdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
