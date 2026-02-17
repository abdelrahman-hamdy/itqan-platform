<?php

namespace App\Filament\Academy\Resources\QuizResource\Pages;

use App\Filament\Academy\Resources\QuizResource;
use Filament\Actions\EditAction;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

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
