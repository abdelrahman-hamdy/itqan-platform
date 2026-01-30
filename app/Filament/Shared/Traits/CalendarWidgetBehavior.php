<?php

declare(strict_types=1);

namespace App\Filament\Shared\Traits;

use App\Enums\CalendarSessionType;
use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use App\ValueObjects\CalendarEventId;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Shared calendar widget behavior trait
 *
 * Extracts common methods between UnifiedCalendarWidget and SupervisorCalendarWidget
 * to eliminate code duplication while allowing customization of:
 * - Target user (Auth::user() vs selected teacher)
 * - Session URLs (different panel paths)
 * - Session fetching strategy
 */
trait CalendarWidgetBehavior
{
    /**
     * Format sessions for FullCalendar display
     */
    protected function formatEventsForType(Collection|array $sessions, CalendarSessionType $type, string $timezone): array
    {
        $sessions = $sessions instanceof Collection ? $sessions : collect($sessions);

        return $sessions->map(function ($session) use ($type, $timezone) {
            $eventId = CalendarEventId::make($type, $session->id);
            $scheduledAt = $this->getScheduledAt($session)?->copy()->setTimezone($timezone);

            if (! $scheduledAt) {
                return null;
            }

            $duration = $session->duration_minutes ?? 60;
            $endTime = $scheduledAt->copy()->addMinutes($duration);
            // Compare against current time in the same timezone to avoid timezone mismatch
            $now = AcademyContextService::nowInAcademyTimezone();
            $isPassed = $scheduledAt->isBefore($now);

            $status = $this->getSessionStatus($session);
            $canEdit = ! $isPassed && ($status?->canReschedule() ?? true);

            $statusColor = $this->getEventColor($type, $status);

            return [
                'id' => $eventId->toString(),
                'title' => $this->getEventTitle($session, $type),
                'start' => $scheduledAt->format('Y-m-d\TH:i:s'),
                'end' => $endTime->format('Y-m-d\TH:i:s'),
                'backgroundColor' => '#ffffff',
                'borderColor' => $statusColor,
                'textColor' => '#1f2937',
                'editable' => $canEdit,
                'classNames' => $isPassed ? ['event-passed'] : [],
                'extendedProps' => [
                    'sessionType' => $type->value,
                    'typeIcon' => $type->icon(),
                    'statusColor' => $statusColor,
                    'status' => $status?->value,
                    'statusLabel' => $status?->label(),
                    'isPassed' => $isPassed,
                    'duration' => $duration,
                    'studentName' => $this->getStudentName($session, $type),
                    'subject' => $this->getSessionSubject($session, $type),
                ],
            ];
        })->filter()->values()->toArray();
    }

    /**
     * Get the scheduled_at time for a session
     */
    protected function getScheduledAt($session): ?Carbon
    {
        // InteractiveCourseSession might use scheduled_date + scheduled_time
        if ($session instanceof InteractiveCourseSession) {
            if ($session->scheduled_at) {
                return Carbon::parse($session->scheduled_at);
            }
            if ($session->scheduled_date && $session->scheduled_time) {
                return Carbon::parse($session->scheduled_date.' '.$session->scheduled_time);
            }

            return null;
        }

        return $session->scheduled_at ? Carbon::parse($session->scheduled_at) : null;
    }

    /**
     * Get the session status as a SessionStatus enum
     */
    protected function getSessionStatus($session): ?SessionStatus
    {
        $status = $session->status;

        if ($status instanceof SessionStatus) {
            return $status;
        }

        if (is_string($status)) {
            return SessionStatus::tryFrom($status);
        }

        return null;
    }

    /**
     * Get the event title for display
     *
     * Uses the dynamic calendar_title accessor from SessionNamingService
     * which automatically updates when session data changes (e.g., reschedule).
     */
    protected function getEventTitle($session, CalendarSessionType $type): string
    {
        // Use the new dynamic calendar title accessor
        // This provides consistent, audience-aware titles across the application
        if (method_exists($session, 'getCalendarTitleAttribute')) {
            return $session->calendar_title;
        }

        // Fallback to legacy behavior for non-BaseSession models
        $studentName = $this->getStudentName($session, $type);
        $typeLabel = $type->fallbackLabel();

        return "{$studentName} - {$typeLabel}";
    }

    /**
     * Get the student/circle name for display
     */
    protected function getStudentName($session, CalendarSessionType $type): string
    {
        return match ($type) {
            CalendarSessionType::QURAN_INDIVIDUAL => $session->student?->name ?? 'طالب',
            CalendarSessionType::QURAN_GROUP => $session->circle?->name ?? 'حلقة',
            CalendarSessionType::QURAN_TRIAL => $session->trialRequest?->student_name ?? $session->student?->name ?? 'تجريبي',
            CalendarSessionType::ACADEMIC_PRIVATE => $session->student?->name ?? 'طالب',
            CalendarSessionType::INTERACTIVE_COURSE => $session->course?->title ?? 'دورة',
        };
    }

