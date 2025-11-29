<?php

namespace App\Filament\Teacher\Resources\QuizAssignmentResource\Pages;

use App\Filament\Teacher\Resources\QuizAssignmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuizAssignment extends CreateRecord
{
    protected static string $resource = QuizAssignmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
