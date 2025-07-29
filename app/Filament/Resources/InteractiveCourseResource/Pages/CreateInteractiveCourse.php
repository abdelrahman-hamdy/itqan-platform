<?php

namespace App\Filament\Resources\InteractiveCourseResource\Pages;

use App\Filament\Resources\InteractiveCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInteractiveCourse extends CreateRecord
{
    protected static string $resource = InteractiveCourseResource::class;

    public function getTitle(): string
    {
        return 'إنشاء دورة تفاعلية جديدة';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['academy_id'] = auth()->user()->academy_id ?? 1;
        $data['created_by'] = auth()->id();
        
        // Calculate total sessions
        if (isset($data['duration_weeks']) && isset($data['sessions_per_week'])) {
            $data['total_sessions'] = $data['duration_weeks'] * $data['sessions_per_week'];
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الدورة التفاعلية بنجاح';
    }
}
