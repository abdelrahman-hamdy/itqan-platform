<?php

namespace App\Filament\AcademicTeacher\Resources\QuizResource\Pages;

use App\Filament\AcademicTeacher\Resources\QuizResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuiz extends EditRecord
{
    protected static string $resource = QuizResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
