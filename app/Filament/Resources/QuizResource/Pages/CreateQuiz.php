<?php

namespace App\Filament\Resources\QuizResource\Pages;

use App\Filament\Resources\QuizResource;
use App\Helpers\AcademyHelper;
use Filament\Resources\Pages\CreateRecord;

class CreateQuiz extends CreateRecord
{
    protected static string $resource = QuizResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currentAcademy = AcademyHelper::getCurrentAcademy();
        if ($currentAcademy && !isset($data['academy_id'])) {
            $data['academy_id'] = $currentAcademy->id;
        }

        return $data;
    }
}
