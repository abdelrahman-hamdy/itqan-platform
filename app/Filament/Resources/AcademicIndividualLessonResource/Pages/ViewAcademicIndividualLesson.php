<?php

namespace App\Filament\Resources\AcademicIndividualLessonResource\Pages;

use App\Filament\Resources\AcademicIndividualLessonResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAcademicIndividualLesson extends ViewRecord
{
    protected static string $resource = AcademicIndividualLessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }
}
