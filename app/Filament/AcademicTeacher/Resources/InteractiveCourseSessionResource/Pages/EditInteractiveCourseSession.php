<?php

namespace App\Filament\AcademicTeacher\Resources\InteractiveCourseSessionResource\Pages;

use App\Filament\AcademicTeacher\Resources\InteractiveCourseSessionResource;
use App\Filament\Pages\BaseEditRecord as EditRecord;
use App\Services\SessionConflictService;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Illuminate\Support\Facades\Auth;

class EditInteractiveCourseSession extends EditRecord
{
    protected static string $resource = InteractiveCourseSessionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['scheduled_at']) && $this->record->scheduled_at?->toDateTimeString() !== Carbon::parse($data['scheduled_at'])->toDateTimeString()) {
            app(SessionConflictService::class)->validate(
                Auth::id(),
                Carbon::parse($data['scheduled_at']),
                (int) ($data['duration_minutes'] ?? $this->record->duration_minutes ?? 60),
                $this->record->id,
                'interactive',
            );
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