    /**
     * Get the subject name for academic sessions
     */
    protected function getSessionSubject($session, CalendarSessionType $type): ?string
    {
        return match ($type) {
            CalendarSessionType::ACADEMIC_PRIVATE => $session->academicIndividualLesson?->subject?->name,
            CalendarSessionType::INTERACTIVE_COURSE => $session->course?->subject?->name,
            default => null,
        };
    }

    /**
     * Get the event color based on status (primary) or type (fallback)
     */
    protected function getEventColor(CalendarSessionType $type, ?SessionStatus $status): string
    {
        // Always use status-based colors when status is available
        if ($status) {
            return $status->hexColor();
        }

        // Fallback to type-based colors only when no status
        return $type->hexColor();
    }

    /**
     * Get the session type from a model
     */
    protected function getSessionTypeFromModel(Model $model): CalendarSessionType
    {
        return match (true) {
            $model instanceof QuranSession => CalendarSessionType::fromQuranSession($model),
            $model instanceof AcademicSession => CalendarSessionType::ACADEMIC_PRIVATE,
            $model instanceof InteractiveCourseSession => CalendarSessionType::INTERACTIVE_COURSE,
            default => CalendarSessionType::QURAN_INDIVIDUAL,
        };
    }

    /**
     * Resolve a record from event ID for modal actions
     */
    public function resolveRecord(int|string $key): Model
    {
        $eventId = CalendarEventId::fromString((string) $key);

        return $eventId->resolve();
    }

    /**
     * Resolve record from action arguments
     */
    protected function resolveRecordFromArguments(array $arguments): ?Model
    {
        $eventId = $arguments['event']['id'] ?? null;

        if (! $eventId) {
            return null;
        }

        try {
            return $this->resolveRecord($eventId);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the modal heading for a session
     */
    protected function getRecordModalHeading(?Model $record): string
    {
        if (! $record) {
            return 'تفاصيل الجلسة';
        }

        $type = $this->getSessionTypeFromModel($record);

        return 'تفاصيل الجلسة - '.$type->fallbackLabel();
    }

    /**
     * Custom event content - renders icons inside events with status colors
     * Uses CSS classes for dark mode support instead of inline colors
     */
    public function eventContent(): string
    {
        return <<<'JS'
            function(arg) {
                const props = arg.event.extendedProps || {};
                const typeIcon = props.typeIcon || '';
                const statusColor = props.statusColor || '#3B82F6';
                const title = arg.event.title || '';
                const timeText = arg.timeText || '';

                // Map heroicon names to SVG paths
                const iconPaths = {
                    'heroicon-m-user': '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />',
                    'heroicon-m-user-group': '<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />',
                    'heroicon-m-clock': '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
                    'heroicon-m-academic-cap': '<path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />',
                    'heroicon-m-play-circle': '<path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 0 1 0 .656l-5.603 3.113a.375.375 0 0 1-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112Z" />'
                };

                const iconPath = iconPaths[typeIcon] || iconPaths['heroicon-m-user'];

                // Create the HTML content - text color is handled by CSS for dark mode support
                const html = `
                    <div class="fc-event-main-frame" style="display: flex; align-items: center; gap: 4px; padding: 2px 4px; overflow: hidden;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="${statusColor}" class="fc-event-icon" style="width: 14px; height: 14px; flex-shrink: 0;">
                            ${iconPath}
                        </svg>
                        <div class="fc-event-content" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1;">
                            ${timeText ? `<span class="fc-event-time" style="margin-left: 4px;">${timeText}</span>` : ''}
                            <span class="fc-event-title" style="font-weight: 500;">${title}</span>
                        </div>
                    </div>
                `;

                return { html: html };
            }
        JS;
    }

    /**
     * CSS class names for events based on status
     */
    public function eventClassNames(): string
    {
        return <<<'JS'
            function(arg) {
                const classes = [];
                if (arg.event.extendedProps && arg.event.extendedProps.isPassed) {
                    classes.push("event-passed");
                }
                if (arg.event.extendedProps && arg.event.extendedProps.status === 'ongoing') {
                    classes.push("event-ongoing");
                }
                return classes;
            }
        JS;
    }

    /**
     * Refresh the calendar
     */
    public function refreshCalendar(): void
    {
        $this->dispatch('filament-fullcalendar--refresh');
    }
}
