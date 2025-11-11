<?php

namespace App\Filament\Resources\SubjectResource\Pages;

use App\Filament\Resources\SubjectResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\AcademyContextService;

class CreateSubject extends CreateRecord
{
    protected static string $resource = SubjectResource::class;
    
    protected static ?string $title = 'إضافة مادة جديدة';

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
        return 'تم إنشاء المادة بنجاح';
    }
} 