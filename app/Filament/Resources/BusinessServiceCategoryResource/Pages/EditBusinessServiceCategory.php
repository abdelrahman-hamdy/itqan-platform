<?php

namespace App\Filament\Resources\BusinessServiceCategoryResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\BusinessServiceCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBusinessServiceCategory extends EditRecord
{
    protected static string $resource = BusinessServiceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
