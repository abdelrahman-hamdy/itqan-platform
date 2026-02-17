<?php

namespace App\Filament\Academy\Resources\QuranIndividualCircleResource\Pages;

use App\Filament\Academy\Resources\QuranIndividualCircleResource;
use App\Filament\Pages\BaseEditRecord as EditRecord;
use Filament\Actions\DeleteAction;

class EditQuranIndividualCircle extends EditRecord
{
    protected static string $resource = QuranIndividualCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
