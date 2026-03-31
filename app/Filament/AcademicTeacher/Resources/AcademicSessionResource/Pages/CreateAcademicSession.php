<?php

namespace App\Filament\AcademicTeacher\Resources\AcademicSessionResource\Pages;

use App\Filament\AcademicTeacher\Resources\AcademicSessionResource;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;
use App\Services\SessionConflictService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CreateAcademicSession extends CreateRecord
{
    protected static string $resource = AcademicSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['scheduled_at'])) {
            app(SessionConflictService::class)->validate(
                Auth::id(),
                Carbon::parse($data['scheduled_at']),
                (int) ($data['duration_minutes'] ?? 60),
                null,
                'academic',
            );
        }

        return $data;
    }
}
