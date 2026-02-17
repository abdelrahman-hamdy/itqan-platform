<?php

namespace App\Filament\AcademicTeacher\Resources\QuizResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\AcademicTeacher\Resources\QuizResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuizzes extends ListRecords
{
    protected static string $resource = QuizResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة اختبار'),
        ];
    }
}
