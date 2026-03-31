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
        if (! empty($data['scheduled_at']) && $this->record->scheduled_at?->toDateTimeString() !== Carbon::parse($data['scheduled_at'])->toDateTimeString()) {
            $profileId = $data['academic_teacher_id'] ?? $this->record->academic_teacher_id;
            $teacherUserId = AcademicTeacherProfile::where('id', $profileId)->value('user_id');

            if ($teacherUserId) {
                app(SessionConflictService::class)->validate(
                    (int) $teacherUserId,
                    Carbon::parse($data['scheduled_at']),
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
