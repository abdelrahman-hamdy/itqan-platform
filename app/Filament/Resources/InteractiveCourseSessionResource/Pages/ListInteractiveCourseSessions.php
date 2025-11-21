<?php

namespace App\Filament\Resources\InteractiveCourseSessionResource\Pages;

use App\Filament\Resources\InteractiveCourseSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInteractiveCourseSessions extends ListRecords
{
    protected static string $resource = InteractiveCourseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
