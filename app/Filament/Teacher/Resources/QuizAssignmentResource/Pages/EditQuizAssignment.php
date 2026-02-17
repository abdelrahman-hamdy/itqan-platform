<?php

namespace App\Filament\Teacher\Resources\QuizAssignmentResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Teacher\Resources\QuizAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuizAssignment extends EditRecord
{
    protected static string $resource = QuizAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
