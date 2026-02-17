<?php

namespace App\Filament\Resources\BusinessServiceCategoryResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\BusinessServiceCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBusinessServiceCategories extends ListRecords
{
    protected static string $resource = BusinessServiceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
