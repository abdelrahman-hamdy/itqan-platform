<?php

namespace App\Filament\Resources\QuizAssignmentResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\QuizAssignmentResource;
use Filament\Actions;
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
