<?php

namespace App\Filament\Resources\InteractiveCourseSettingsResource\Pages;

use App\Filament\Resources\InteractiveCourseSettingsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInteractiveCourseSettings extends ListRecords
{
    protected static string $resource = InteractiveCourseSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
