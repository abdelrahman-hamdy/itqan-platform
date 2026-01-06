<?php

namespace App\Filament\Resources\AcademicPackageResource\Pages;

use App\Filament\Resources\AcademicPackageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAcademicPackage extends CreateRecord
{
    protected static string $resource = AcademicPackageResource::class;

    public function getTitle(): string
    {
        return 'إنشاء باقة أكاديمية جديدة';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['academy_id'] = auth()->user()->academy_id ?? 1;
        $data['created_by'] = auth()->id();

        return $data;
    }
}
