<?php

declare(strict_types=1);

namespace App\Filament\Shared\Widgets;

use App\Enums\CalendarSessionType;
use App\Enums\SessionDuration;
use App\Filament\Shared\Traits\CalendarWidgetBehavior;
use App\Filament\Shared\Traits\FormatsCalendarData;
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
    use CalendarWidgetBehavior;
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

            // Time configuration (from config/calendar.php)
            'slotMinTime' => config('calendar.time_slice.start', '06:00').':00',
            'slotMaxTime' => config('calendar.time_slice.end', '23:00').':00',
            'scrollTime' => config('calendar.scroll_time', '08:00:00'),
            'slotDuration' => config('calendar.slot_duration', '00:30:00'),
            'nowIndicator' => true,

            // Week configuration (from config/calendar.php)
            'firstDay' => config('calendar.first_day', 6),
            'weekNumbers' => config('calendar.show_week_numbers', true),
            'weekends' => config('calendar.show_weekends', true),

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

            // Business hours (from config/calendar.php)
            'businessHours' => [
                'daysOfWeek' => config('calendar.business_hours.days_of_week', [6, 0, 1, 2, 3, 4, 5]),
                'startTime' => config('calendar.business_hours.start', '08:00'),
                'endTime' => config('calendar.business_hours.end', '22:00'),
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
            // Parse and explicitly convert to academy timezone for consistent comparison
            $newStart = Carbon::parse($event['start'])->setTimezone($timezone);
            $newEnd = Carbon::parse($event['end'] ?? $event['start'])->setTimezone($timezone);

            // Debug logging
            $now = AcademyContextService::nowInAcademyTimezone();
            \Log::info('Calendar drop debug', [
                'raw_start' => $event['start'],
                'parsed_start' => $newStart->format('Y-m-d H:i:s T'),
                'now' => $now->format('Y-m-d H:i:s T'),
                'is_before' => $newStart->isBefore($now),
            ]);

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
            // Parse and explicitly convert to academy timezone for consistent comparison
            $newStart = Carbon::parse($event['start'])->setTimezone($timezone);
            $newEnd = Carbon::parse($event['end'])->setTimezone($timezone);

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

                    return 'جلسات '.$date->translatedFormat('l').' - '.$date->format('d/m/Y');
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
                // Compare against current time in the same timezone to avoid timezone mismatch
                $now = AcademyContextService::nowInAcademyTimezone();

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
                    'isPassed' => $scheduledAt->isBefore($now),
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
                $now = AcademyContextService::nowInAcademyTimezone();
                $canEdit = $record && $scheduledAt && ! $scheduledAt->isBefore($now);

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
                                    for ($hour = 0; $hour <= 23; $hour++) {
                                        foreach (['00', '30'] as $minute) {
                                            $time = sprintf('%02d:%s', $hour, $minute);
                                            $hour12 = $hour > 12 ? $hour - 12 : ($hour == 0 ? 12 : $hour);
                                            $period = $hour >= 12 ? 'م' : 'ص';
                                            $display = sprintf('%d:%s %s', $hour12, $minute, $period);
                                            $options[$time] = $display;
                                        }
                                    }
                                    return $options;
                                })
                                ->searchable()
                                ->optionsLimit(50)
                                ->native(false),
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

                // Build the new scheduled_at datetime in the academy timezone
                // Note: Do NOT convert to UTC - Eloquent handles timezone based on APP_TIMEZONE
                $newScheduledAt = Carbon::parse(
                    $data['scheduled_date'].' '.$data['scheduled_time'],
                    $timezone
                );

                // Check if the new time is in the past (compare in same timezone)
                $now = AcademyContextService::nowInAcademyTimezone();

                // Debug logging
                \Log::info('Quick edit debug', [
                    'input_date' => $data['scheduled_date'],
                    'input_time' => $data['scheduled_time'],
                    'timezone' => $timezone,
                    'parsed_datetime' => $newScheduledAt->format('Y-m-d H:i:s T'),
                    'now' => $now->format('Y-m-d H:i:s T'),
                    'is_before' => $newScheduledAt->isBefore($now),
                ]);

                if ($newScheduledAt->isBefore($now)) {
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
