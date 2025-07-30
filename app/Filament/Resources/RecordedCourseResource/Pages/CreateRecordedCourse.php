<?php

namespace App\Filament\Resources\RecordedCourseResource\Pages;

use App\Filament\Resources\RecordedCourseResource;
use App\Helpers\AcademyHelper;
use Filament\Resources\Pages\CreateRecord;

class CreateRecordedCourse extends CreateRecord
{
    protected static string $resource = RecordedCourseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currentAcademy = AcademyHelper::getCurrentAcademy();
        
        if ($currentAcademy) {
            $data['academy_id'] = $currentAcademy->id;
        }
        
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        
        return $data;
    }
} 