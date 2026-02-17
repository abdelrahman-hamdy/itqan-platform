<?php

namespace App\Filament\Resources\RecordedCourseResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\RecordedCourseResource;
use Filament\Actions;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewRecordedCourse extends ViewRecord
{
    protected static string $resource = RecordedCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
