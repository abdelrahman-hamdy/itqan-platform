<?php

namespace App\Filament\Academy\Resources\QuranSessionResource\Pages;

use App\Filament\Academy\Resources\QuranIndividualCircleResource;
use App\Filament\Academy\Resources\QuranSessionResource;
use App\Filament\Academy\Resources\QuranSubscriptionResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Resources\QuranCircleResource;
use App\Filament\Shared\Actions\MeetingActions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewQuranSession extends ViewRecord
{
    protected static string $resource = QuranSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            MeetingActions::viewMeeting('quran'),
            Action::make('view_entity')
                ->label(fn ($record) => $record->session_type === 'individual'
                    ? __('sessions.actions.view_individual_circle')
                    : __('sessions.actions.view_circle'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('success')
                ->url(fn ($record) => $record->session_type === 'individual'
                    ? QuranIndividualCircleResource::getUrl('view', ['record' => $record->individual_circle_id])
                    : QuranCircleResource::getUrl('view', ['record' => $record->circle_id]))
                ->openUrlInNewTab()
                ->visible(fn ($record) => ($record->session_type === 'individual' && $record->individual_circle_id)
                    || ($record->session_type !== 'individual' && $record->circle_id)),
            Action::make('view_subscription')
                ->label(__('sessions.actions.view_subscription'))
                ->icon('heroicon-o-credit-card')
                ->color('warning')
                ->url(fn ($record) => QuranSubscriptionResource::getUrl('view', ['record' => $record->quran_subscription_id]))
                ->openUrlInNewTab()
                ->visible(fn ($record) => (bool) $record->quran_subscription_id),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
