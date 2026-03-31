<?php

namespace App\Filament\Academy\Resources\AcademicSessionResource\Pages;

use App\Filament\Academy\Resources\AcademicIndividualLessonResource;
use App\Filament\Academy\Resources\AcademicSessionResource;
use App\Filament\Academy\Resources\AcademicSubscriptionResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Shared\Actions\MeetingActions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewAcademicSession extends ViewRecord
{
    protected static string $resource = AcademicSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            MeetingActions::viewMeeting('academic'),
            Action::make('view_entity')
                ->label(__('sessions.actions.view_lesson'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('success')
                ->url(fn ($record) => AcademicIndividualLessonResource::getUrl('view', ['record' => $record->academic_individual_lesson_id]))
                ->openUrlInNewTab()
                ->visible(fn ($record) => (bool) $record->academic_individual_lesson_id),
            Action::make('view_subscription')
                ->label(__('sessions.actions.view_subscription'))
                ->icon('heroicon-o-credit-card')
                ->color('warning')
                ->url(fn ($record) => AcademicSubscriptionResource::getUrl('view', ['record' => $record->academic_subscription_id]))
                ->openUrlInNewTab()
                ->visible(fn ($record) => (bool) $record->academic_subscription_id),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
