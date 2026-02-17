<?php

namespace App\Filament\AcademicTeacher\Resources\QuizResource\Pages;

use App\Filament\AcademicTeacher\Resources\QuizResource;
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
