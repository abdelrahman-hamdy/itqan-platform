<?php

namespace App\Filament\Academy\Resources\AcademicIndividualLessonResource\Pages;

use App\Filament\Academy\Resources\AcademicIndividualLessonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAcademicIndividualLessons extends ListRecords
{
    protected static string $resource = AcademicIndividualLessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إنشاء درس فردي'),
        ];
    }
}
