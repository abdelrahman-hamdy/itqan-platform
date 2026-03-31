<?php

namespace App\Filament\Resources\InteractiveCourseSessionResource\Pages;

use App\Filament\Pages\BaseCreateRecord as CreateRecord;
use App\Filament\Resources\InteractiveCourseSessionResource;
use App\Models\InteractiveCourse;
use App\Services\SessionConflictService;
use Carbon\Carbon;

class CreateInteractiveCourseSession extends CreateRecord
{
    protected static string $resource = InteractiveCourseSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['course_id']) && ! empty($data['scheduled_at'])) {
            $teacherUserId = InteractiveCourse::where('id', $data['course_id'])
                ->with('assignedTeacher:id,user_id')
                ->first()
                ?->assignedTeacher?->user_id;

            if ($teacherUserId) {
                app(SessionConflictService::class)->validate(
                    (int) $teacherUserId,
                    Carbon::parse($data['scheduled_at']),
                    (int) ($data['duration_minutes'] ?? 60),
                    null,
                    'interactive',
                );
            }
        }

        return $data;
    }
}
