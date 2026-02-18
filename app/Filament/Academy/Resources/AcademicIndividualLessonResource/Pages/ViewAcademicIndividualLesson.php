<?php

namespace App\Filament\Academy\Resources\AcademicIndividualLessonResource\Pages;

use App\Filament\Academy\Resources\AcademicIndividualLessonResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewAcademicIndividualLesson extends ViewRecord
{
    protected static string $resource = AcademicIndividualLessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
