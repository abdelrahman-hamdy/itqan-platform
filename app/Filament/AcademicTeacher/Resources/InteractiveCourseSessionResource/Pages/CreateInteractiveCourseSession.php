<?php

namespace App\Filament\AcademicTeacher\Resources\InteractiveCourseSessionResource\Pages;

use App\Filament\AcademicTeacher\Resources\InteractiveCourseSessionResource;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;
use App\Services\SessionConflictService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CreateInteractiveCourseSession extends CreateRecord
{
    protected static string $resource = InteractiveCourseSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['scheduled_at'])) {
            app(SessionConflictService::class)->validate(
                Auth::id(),
                Carbon::parse($data['scheduled_at']),
                (int) ($data['duration_minutes'] ?? 60),
                null,
                'interactive',
            );
        }

        return $data;
    }
}
