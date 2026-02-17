<?php

namespace App\Filament\Academy\Resources\QuizResource\Pages;

use App\Filament\Academy\Resources\QuizResource;
use Filament\Facades\Filament;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;

class CreateQuiz extends CreateRecord
{
    protected static string $resource = QuizResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['academy_id'] = Filament::getTenant()?->id;

        return $data;
    }
}
