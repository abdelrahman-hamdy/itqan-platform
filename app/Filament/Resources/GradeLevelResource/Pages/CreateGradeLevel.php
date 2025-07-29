<?php

namespace App\Filament\Resources\GradeLevelResource\Pages;

use App\Filament\Resources\GradeLevelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGradeLevel extends CreateRecord
{
    protected static string $resource = GradeLevelResource::class;

    public function getTitle(): string
    {
        return 'إضافة مرحلة دراسية جديدة';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['academy_id'] = auth()->user()->academy_id ?? 1;
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
