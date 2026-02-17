<?php

namespace App\Filament\Resources\QuranCircleResource\Pages;

use App\Models\QuranCircle;
use App\Filament\Resources\QuranCircleResource;
use App\Services\AcademyContextService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateQuranCircle extends CreateRecord
{
    protected static string $resource = QuranCircleResource::class;

    public function getTitle(): string
    {
        return 'إضافة دائرة قرآن جديدة';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Add the academy ID and created_by automatically
        $data['academy_id'] = AcademyContextService::getCurrentAcademyId() ?? Auth::user()->academy_id;
        $data['created_by'] = Auth::id();

        // Generate circle code using the model method
        $data['circle_code'] = QuranCircle::generateCircleCode($data['academy_id']);

        // Set initial counters only
        $data['enrolled_students'] = 0;
        $data['sessions_completed'] = 0;

        // Respect user's status selections from the form (don't override)
        // status and enrollment_status are set by the form toggles

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إضافة دائرة القرآن بنجاح';
    }
}
