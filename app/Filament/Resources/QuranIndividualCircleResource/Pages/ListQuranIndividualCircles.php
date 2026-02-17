<?php

namespace App\Filament\Resources\QuranIndividualCircleResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\QuranIndividualCircleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuranIndividualCircles extends ListRecords
{
    protected static string $resource = QuranIndividualCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
