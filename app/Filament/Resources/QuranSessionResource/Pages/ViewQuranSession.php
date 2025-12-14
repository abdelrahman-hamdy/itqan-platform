<?php

namespace App\Filament\Resources\QuranSessionResource\Pages;

use App\Filament\Resources\QuranSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQuranSession extends ViewRecord
{
    protected static string $resource = QuranSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }
}
