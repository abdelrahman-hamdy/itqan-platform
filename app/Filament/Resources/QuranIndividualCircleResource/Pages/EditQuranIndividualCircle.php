<?php

namespace App\Filament\Resources\QuranIndividualCircleResource\Pages;

use App\Filament\Resources\QuranIndividualCircleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuranIndividualCircle extends EditRecord
{
    protected static string $resource = QuranIndividualCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
