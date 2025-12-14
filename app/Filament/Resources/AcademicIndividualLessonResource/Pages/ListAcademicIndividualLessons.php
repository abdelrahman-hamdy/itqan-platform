<?php

namespace App\Filament\Resources\AcademicIndividualLessonResource\Pages;

use App\Filament\Resources\AcademicIndividualLessonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcademicIndividualLessons extends ListRecords
{
    protected static string $resource = AcademicIndividualLessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة درس فردي'),
        ];
    }
}
