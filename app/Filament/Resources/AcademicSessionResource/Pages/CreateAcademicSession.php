<?php

namespace App\Filament\Resources\AcademicSessionResource\Pages;

use App\Filament\Pages\BaseCreateRecord as CreateRecord;
use App\Filament\Resources\AcademicSessionResource;
use App\Models\AcademicTeacherProfile;
use App\Services\SessionConflictService;
use Carbon\Carbon;

class CreateAcademicSession extends CreateRecord
{
    protected static string $resource = AcademicSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['academic_teacher_id']) && ! empty($data['scheduled_at'])) {
            $teacherUserId = AcademicTeacherProfile::where('id', $data['academic_teacher_id'])->value('user_id');

            if ($teacherUserId) {
                app(SessionConflictService::class)->validate(
                    (int) $teacherUserId,
                    Carbon::parse($data['scheduled_at']),
                    (int) ($data['duration_minutes'] ?? 60),
                    null,
                    'academic',
                );
            }
        }

        return $data;
    }
}
