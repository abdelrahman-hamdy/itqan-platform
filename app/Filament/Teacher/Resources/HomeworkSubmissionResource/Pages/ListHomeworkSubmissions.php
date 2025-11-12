<?php

namespace App\Filament\Teacher\Resources\HomeworkSubmissionResource\Pages;

use App\Filament\Teacher\Resources\HomeworkSubmissionResource;
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
