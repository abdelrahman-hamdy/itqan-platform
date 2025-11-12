<?php

namespace App\Filament\Teacher\Resources\HomeworkSubmissionResource\Pages;

use App\Filament\Teacher\Resources\HomeworkSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewHomeworkSubmission extends ViewRecord
{
    protected static string $resource = HomeworkSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
