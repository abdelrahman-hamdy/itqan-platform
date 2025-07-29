<?php

namespace App\Filament\Resources\AcademicSettingsResource\Pages;

use App\Filament\Resources\AcademicSettingsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAcademicSettings extends CreateRecord
{
    protected static string $resource = AcademicSettingsResource::class;

    public function getTitle(): string
    {
        return 'إنشاء إعدادات أكاديمية جديدة';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Automatically set academy_id based on current user's academy
        $data['academy_id'] = auth()->user()->academy_id ?? 1;
        $data['created_by'] = auth()->id();
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الإعدادات الأكاديمية بنجاح';
    }
}
