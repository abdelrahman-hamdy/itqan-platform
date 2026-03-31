<?php

namespace App\Filament\Resources\InteractiveCourseSessionResource\Pages;

use App\Filament\Pages\BaseEditRecord as EditRecord;
use App\Filament\Resources\InteractiveCourseSessionResource;
use App\Services\SessionConflictService;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;

class EditInteractiveCourseSession extends EditRecord
{
    protected static string $resource = InteractiveCourseSessionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['scheduled_at']) && $this->record->scheduled_at?->toDateTimeString() !== Carbon::parse($data['scheduled_at'])->toDateTimeString()) {
            $teacherUserId = $this->record->course?->assignedTeacher?->user_id;

            if ($teacherUserId) {
                app(SessionConflictService::class)->validate(
                    (int) $teacherUserId,
                    Carbon::parse($data['scheduled_at']),
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
            DeleteAction::make(),
        ];
    }
}
