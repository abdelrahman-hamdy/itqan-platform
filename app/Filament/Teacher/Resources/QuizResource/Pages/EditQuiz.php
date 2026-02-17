<?php

namespace App\Filament\Teacher\Resources\QuizResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Teacher\Resources\QuizResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

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
