<?php

namespace App\Filament\Academy\Resources\QuizAssignmentResource\Pages;

use App\Filament\Academy\Resources\QuizAssignmentResource;
use Filament\Actions\DeleteAction;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditQuizAssignment extends EditRecord
{
    protected static string $resource = QuizAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
