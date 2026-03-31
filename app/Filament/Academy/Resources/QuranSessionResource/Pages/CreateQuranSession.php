<?php

namespace App\Filament\Academy\Resources\QuranSessionResource\Pages;

use App\Filament\Academy\Resources\QuranSessionResource;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;
use App\Services\SessionConflictService;
use Carbon\Carbon;

class CreateQuranSession extends CreateRecord
{
    protected static string $resource = QuranSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['academy_id'] = auth()->user()->academy_id;

        if (! empty($data['quran_teacher_id']) && ! empty($data['scheduled_at'])) {
            app(SessionConflictService::class)->validate(
                (int) $data['quran_teacher_id'],
                Carbon::parse($data['scheduled_at']),
                (int) ($data['duration_minutes'] ?? 60),
            );
        }

        return $data;
    }
}
