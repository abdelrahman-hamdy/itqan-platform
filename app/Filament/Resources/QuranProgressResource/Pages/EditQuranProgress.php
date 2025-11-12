<?php

namespace App\Filament\Resources\QuranProgressResource\Pages;

use App\Filament\Resources\QuranProgressResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuranProgress extends EditRecord
{
    protected static string $resource = QuranProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
