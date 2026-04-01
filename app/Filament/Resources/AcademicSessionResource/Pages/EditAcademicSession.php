<?php

namespace App\Filament\Resources\AcademicSessionResource\Pages;

use App\Filament\Pages\BaseEditRecord as EditRecord;
use App\Filament\Resources\AcademicSessionResource;
use App\Models\AcademicTeacherProfile;
use App\Services\SessionConflictService;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;

class EditAcademicSession extends EditRecord
{
    protected static string $resource = AcademicSessionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $newScheduledAt = ! empty($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null;

        if ($newScheduledAt && $this->record->scheduled_at?->toDateTimeString() !== $newScheduledAt->toDateTimeString()) {
            $profileId = $data['academic_teacher_id'] ?? $this->record->academic_teacher_id;
            $teacherUserId = AcademicTeacherProfile::where('id', $profileId)->value('user_id');

            if ($teacherUserId) {
                app(SessionConflictService::class)->validate(
                    (int) $teacherUserId,
                    $newScheduledAt,
                    (int) ($data['duration_minutes'] ?? $this->record->duration_minutes ?? 60),
                    $this->record->id,
                    'academic',
                );
            }
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
