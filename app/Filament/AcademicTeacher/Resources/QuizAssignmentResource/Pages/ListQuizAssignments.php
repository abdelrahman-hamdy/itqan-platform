<?php

namespace App\Filament\AcademicTeacher\Resources\QuizAssignmentResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\AcademicTeacher\Resources\QuizAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuizAssignments extends ListRecords
{
    protected static string $resource = QuizAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('teacher.quiz_assignments.action_assign_quiz')),
        ];
    }
}
