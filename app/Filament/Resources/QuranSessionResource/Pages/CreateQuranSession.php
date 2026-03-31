<?php

namespace App\Filament\Resources\QuranSessionResource\Pages;

use App\Filament\Pages\BaseCreateRecord as CreateRecord;
use App\Filament\Resources\QuranSessionResource;
use App\Services\SessionConflictService;
use Carbon\Carbon;

class CreateQuranSession extends CreateRecord
{
    protected static string $resource = QuranSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['quran_teacher_id']) && ! empty($data['scheduled_at'])) {
            app(SessionConflictService::class)->validate(
                (int) $data['quran_teacher_id'],
                Carbon::parse($data['scheduled_at']),
                (int) ($data['duration_minutes'] ?? 60),
            );
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
