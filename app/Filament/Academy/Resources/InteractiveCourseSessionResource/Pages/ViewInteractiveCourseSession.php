<?php

namespace App\Filament\Academy\Resources\InteractiveCourseSessionResource\Pages;

use App\Filament\Academy\Resources\InteractiveCourseSessionResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Resources\InteractiveCourseResource;
use App\Filament\Shared\Actions\MeetingActions;
use App\Filament\Shared\Resources\BaseInteractiveCourseSessionResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewInteractiveCourseSession extends ViewRecord
{
    protected static string $resource = InteractiveCourseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            MeetingActions::viewMeeting('interactive'),
            Action::make('view_entity')
                ->label(__('sessions.actions.view_course'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('success')
                ->url(fn ($record) => InteractiveCourseResource::getUrl('edit', ['record' => $record->course_id]))
                ->openUrlInNewTab()
                ->visible(fn ($record) => (bool) $record->course_id),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }

    protected function getFooterSchemas(): array
    {
        return [
            BaseInteractiveCourseSessionResource::getRecordingSection(),
        ];
    }
}
