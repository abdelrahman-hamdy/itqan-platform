<?php

namespace App\Filament\Teacher\Resources\QuizAssignmentResource\Pages;

use App\Filament\Teacher\Resources\QuizAssignmentResource;
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
