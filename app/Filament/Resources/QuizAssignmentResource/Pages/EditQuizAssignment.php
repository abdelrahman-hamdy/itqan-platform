<?php

namespace App\Filament\Resources\QuizAssignmentResource\Pages;

use App\Filament\Resources\QuizAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuizAssignment extends EditRecord
{
    protected static string $resource = QuizAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
