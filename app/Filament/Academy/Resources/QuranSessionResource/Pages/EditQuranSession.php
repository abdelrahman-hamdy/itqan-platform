<?php

namespace App\Filament\Academy\Resources\QuranSessionResource\Pages;

use App\Filament\Academy\Resources\QuranSessionResource;
use App\Filament\Pages\BaseEditRecord as EditRecord;
use App\Services\SessionConflictService;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;

class EditQuranSession extends EditRecord
{
    protected static string $resource = QuranSessionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $newScheduledAt = ! empty($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null;

        if ($newScheduledAt && $this->record->scheduled_at?->toDateTimeString() !== $newScheduledAt->toDateTimeString()) {
            $teacherId = $data['quran_teacher_id'] ?? $this->record->quran_teacher_id;

            if ($teacherId) {
                app(SessionConflictService::class)->validate(
                    (int) $teacherId,
                    $newScheduledAt,
                    (int) ($data['duration_minutes'] ?? $this->record->duration_minutes ?? 60),
                    $this->record->id,
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
