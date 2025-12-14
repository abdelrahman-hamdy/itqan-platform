<?php

namespace App\Filament\Resources\AcademicIndividualLessonResource\Pages;

use App\Filament\Resources\AcademicIndividualLessonResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAcademicIndividualLesson extends CreateRecord
{
    protected static string $resource = AcademicIndividualLessonResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
