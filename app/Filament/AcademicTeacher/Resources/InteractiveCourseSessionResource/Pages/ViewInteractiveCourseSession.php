<?php

namespace App\Filament\AcademicTeacher\Resources\InteractiveCourseSessionResource\Pages;

use App\Filament\AcademicTeacher\Resources\InteractiveCourseSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInteractiveCourseSession extends ViewRecord
{
    protected static string $resource = InteractiveCourseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
