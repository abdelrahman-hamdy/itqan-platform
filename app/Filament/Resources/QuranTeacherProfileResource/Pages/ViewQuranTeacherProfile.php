<?php

namespace App\Filament\Resources\QuranTeacherProfileResource\Pages;

use App\Filament\Resources\QuranTeacherProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQuranTeacherProfile extends ViewRecord
{
    protected static string $resource = QuranTeacherProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
