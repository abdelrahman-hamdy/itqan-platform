<?php

declare(strict_types=1);

namespace App\Filament\Shared\Widgets;

use App\Enums\CalendarSessionType;
use App\Enums\SessionDuration;
use App\Enums\SessionStatus;
use App\Filament\Shared\Traits\FormatsCalendarData;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use App\Services\Calendar\CalendarConfiguration;
use App\Services\Calendar\CalendarEventHandler;
use App\Services\Calendar\EventFetchingService;
use App\ValueObjects\CalendarEventId;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

/**
 * Unified Calendar Widget
 *
 * A single, configurable calendar widget that replaces:
 * - TeacherCalendarWidget (1,215 lines)
 * - AcademicFullCalendarWidget (504 lines)
 *
 * Key improvements:
 * - Uses CalendarConfiguration for role-based session types
 * - Uses EventFetchingService for data (service layer)
 * - Uses CalendarEventHandler for drag/drop/resize (consolidates ~500 lines)
 * - Uses CalendarEventId for type-safe event ID handling
 * - Unified timezone handling via AcademyContextService
 *
 * @see \App\Services\Calendar\CalendarConfiguration
 * @see \App\Services\Calendar\CalendarEventHandler
 * @see \App\Services\Calendar\EventFetchingService
 */
class UnifiedCalendarWidget extends FullCalendarWidget
{
    use FormatsCalendarData;

    /**
     * Widget spans full width
     */
    protected int|string|array $columnSpan = 'full';

    /**
     * Widget sort order
     */
    protected static ?int $sort = 1;

    /**
     * State for day sessions modal
     */
    public ?string $selectedDate = null;

    public array $daySessions = [];

    /**
     * State for edit session modal
     */
    public ?string $editingEventId = null;

    /**
     * Event listeners for real-time updates
     */
    protected $listeners = [
        'refresh-calendar' => 'refreshCalendar',
        'triggerEditAction' => 'handleTriggerEditAction',
    ];

    /**
     * Configuration instance
     */
    protected ?CalendarConfiguration $configuration = null;

    /**
     * Services (injected via boot)
     */
    protected EventFetchingService $fetchingService;

    protected CalendarEventHandler $eventHandler;

    /**
     * Boot the widget - inject dependencies
     */
    public function boot(): void
    {
        $this->fetchingService = app(EventFetchingService::class);
        $this->eventHandler = app(CalendarEventHandler::class);
        $this->configuration = $this->buildConfiguration();
    }

    /**
     * Register modal actions that can be mounted
     * This is required for mountAction() to find the actions
     */
    protected function modalActions(): array
    {
        return [
            $this->editAction(),
            $this->daySessionsAction(),
        ];
    }

    /**
     * Build configuration based on authenticated user's role
     */
    protected function buildConfiguration(): CalendarConfiguration
    {
        $user = Auth::user();

        if (! $user) {
            throw new \RuntimeException('User must be authenticated to access calendar');
        }

        return CalendarConfiguration::forUser($user);
    }

    /**
     * Get the configuration (allows external access for color legend)
     */
    public function getConfiguration(): CalendarConfiguration
    {
        return $this->configuration ?? $this->buildConfiguration();
    }

