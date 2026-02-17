<?php

namespace App\Filament\Academy\Resources\QuizResource\Pages;

use App\Filament\Academy\Resources\QuizResource;
use Filament\Actions\CreateAction;
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
