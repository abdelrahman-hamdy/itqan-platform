<?php

namespace App\Filament\Academy\Resources\QuizAssignmentResource\Pages;

use App\Filament\Academy\Resources\QuizAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuizAssignments extends ListRecords
{
    protected static string $resource = QuizAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('تعيين اختبار'),
        ];
    }
}
