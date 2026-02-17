<?php

namespace App\Filament\Academy\Resources\QuranIndividualCircleResource\Pages;

use App\Filament\Academy\Resources\QuranIndividualCircleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuranIndividualCircles extends ListRecords
{
    protected static string $resource = QuranIndividualCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إنشاء حلقة فردية'),
        ];
    }
}
