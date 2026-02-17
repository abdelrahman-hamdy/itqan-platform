<?php

namespace App\Filament\Resources\AcademicGradeLevelResource\Pages;

use App\Filament\Resources\AcademicGradeLevelResource;
use App\Services\AcademyContextService;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;

class CreateAcademicGradeLevel extends CreateRecord
{
    protected static string $resource = AcademicGradeLevelResource::class;

    public function getTitle(): string
    {
        return 'إنشاء صف دراسي جديد';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set the academy_id from the current context
        $academyId = AcademyContextService::getCurrentAcademyId();
        if ($academyId) {
            $data['academy_id'] = $academyId;
        }

        // Set the created_by field
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
