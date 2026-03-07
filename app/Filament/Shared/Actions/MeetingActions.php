<?php

namespace App\Filament\Shared\Actions;

use Filament\Actions\Action;

class MeetingActions
{
    /**
     * Create a "View Meeting" action that links to the frontend session page.
     *
     * @param  string  $sessionType  One of: 'quran', 'academic', 'interactive'
     * @param  bool  $forTeacher  When true, links to the teacher frontend session page instead of monitoring
     */
    public static function viewMeeting(string $sessionType, bool $forTeacher = false): Action
    {
        return Action::make('view_meeting')
            ->label(__('supervisor.observation.view_meeting'))
            ->icon('heroicon-o-video-camera')
            ->color('info')
            ->url(function ($record) use ($sessionType, $forTeacher): string {
                $subdomain = $record->academy?->subdomain
                    ?? ($sessionType === 'interactive'
                        ? $record->course?->academy?->subdomain
                        : null)
                    ?? request()->route('subdomain')
                    ?? 'itqan-academy';

                if ($forTeacher) {
                    $routeName = match ($sessionType) {
                        'quran' => 'teacher.sessions.show',
                        'academic' => 'teacher.academic-sessions.show',
                        'interactive' => 'teacher.interactive-sessions.show',
                    };
                    $paramName = $sessionType === 'quran' ? 'sessionId' : 'session';

                    return route($routeName, [
                        'subdomain' => $subdomain,
                        $paramName => $record->id,
                    ]);
                }

                return route('sessions.monitoring.show', [
                    'subdomain' => $subdomain,
                    'sessionType' => $sessionType,
                    'sessionId' => $record->id,
                ]);
            })
            ->openUrlInNewTab();
    }
}
