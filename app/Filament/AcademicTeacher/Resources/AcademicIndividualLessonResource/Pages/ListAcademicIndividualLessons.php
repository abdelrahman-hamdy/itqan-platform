<?php

namespace App\Filament\AcademicTeacher\Resources\AcademicIndividualLessonResource\Pages;

use App\Filament\AcademicTeacher\Resources\AcademicIndividualLessonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcademicIndividualLessons extends ListRecords
{
    protected static string $resource = AcademicIndividualLessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إنشاء درس جديد'),
        ];
    }

    public function getTitle(): string
    {
        return 'الدروس الفردية';
    }
}