    /**
     * Calendar configuration for FullCalendar
     */
    public function config(): array
    {
        $timezone = AcademyContextService::getTimezone();
        $config = $this->getConfiguration();

        return [
            // Timezone - CRITICAL for correct time display
            'timeZone' => $timezone,
            'locale' => 'ar',
            'direction' => 'rtl',

            // Calendar views
            'initialView' => 'dayGridMonth',
            'headerToolbar' => [
                'start' => 'prev,next today',
                'center' => 'title',
                'end' => 'dayGridMonth,timeGridWeek,timeGridDay',
            ],

            // Time configuration
            'slotMinTime' => '06:00:00',
            'slotMaxTime' => '23:00:00',
            'scrollTime' => '08:00:00',
            'slotDuration' => '00:30:00',
            'nowIndicator' => true,

            // Week configuration (Saturday start for Arabic)
            'firstDay' => 6,
            'weekNumbers' => true,
            'weekends' => true,

            // Event display
            'dayMaxEvents' => true,
            'allDaySlot' => false,
            'eventTimeFormat' => [
                'hour' => '2-digit',
                'minute' => '2-digit',
                'meridiem' => 'short',
                'hour12' => true,
            ],

            // Interactivity
            'editable' => $config->allowDragDrop,
            'eventStartEditable' => $config->allowDragDrop,
            'eventDurationEditable' => $config->allowResize,
            'eventOverlap' => false,
            'selectable' => false,

            // Layout
            'height' => 'auto',
            'expandRows' => true,

            // Business hours
            'businessHours' => [
                'daysOfWeek' => [6, 0, 1, 2, 3, 4, 5],
                'startTime' => '08:00',
                'endTime' => '22:00',
            ],
        ];
    }

    /**
     * Fetch events for the calendar
     *
     * @param  array  $fetchInfo  Contains start, end, timezone from FullCalendar
     * @return array Events in FullCalendar format
     */
    public function fetchEvents(array $fetchInfo): array
    {
        $user = Auth::user();
        if (! $user) {
            return [];
        }

        $timezone = AcademyContextService::getTimezone();
        $startDate = Carbon::parse($fetchInfo['start']);
        $endDate = Carbon::parse($fetchInfo['end']);

        $events = collect();

        foreach ($this->getConfiguration()->getSessionTypes() as $sessionType) {
            $sessions = $this->fetchSessionsForType($user, $sessionType, $startDate, $endDate);
            $formattedEvents = $this->formatEventsForType($sessions, $sessionType, $timezone);
            $events = $events->merge($formattedEvents);
        }

        return $events->toArray();
    }

    /**
     * Fetch sessions for a specific type
     */
    protected function fetchSessionsForType($user, CalendarSessionType $type, Carbon $start, Carbon $end)
    {
        return match ($type) {
            CalendarSessionType::QURAN_INDIVIDUAL => $this->fetchingService->getQuranIndividualSessions($user, $start, $end),
            CalendarSessionType::QURAN_GROUP => $this->fetchingService->getQuranGroupSessions($user, $start, $end),
            CalendarSessionType::QURAN_TRIAL => $this->fetchingService->getTrialSessions($user, $start, $end),
            CalendarSessionType::ACADEMIC_PRIVATE => $this->fetchingService->getAcademicSessions($user, $start, $end),
            CalendarSessionType::INTERACTIVE_COURSE => $this->fetchingService->getCourseSessions($user, $start, $end),
        };
    }

