<?php

namespace App\Filament\Academy\Resources\InteractiveCourseSessionResource\Pages;

use App\Filament\Academy\Resources\InteractiveCourseSessionResource;
use App\Filament\Pages\BaseEditRecord as EditRecord;
use App\Services\SessionConflictService;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;

class EditInteractiveCourseSession extends EditRecord
{
    protected static string $resource = InteractiveCourseSessionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $newScheduledAt = ! empty($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null;

        if ($newScheduledAt && $this->record->scheduled_at?->toDateTimeString() !== $newScheduledAt->toDateTimeString()) {
            $teacherUserId = $this->record->course?->assignedTeacher?->user_id;

            if ($teacherUserId) {
                app(SessionConflictService::class)->validate(
                    (int) $teacherUserId,
                    $newScheduledAt,
                    (int) ($data['duration_minutes'] ?? $this->record->duration_minutes ?? 60),
                    $this->record->id,
                    'interactive',
                );
            }
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
