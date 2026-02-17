<?php

namespace App\Filament\Resources\QuizResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\QuizResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQuiz extends ViewRecord
{
    protected static string $resource = QuizResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
