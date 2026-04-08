<?php

namespace App\Filament\Supervisor\Resources\ManagedQuranTeachersResource\Pages;

use App\Filament\Supervisor\Resources\BaseSupervisorResource;
use App\Filament\Supervisor\Resources\ManagedQuranTeachersResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewManagedQuranTeacher extends ViewRecord
{
    protected static string $resource = ManagedQuranTeachersResource::class;

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
