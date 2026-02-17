<?php

namespace App\Filament\Academy\Resources\AcademicIndividualLessonResource\Pages;

use App\Filament\Academy\Resources\AcademicIndividualLessonResource;
use App\Filament\Pages\BaseEditRecord as EditRecord;
use Filament\Actions\DeleteAction;

class EditAcademicIndividualLesson extends EditRecord
{
    protected static string $resource = AcademicIndividualLessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
