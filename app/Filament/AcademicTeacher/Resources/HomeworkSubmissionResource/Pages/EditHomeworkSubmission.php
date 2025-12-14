<?php

namespace App\Filament\AcademicTeacher\Resources\HomeworkSubmissionResource\Pages;

use App\Filament\AcademicTeacher\Resources\HomeworkSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHomeworkSubmission extends EditRecord
{
    protected static string $resource = HomeworkSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('عرض'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
