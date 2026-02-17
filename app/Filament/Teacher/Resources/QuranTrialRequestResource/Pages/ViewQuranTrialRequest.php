<?php

namespace App\Filament\Teacher\Resources\QuranTrialRequestResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Teacher\Resources\QuranTrialRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQuranTrialRequest extends ViewRecord
{
    protected static string $resource = QuranTrialRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }
}
