<?php

namespace App\Filament\Resources\QuizResource\Pages;

use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Resources\QuizResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewQuiz extends ViewRecord
{
    protected static string $resource = QuizResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
