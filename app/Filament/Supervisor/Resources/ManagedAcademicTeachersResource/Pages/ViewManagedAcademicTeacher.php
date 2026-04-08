<?php

namespace App\Filament\Supervisor\Resources\ManagedAcademicTeachersResource\Pages;

use App\Filament\Supervisor\Resources\BaseSupervisorResource;
use App\Filament\Supervisor\Resources\ManagedAcademicTeachersResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewManagedAcademicTeacher extends ViewRecord
{
    protected static string $resource = ManagedAcademicTeachersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_calendar')
                ->label(__('supervisor.view_in_calendar'))
                ->icon('heroicon-o-calendar-days')
                ->url(fn (): string => route('manage.calendar.index', [
                    'subdomain' => auth()->user()->academy?->subdomain,
                    'teacher_id' => $this->record->user_id,
                ]))
                ->openUrlInNewTab()
                ->visible(fn () => BaseSupervisorResource::canManageTeachers()),
        ];
    }
}
