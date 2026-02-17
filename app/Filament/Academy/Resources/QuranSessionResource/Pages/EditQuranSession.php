<?php

namespace App\Filament\Academy\Resources\QuranSessionResource\Pages;

use App\Filament\Academy\Resources\QuranSessionResource;
use App\Filament\Pages\BaseEditRecord as EditRecord;
use Filament\Actions\DeleteAction;

class EditQuranSession extends EditRecord
{
    protected static string $resource = QuranSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
