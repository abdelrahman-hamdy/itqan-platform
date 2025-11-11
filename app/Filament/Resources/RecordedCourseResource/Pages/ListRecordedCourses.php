<?php

namespace App\Filament\Resources\RecordedCourseResource\Pages;

use App\Filament\Resources\RecordedCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecordedCourses extends ListRecords
{
    protected static string $resource = RecordedCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
} 