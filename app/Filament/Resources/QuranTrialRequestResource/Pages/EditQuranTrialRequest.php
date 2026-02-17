<?php

namespace App\Filament\Resources\QuranTrialRequestResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\QuranTrialRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuranTrialRequest extends EditRecord
{
    protected static string $resource = QuranTrialRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
