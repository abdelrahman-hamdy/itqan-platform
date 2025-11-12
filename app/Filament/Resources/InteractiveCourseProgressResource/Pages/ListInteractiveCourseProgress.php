<?php

namespace App\Filament\Resources\InteractiveCourseProgressResource\Pages;

use App\Filament\Resources\InteractiveCourseProgressResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInteractiveCourseProgress extends ListRecords
{
    protected static string $resource = InteractiveCourseProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
