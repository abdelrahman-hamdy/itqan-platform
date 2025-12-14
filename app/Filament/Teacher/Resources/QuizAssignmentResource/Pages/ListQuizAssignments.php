<?php

namespace App\Filament\Teacher\Resources\QuizAssignmentResource\Pages;

use App\Filament\Teacher\Resources\QuizAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuizAssignments extends ListRecords
{
    protected static string $resource = QuizAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('تعيين اختبار'),
        ];
    }
}
