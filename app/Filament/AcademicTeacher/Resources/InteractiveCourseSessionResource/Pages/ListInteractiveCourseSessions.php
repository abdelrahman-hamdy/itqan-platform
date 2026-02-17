<?php

namespace App\Filament\AcademicTeacher\Resources\InteractiveCourseSessionResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\AcademicTeacher\Resources\InteractiveCourseSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInteractiveCourseSessions extends ListRecords
{
    protected static string $resource = InteractiveCourseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة جلسة'),
        ];
    }
}
