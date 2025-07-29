<?php

namespace App\Filament\Resources\SubjectResource\Pages;

use App\Filament\Resources\SubjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubject extends CreateRecord
{
    protected static string $resource = SubjectResource::class;
    
    protected static ?string $title = 'إضافة مادة جديدة';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Automatically set academy_id based on current user's academy
        $data['academy_id'] = auth()->user()->academy_id ?? 1; // Default to academy 1 if not set
        
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