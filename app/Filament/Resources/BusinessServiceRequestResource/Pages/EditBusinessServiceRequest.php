<?php

namespace App\Filament\Resources\BusinessServiceRequestResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\BusinessServiceRequestResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditBusinessServiceRequest extends EditRecord
{
    protected static string $resource = BusinessServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
