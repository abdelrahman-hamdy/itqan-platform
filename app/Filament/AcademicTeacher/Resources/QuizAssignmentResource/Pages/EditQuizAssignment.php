<?php

namespace App\Filament\AcademicTeacher\Resources\QuizAssignmentResource\Pages;

use App\Filament\AcademicTeacher\Resources\QuizAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuizAssignment extends EditRecord
{
    protected static string $resource = QuizAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('عرض'),
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
