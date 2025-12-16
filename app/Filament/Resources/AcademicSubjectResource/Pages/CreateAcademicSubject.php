<?php

namespace App\Filament\Resources\AcademicSubjectResource\Pages;

use App\Filament\Resources\AcademicSubjectResource;
use App\Services\AcademyContextService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAcademicSubject extends CreateRecord
{
    protected static string $resource = AcademicSubjectResource::class;

    protected static ?string $title = 'إضافة مادة أكاديمية جديدة';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إضافة المادة الأكاديمية بنجاح';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Add the academy ID and created_by automatically
        $data['academy_id'] = AcademyContextService::getCurrentAcademyId() ?? Auth::user()->academy_id;
        $data['created_by'] = Auth::id();

        return $data;
    }
}