    /**
     * Format sessions for FullCalendar display
     */
    protected function formatEventsForType($sessions, CalendarSessionType $type, string $timezone): array
    {
        return $sessions->map(function ($session) use ($type, $timezone) {
            $eventId = CalendarEventId::make($type, $session->id);
            $scheduledAt = $this->getScheduledAt($session)?->copy()->setTimezone($timezone);

            if (! $scheduledAt) {
                return null;
            }

            $duration = $session->duration_minutes ?? 60;
            $endTime = $scheduledAt->copy()->addMinutes($duration);
            $isPassed = $scheduledAt->isPast();

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
     */
    protected function getEventTitle($session, CalendarSessionType $type): string
    {
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
            CalendarSessionType::QURAN_GROUP => $session->circle?->name_ar ?? $session->circle?->name ?? 'حلقة',
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
            CalendarSessionType::ACADEMIC_PRIVATE => $session->academicIndividualLesson?->subject?->name_ar
                ?? $session->academicIndividualLesson?->subject?->name,
            CalendarSessionType::INTERACTIVE_COURSE => $session->course?->subject?->name_ar
                ?? $session->course?->subject?->name,
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
     * Handle event drop (drag to reschedule)
     */
    public function onEventDrop(
        array $event,
        array $oldEvent,
        array $relatedEvents,
        array $delta,
        ?array $oldResource,
        ?array $newResource
    ): bool {
        $timezone = AcademyContextService::getTimezone();

        try {
            $eventId = CalendarEventId::fromString($event['id']);
            $newStart = Carbon::parse($event['start'], $timezone);
            $newEnd = Carbon::parse($event['end'] ?? $event['start'], $timezone);

            $result = $this->eventHandler->handleEventDrop(
                $eventId,
                $newStart,
                $newEnd,
                $this->getConfiguration()
            );

            if ($result->isSuccess()) {
                Notification::make()
                    ->title('تم التحديث')
                    ->body($result->getMessage())
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title($result->getErrorTitle())
                    ->body($result->getMessage())
                    ->warning()
                    ->send();
            }

            return $result->shouldRevert();
        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ')
                ->body('حدث خطأ أثناء تحديث الموعد')
                ->danger()
                ->send();

            return true; // Revert on error
        }
    }

    /**
     * Handle event resize (duration change)
     */
    public function onEventResize(
        array $event,
        array $oldEvent,
        array $relatedEvents,
        array $startDelta,
        array $endDelta
    ): bool {
        $timezone = AcademyContextService::getTimezone();

        try {
            $eventId = CalendarEventId::fromString($event['id']);
            $newStart = Carbon::parse($event['start'], $timezone);
            $newEnd = Carbon::parse($event['end'], $timezone);

            $result = $this->eventHandler->handleEventResize(
                $eventId,
                $newStart,
                $newEnd,
                $this->getConfiguration()
            );

            if ($result->isSuccess()) {
                Notification::make()
                    ->title('تم التحديث')
                    ->body($result->getMessage())
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title($result->getErrorTitle())
                    ->body($result->getMessage())
                    ->warning()
                    ->send();
            }

            return $result->shouldRevert();
        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ')
                ->body('حدث خطأ أثناء تحديث المدة')
                ->danger()
                ->send();

            return true; // Revert on error
        }
    }

    /**
     * Show day sessions modal when a day is clicked
     */
    public function showDaySessionsModal(string $dateStr): void
    {
        $timezone = AcademyContextService::getTimezone();
        $date = Carbon::parse($dateStr, $timezone);

        $this->selectedDate = $date->format('Y-m-d');
        $this->daySessions = $this->getDaySessionsData($date);

        // Mount the daySessions action with the date
        $this->mountAction('daySessions', ['date' => $dateStr]);
    }

    /**
     * Day sessions action - shows all sessions for a specific day
     */
    protected function daySessionsAction(): Action
    {
        return Action::make('daySessions')
            ->label('جلسات اليوم')
            ->modalHeading(function () {
                $timezone = AcademyContextService::getTimezone();
                if ($this->selectedDate) {
                    $date = Carbon::parse($this->selectedDate, $timezone);
                    return 'جلسات ' . $date->translatedFormat('l') . ' - ' . $date->format('d/m/Y');
                }
                return 'جلسات اليوم';
            })
            ->modalContent(function () {
                return view('filament.shared.widgets.day-sessions-modal', [
                    'sessions' => $this->daySessions,
                    'date' => $this->selectedDate,
                    'widgetId' => $this->getId(),
                ]);
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('إغلاق')
            ->modalWidth('3xl');
    }

    /**
     * Get session data for a specific day
     */
    protected function getDaySessionsData(Carbon $date): array
    {
        $user = Auth::user();
        if (! $user) {
            return [];
        }

        $timezone = AcademyContextService::getTimezone();
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $sessions = collect();

        foreach ($this->getConfiguration()->getSessionTypes() as $type) {
            $typeSessions = $this->fetchSessionsForType($user, $type, $startOfDay, $endOfDay);

            foreach ($typeSessions as $session) {
                $eventId = CalendarEventId::make($type, $session->id);
                $scheduledAt = $this->getScheduledAt($session)?->copy()->setTimezone($timezone);

                if (! $scheduledAt) {
                    continue;
                }

                $status = $this->getSessionStatus($session);

                $sessions->push([
                    'eventId' => $eventId->toString(),
                    'type' => $type->value,
                    'sessionType' => $type->fallbackLabel(),
                    'studentName' => $this->getStudentName($session, $type),
                    'time' => $scheduledAt->format('g:i a'),
                    'duration' => $session->duration_minutes ?? 60,
                    'color' => $this->getEventColor($type, $status),
                    'status' => $status?->value,
                    'statusLabel' => $status?->label(),
                    'statusColor' => $status?->hexColor(),
                    'isPassed' => $scheduledAt->isPast(),
                    'canEdit' => $status?->canReschedule() ?? true,
                    'subject' => $this->getSessionSubject($session, $type),
                    'icon' => $type->icon(),
                    'sessionUrl' => $this->getSessionUrl($session),
                ]);
            }
        }

        return $sessions->sortBy('time')->values()->toArray();
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
     * Handle event click - override parent to resolve multi-model records
     */
    public function onEventClick(array $event): void
    {
        // Always resolve the record using our CalendarEventId logic
        // (parent only resolves if getModel() is set, but we handle multiple models)
        try {
            $this->record = $this->resolveRecord($event['id']);
        } catch (\Exception $e) {
            $this->record = null;
        }

        $this->mountAction('view', [
            'type' => 'click',
            'event' => $event,
        ]);
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
     * View action for event modal
     */
    protected function viewAction(): Action
    {
        return Action::make('view')
            ->label('عرض التفاصيل')
            ->icon('heroicon-o-eye')
            ->modalHeading(function (array $arguments) {
                $record = $this->resolveRecordFromArguments($arguments);
                return $record ? $this->getRecordModalHeading($record) : 'تفاصيل الجلسة';
            })
            ->modalContent(function (array $arguments) {
                $record = $this->resolveRecordFromArguments($arguments);
                $eventId = $arguments['event']['id'] ?? null;

                if (! $record) {
                    return view('filament.shared.widgets.session-not-found');
                }
                return view('filament.shared.widgets.session-details', [
                    'session' => $record,
                    'type' => $this->getSessionTypeFromModel($record),
                    'sessionUrl' => $this->getSessionUrl($record),
                    'eventId' => $eventId,
                ]);
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('إغلاق')
            ->extraModalFooterActions(function (array $arguments): array {
                $record = $this->resolveRecordFromArguments($arguments);
                $eventId = $arguments['event']['id'] ?? null;
                $timezone = AcademyContextService::getTimezone();
                $scheduledAt = $record?->scheduled_at?->copy()->setTimezone($timezone);
                $canEdit = $record && $scheduledAt && !$scheduledAt->isPast();

                $actions = [];

                // Edit button - close modal first, then open edit after animation completes
                if ($canEdit && $eventId) {
                    $escapedEventId = addslashes($eventId);
                    $widgetId = $this->getId();
                    $actions[] = Action::make('editSession')
                        ->label('تعديل سريع')
                        ->icon('heroicon-m-pencil-square')
                        ->color('primary')
                        ->alpineClickHandler("
                            const wireComponent = Livewire.find('{$widgetId}');
                            \$dispatch('close-modal', { id: '{$widgetId}-action' });
                            setTimeout(() => {
                                wireComponent.call('openEditDialog', '{$escapedEventId}');
                            }, 400);
                        ");
                }

                // Open in new tab button
                if ($record) {
                    $sessionUrl = $this->getSessionUrl($record);
                    if ($sessionUrl) {
                        $actions[] = Action::make('openInNewTab')
                            ->label('فتح في صفحة جديدة')
                            ->icon('heroicon-m-arrow-top-right-on-square')
                            ->color('gray')
                            ->url($sessionUrl, shouldOpenInNewTab: true);
                    }
                }

                return $actions;
            });
    }

    /**
     * Edit action for inline session editing
     */
    protected function editAction(): Action
    {
        return Action::make('edit')
            ->label('تعديل الجلسة')
            ->icon('heroicon-o-pencil-square')
            ->modalHeading('تعديل الجلسة')
            ->modalDescription('تعديل المعلومات الأساسية للجلسة')
            ->form(function (array $arguments): array {
                $record = $this->resolveRecordFromArguments($arguments);
                $timezone = AcademyContextService::getTimezone();
                $scheduledAt = $record?->scheduled_at?->copy()->setTimezone($timezone);

                return [
                    Forms\Components\TextInput::make('title')
                        ->label('عنوان الجلسة')
                        ->default($record?->title)
                        ->maxLength(255),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\DatePicker::make('scheduled_date')
                                ->label('تاريخ الجلسة')
                                ->default($scheduledAt?->format('Y-m-d'))
                                ->required()
                                ->native(false)
                                ->displayFormat('Y/m/d')
                                ->closeOnDateSelection(),

                            Forms\Components\Select::make('scheduled_time')
                                ->label('وقت الجلسة')
                                ->default($scheduledAt?->format('H:i'))
                                ->required()
                                ->options(function () {
                                    $options = [];
                                    $intervals = ['00', '15', '30', '45'];

                                    for ($hour = 6; $hour <= 23; $hour++) {
                                        $hour12 = $hour > 12 ? $hour - 12 : ($hour == 0 ? 12 : $hour);
                                        $period = $hour >= 12 ? 'م' : 'ص';

                                        foreach ($intervals as $minute) {
                                            $time = sprintf('%02d:%s', $hour, $minute);
                                            $display = sprintf('%d:%s %s', $hour12, $minute, $period);
                                            $options[$time] = $display;
                                        }
                                    }
                                    return $options;
                                })
                                ->searchable(),
                        ]),

                    Forms\Components\Select::make('duration_minutes')
                        ->label('مدة الجلسة')
                        ->default($record?->duration_minutes ?? 60)
                        ->required()
                        ->options(SessionDuration::options()),
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $record = $this->resolveRecordFromArguments($arguments);

                if (! $record) {
                    Notification::make()
                        ->title('خطأ')
                        ->body('لم يتم العثور على الجلسة')
                        ->danger()
                        ->send();
                    return;
                }

                $timezone = AcademyContextService::getTimezone();

                // Build the new scheduled_at datetime
                $newScheduledAt = Carbon::parse(
                    $data['scheduled_date'] . ' ' . $data['scheduled_time'],
                    $timezone
                )->utc();

                // Check if the new time is in the past
                if ($newScheduledAt->isPast()) {
                    Notification::make()
                        ->title('خطأ')
                        ->body('لا يمكن جدولة جلسة في وقت ماضي')
                        ->danger()
                        ->send();
                    return;
                }

                // Update the session
                $record->update([
                    'title' => $data['title'] ?? $record->title,
                    'scheduled_at' => $newScheduledAt,
                    'duration_minutes' => $data['duration_minutes'] ?? $record->duration_minutes,
                ]);

                Notification::make()
                    ->title('تم التحديث')
                    ->body('تم تحديث الجلسة بنجاح')
                    ->success()
                    ->send();
            })
            ->after(fn () => $this->refreshRecords())
            ->modalSubmitActionLabel('حفظ التغييرات')
            ->modalCancelActionLabel('إلغاء');
    }

    /**
     * Open edit dialog for a session (called from JS)
     */
    public function openEditDialog(string $eventId): void
    {
        $this->editingEventId = $eventId;

        // Mount the edit action in a fresh request cycle
        $this->mountAction('edit', [
            'event' => ['id' => $eventId],
        ]);
    }

    /**
     * Handle trigger edit action event (kept for backwards compatibility)
     */
    public function handleTriggerEditAction(string $eventId): void
    {
        $this->openEditDialog($eventId);
    }

    /**
     * Get the URL to view a session's full page
     */
    protected function getSessionUrl(Model $record): ?string
    {
        $type = $this->getSessionTypeFromModel($record);

        // Get the current panel ID to construct proper URL
        $panelId = filament()->getCurrentPanel()?->getId() ?? 'teacher';

        return match ($type) {
            CalendarSessionType::QURAN_INDIVIDUAL,
            CalendarSessionType::QURAN_GROUP,
            CalendarSessionType::QURAN_TRIAL => "/{$panelId}-panel/quran-sessions/{$record->id}",
            CalendarSessionType::ACADEMIC_PRIVATE => "/{$panelId}-panel/academic-sessions/{$record->id}",
            CalendarSessionType::INTERACTIVE_COURSE => "/{$panelId}-panel/interactive-course-sessions/{$record->id}",
            default => null,
        };
    }

    /**
     * Get the URL to edit a session (Filament resource)
     */
    protected function getSessionEditUrl(Model $record): ?string
    {
        $type = $this->getSessionTypeFromModel($record);

        // Get the current panel ID to construct proper URL
        $panelId = filament()->getCurrentPanel()?->getId() ?? 'teacher';

        return match ($type) {
            CalendarSessionType::QURAN_INDIVIDUAL,
            CalendarSessionType::QURAN_GROUP,
            CalendarSessionType::QURAN_TRIAL => "/{$panelId}-panel/quran-sessions/{$record->id}/edit",
            CalendarSessionType::ACADEMIC_PRIVATE => "/{$panelId}-panel/academic-sessions/{$record->id}/edit",
            CalendarSessionType::INTERACTIVE_COURSE => "/{$panelId}-panel/interactive-course-sessions/{$record->id}/edit",
            default => null,
        };
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
     * Get color legend data for display
     *
     * @return array{sessionTypes: array, statusIndicators: array}
     */
    public function getColorLegendData(): array
    {
        $sessionTypes = collect($this->getConfiguration()->getSessionTypes())
            ->map(fn ($type) => [
                'value' => $type->value,
                'color' => $type->hexColor(),
                'label' => $type->fallbackLabel(),
                'icon' => $type->icon(),
            ])->toArray();

        $statusIndicators = $this->getStatusColorIndicators();

        return [
            'sessionTypes' => $sessionTypes,
            'statusIndicators' => $statusIndicators,
        ];
    }

    /**
     * Refresh the calendar
     */
    public function refreshCalendar(): void
    {
        $this->dispatch('filament-fullcalendar--refresh');
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
     * Event did mount - add click handlers for day cells
     */
    public function eventDidMount(): string
    {
        $widgetId = $this->getId();

        return <<<JS
            function(info) {
                const calendarEl = info.el.closest('.filament-fullcalendar');

                // Initialize click handlers only once
                if (calendarEl && !calendarEl.hasAttribute('data-click-initialized')) {
                    calendarEl.setAttribute('data-click-initialized', 'true');

                    // Style day numbers as clickable
                    const dayNumbers = calendarEl.querySelectorAll('.fc-daygrid-day-number');
                    dayNumbers.forEach(dayNum => {
                        dayNum.style.cursor = 'pointer';
                        dayNum.style.color = '#3b82f6';
                    });

                    // Add click handler for day cells (NOT events)
                    calendarEl.addEventListener('click', function(e) {
                        // Skip if click is on an event
                        if (e.target.closest('.fc-event')) return;

                        // Find the date cell that was clicked
                        const dateCell = e.target.closest('.fc-daygrid-day, .fc-timegrid-col, .fc-timegrid-slot');
                        if (!dateCell) return;

                        // Extract date from cell
                        let dateStr = dateCell.dataset.date;
                        if (!dateStr && dateCell.classList.contains('fc-timegrid-slot')) {
                            const col = dateCell.closest('.fc-timegrid-col');
                            dateStr = col?.dataset.date;
                        }

                        if (!dateStr) return;

                        // Call Livewire method to show day sessions modal
                        window.Livewire.find('{$widgetId}').call('showDaySessionsModal', dateStr);
                    });
                }
            }
        JS;
    }
}
