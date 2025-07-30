<?php

namespace App\Filament\Resources\GradeLevelResource\Pages;

use App\Filament\Resources\GradeLevelResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\AcademyContextService;

class CreateGradeLevel extends CreateRecord
{
    protected static string $resource = GradeLevelResource::class;

    public function getTitle(): string
    {
        return 'إضافة مرحلة دراسية جديدة';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $academyId = AcademyContextService::getCurrentAcademyId();
        
        if (!$academyId) {
            throw new \Exception('No academy context available. Please select an academy first.');
        }
        
        $data['academy_id'] = $academyId;
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء المرحلة الدراسية بنجاح';
    }
}
