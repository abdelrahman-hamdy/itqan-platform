<?php

namespace App\Filament\Shared\Actions;

use Filament\Actions\Action;

class MeetingActions
{
    /**
     * Create a "View Meeting" action that links to the frontend monitoring page.
     */
    public static function viewMeeting(string $sessionType): Action
    {
        return Action::make('view_meeting')
            ->label(__('supervisor.observation.view_meeting'))
            ->icon('heroicon-o-video-camera')
            ->color('info')
            ->url(function ($record) use ($sessionType): string {
                $subdomain = $record->academy?->subdomain
                    ?? ($sessionType === 'interactive'
                        ? $record->course?->academy?->subdomain
                        : null)
                    ?? request()->route('subdomain')
                    ?? 'itqan-academy';

                return route('sessions.monitoring.show', [
                    'subdomain' => $subdomain,
                    'sessionType' => $sessionType,
                    'sessionId' => $record->id,
                ]);
            })
            ->openUrlInNewTab();
    }
}
