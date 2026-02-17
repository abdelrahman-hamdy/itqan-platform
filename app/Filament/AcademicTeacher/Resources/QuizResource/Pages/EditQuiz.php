<?php

namespace App\Filament\AcademicTeacher\Resources\QuizResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\AcademicTeacher\Resources\QuizResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuiz extends EditRecord
{
    protected static string $resource = QuizResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
