<?php

namespace App\Filament\Teacher\Resources\QuranIndividualCircleResource\Pages;

use App\Filament\Teacher\Resources\QuranIndividualCircleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuranIndividualCircle extends EditRecord
{
    protected static string $resource = QuranIndividualCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('عرض'),
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
