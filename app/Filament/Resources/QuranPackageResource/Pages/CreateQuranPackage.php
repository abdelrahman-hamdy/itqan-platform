<?php

namespace App\Filament\Resources\QuranPackageResource\Pages;

use App\Filament\Resources\QuranPackageResource;
use App\Services\AcademyContextService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateQuranPackage extends CreateRecord
{
    protected static string $resource = QuranPackageResource::class;

    public function getTitle(): string
    {
        return 'إضافة باقة قرآن جديدة';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Add the academy ID and created_by automatically
        $data['academy_id'] = AcademyContextService::getCurrentAcademyId() ?? Auth::user()->academy_id;
        $data['created_by'] = Auth::id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء باقة القرآن بنجاح';
    }
}
