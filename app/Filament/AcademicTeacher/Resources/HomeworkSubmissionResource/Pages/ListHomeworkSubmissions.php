<?php

namespace App\Filament\AcademicTeacher\Resources\HomeworkSubmissionResource\Pages;

use App\Filament\AcademicTeacher\Resources\HomeworkSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHomeworkSubmissions extends ListRecords
{
    protected static string $resource = HomeworkSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Create action disabled for teachers
        ];
    }
}
