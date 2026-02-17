<?php

namespace App\Filament\Resources\InteractiveCourseResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\InteractiveCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInteractiveCourses extends ListRecords
{
    protected static string $resource = InteractiveCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
