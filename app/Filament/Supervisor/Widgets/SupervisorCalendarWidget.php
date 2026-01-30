<?php

declare(strict_types=1);

namespace App\Filament\Supervisor\Widgets;

use App\Enums\CalendarSessionType;
use App\Enums\SessionDuration;
use App\Filament\Shared\Traits\CalendarWidgetBehavior;
use App\Filament\Shared\Traits\FormatsCalendarData;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\AcademyContextService;
use App\Services\Calendar\CalendarConfiguration;
use App\Services\Calendar\CalendarEventHandler;
use App\ValueObjects\CalendarEventId;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

/**
 * Supervisor Calendar Widget
 *
 * Displays a calendar for a specific teacher that the supervisor manages.
 * Allows viewing and managing the teacher's sessions.
 */
class SupervisorCalendarWidget extends FullCalendarWidget
{
    use CalendarWidgetBehavior;
    use FormatsCalendarData;

    protected static bool $isDiscoverable = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    // Target teacher ID and type (passed from page)
    public ?int $selectedTeacherId = null;

    public ?string $selectedTeacherType = null;

    // State for modals
    public ?string $selectedDate = null;

    public array $daySessions = [];

    public ?string $editingEventId = null;

    protected $listeners = [
        'refresh-calendar' => 'refreshCalendar',
        'teacherSelected' => 'handleTeacherSelected',
    ];

    protected ?CalendarConfiguration $configuration = null;

    protected CalendarEventHandler $eventHandler;

    public function boot(): void
    {
        $this->eventHandler = app(CalendarEventHandler::class);
        $this->configuration = $this->buildConfiguration();
    }

    /**
     * Handle teacher selection from parent page
     */
    public function handleTeacherSelected(int $teacherId, string $teacherType): void
    {
        $this->selectedTeacherId = $teacherId;
        $this->selectedTeacherType = $teacherType;
        $this->configuration = $this->buildConfiguration();
        $this->refreshCalendar();
    }

    /**
     * Build configuration for supervisor mode
     */
    protected function buildConfiguration(): CalendarConfiguration
    {
        if (! $this->selectedTeacherId || ! $this->selectedTeacherType) {
            // Return empty configuration if no teacher selected
            return new CalendarConfiguration(
                sessionTypes: [],
                allowDragDrop: false,
                allowResize: false,
            );
        }

        $sessionTypes = match ($this->selectedTeacherType) {
            'quran_teacher' => CalendarSessionType::forQuranTeacher(),
            'academic_teacher' => CalendarSessionType::forAcademicTeacher(),
            default => [],
        };

        return CalendarConfiguration::forSupervisor($this->selectedTeacherId, $sessionTypes);
    }

    public function getConfiguration(): CalendarConfiguration
    {
        return $this->configuration ?? $this->buildConfiguration();
    }

    /**
     * Get the target teacher as a User model
     */
    protected function getTargetTeacher(): ?User
    {
        if (! $this->selectedTeacherId) {
            return null;
        }

        return User::find($this->selectedTeacherId);
    }

    /**
     * Calendar configuration for FullCalendar
     */
    public function config(): array
    {
        $timezone = AcademyContextService::getTimezone();
        $config = $this->getConfiguration();

        return [
            'timeZone' => $timezone,
            'locale' => 'ar',
            'direction' => 'rtl',
            'initialView' => 'dayGridMonth',
            'headerToolbar' => [
                'start' => 'prev,next today',
                'center' => 'title',
                'end' => 'dayGridMonth,timeGridWeek,timeGridDay',
            ],
            'slotMinTime' => '06:00:00',
            'slotMaxTime' => '23:00:00',
            'scrollTime' => '08:00:00',
            'slotDuration' => '00:30:00',
            'nowIndicator' => true,
            'firstDay' => 6,
            'weekNumbers' => true,
            'weekends' => true,
            'dayMaxEvents' => true,
            'allDaySlot' => false,
            'eventTimeFormat' => [
                'hour' => '2-digit',
                'minute' => '2-digit',
                'meridiem' => 'short',
                'hour12' => true,
            ],
            'editable' => $config->allowDragDrop,
            'eventStartEditable' => $config->allowDragDrop,
            'eventDurationEditable' => $config->allowResize,
            'eventOverlap' => false,
            'selectable' => true,
            'height' => 'auto',
            'expandRows' => true,
            'businessHours' => [
                'daysOfWeek' => [6, 0, 1, 2, 3, 4, 5],
                'startTime' => '08:00',
                'endTime' => '22:00',
            ],
        ];
    }

    /**
     * Fetch events for the calendar
     */
    public function fetchEvents(array $fetchInfo): array
    {
        $teacher = $this->getTargetTeacher();
        if (! $teacher) {
            return [];
        }

        $timezone = AcademyContextService::getTimezone();
        $startDate = Carbon::parse($fetchInfo['start']);
        $endDate = Carbon::parse($fetchInfo['end']);

        $events = collect();

        foreach ($this->getConfiguration()->getSessionTypes() as $sessionType) {
            $sessions = $this->fetchSessionsForType($teacher, $sessionType, $startDate, $endDate);
            $formattedEvents = $this->formatEventsForType($sessions, $sessionType, $timezone);
            $events = $events->merge($formattedEvents);
        }

        return $events->toArray();
    }

    /**
     * Fetch sessions for a specific type
     */
    protected function fetchSessionsForType(User $teacher, CalendarSessionType $type, Carbon $start, Carbon $end): Collection
    {
        return match ($type) {
            CalendarSessionType::QURAN_INDIVIDUAL => $this->getQuranIndividualSessions($teacher, $start, $end),
            CalendarSessionType::QURAN_GROUP => $this->getQuranGroupSessions($teacher, $start, $end),
            CalendarSessionType::QURAN_TRIAL => $this->getTrialSessions($teacher, $start, $end),
            CalendarSessionType::ACADEMIC_PRIVATE => $this->getAcademicSessions($teacher, $start, $end),
            CalendarSessionType::INTERACTIVE_COURSE => $this->getCourseSessions($teacher, $start, $end),
        };
    }

    /**
     * Get Quran individual sessions for teacher
     */
    protected function getQuranIndividualSessions(User $teacher, Carbon $start, Carbon $end): Collection
    {
        return QuranSession::select([
            'id', 'title', 'description', 'scheduled_at', 'duration_minutes', 'status',
            'quran_teacher_id', 'student_id', 'quran_subscription_id',
            'individual_circle_id', 'session_type',
        ])
            ->whereBetween('scheduled_at', [$start, $end])
            ->where('quran_teacher_id', $teacher->id)
            ->where('session_type', 'individual')
            ->whereNull('trial_request_id')
            ->with([
                'quranTeacher:id,first_name,last_name,name,email,gender',
                'student:id,name',
                'subscription:id,package_id,starts_at,ends_at,status',
                'individualCircle:id,name,circle_code,default_duration_minutes',
            ])
            ->get();
    }

    /**
     * Get Quran group sessions for teacher
     */
    protected function getQuranGroupSessions(User $teacher, Carbon $start, Carbon $end): Collection
    {
        return QuranSession::select([
            'id', 'title', 'description', 'scheduled_at', 'duration_minutes', 'status',
            'quran_teacher_id', 'circle_id', 'session_type',
        ])
            ->whereBetween('scheduled_at', [$start, $end])
            ->where('session_type', 'group')
            ->where('quran_teacher_id', $teacher->id)
            ->with([
                'circle:id,name,circle_code,enrolled_students',
                'quranTeacher:id,first_name,last_name,name,email,gender',
            ])
            ->get();
    }

    /**
     * Get trial sessions for teacher
     */
    protected function getTrialSessions(User $teacher, Carbon $start, Carbon $end): Collection
    {
        return QuranSession::select([
            'id', 'title', 'description', 'scheduled_at', 'duration_minutes', 'status',
            'quran_teacher_id', 'student_id', 'session_type', 'trial_request_id',
        ])
            ->whereBetween('scheduled_at', [$start, $end])
            ->where('quran_teacher_id', $teacher->id)
            ->whereNotNull('trial_request_id')
            ->with([
                'quranTeacher:id,first_name,last_name,name,email,gender',
                'student:id,name',
                'trialRequest:id,student_name,phone,status',
            ])
            ->get();
    }

    /**
     * Get academic sessions for teacher
     */
    protected function getAcademicSessions(User $teacher, Carbon $start, Carbon $end): Collection
    {
        $profile = $teacher->academicTeacherProfile;
        if (! $profile) {
            return collect();
        }

        return AcademicSession::select([
            'id', 'title', 'description', 'scheduled_at', 'duration_minutes', 'status',
            'academic_teacher_id', 'student_id', 'academic_subscription_id',
            'academic_individual_lesson_id', 'session_type', 'session_code',
        ])
            ->whereBetween('scheduled_at', [$start, $end])
            ->where('academic_teacher_id', $profile->id)
            ->with([
                'academicTeacher:id,user_id',
                'academicTeacher.user:id,name,email,gender',
                'student:id,name',
                'academicIndividualLesson:id,subject_id,subscription_id',
                'academicIndividualLesson.subject:id,name,name_en',
                'subscription:id,package_id,starts_at,ends_at,status',
            ])
            ->get();
    }

    /**
     * Get interactive course sessions for teacher
     */
    protected function getCourseSessions(User $teacher, Carbon $start, Carbon $end): Collection
    {
        $profile = $teacher->academicTeacherProfile;
        if (! $profile) {
            return collect();
        }

        return InteractiveCourseSession::whereBetween('scheduled_at', [$start, $end])
            ->whereHas('course', function ($q) use ($profile) {
                $q->where('assigned_teacher_id', $profile->id);
            })
            ->with([
                'course' => function ($query) {
                    $query->with([
                        'assignedTeacher:id,user_id',
                        'assignedTeacher.user:id,name,email,gender',
                    ]);
                },
            ])
            ->get();
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

            return true;
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

            return true;
        }
    }

    /**
     * Handle event click
     */
    public function onEventClick(array $event): void
    {
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
     * Handle date selection (day cell click/selection)
     * Overrides the parent onDateSelect to show day sessions instead of create action
     */
    public function onDateSelect(string $start, ?string $end, bool $allDay, ?array $view, ?array $resource): void
    {
        // Extract just the date part for display
        $this->selectedDate = Carbon::parse($start)->format('Y-m-d');

        // Fetch sessions for the selected date
        $this->daySessions = $this->fetchSessionsForDate($this->selectedDate);

        $this->mountAction('daySessions', [
            'date' => $this->selectedDate,
        ]);
    }

    /**
     * Fetch sessions for a specific date
     */
    protected function fetchSessionsForDate(string $dateStr): array
    {
        $teacher = $this->getTargetTeacher();
        if (! $teacher) {
            return [];
        }

        $timezone = AcademyContextService::getTimezone();
        $date = Carbon::parse($dateStr, $timezone);
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $events = collect();

        foreach ($this->getConfiguration()->getSessionTypes() as $sessionType) {
            $sessions = $this->fetchSessionsForType($teacher, $sessionType, $startOfDay, $endOfDay);

            foreach ($sessions as $session) {
                $scheduledAt = $this->getScheduledAt($session)?->copy()->setTimezone($timezone);

                if (! $scheduledAt) {
                    continue;
                }

                $duration = $session->duration_minutes ?? 60;
                // Compare against current time in the same timezone to avoid timezone mismatch
                $now = AcademyContextService::nowInAcademyTimezone();
                $isPassed = $scheduledAt->isBefore($now);
                $status = $this->getSessionStatus($session);
                $canEdit = ! $isPassed && ($status?->canReschedule() ?? true);
                $statusColor = $this->getEventColor($sessionType, $status);
                $eventId = CalendarEventId::make($sessionType, $session->id);

                $events->push([
                    'eventId' => $eventId->toString(),
                    'time' => $scheduledAt->format('h:i A'),
                    'duration' => $duration,
                    'color' => $sessionType->hexColor(),
                    'icon' => $sessionType->icon(),
                    'sessionType' => $sessionType->fallbackLabel(),
                    'studentName' => $this->getStudentName($session, $sessionType),
                    'subject' => $this->getSessionSubject($session, $sessionType),
                    'status' => $status?->value,
                    'statusLabel' => $status?->label(),
                    'statusColor' => $status?->hexColor(),
                    'isPassed' => $isPassed,
                    'canEdit' => $canEdit,
                    'sessionUrl' => $this->getSessionUrl($session),
                ]);
            }
        }

        // Sort by time
        return $events->sortBy('time')->values()->toArray();
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

                // Edit button
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
                                ->timezone($timezone)
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

                // Build the new scheduled_at datetime in the academy timezone, then convert to APP_TIMEZONE for storage
                $newScheduledAt = Carbon::parse(
                    $data['scheduled_date'].' '.$data['scheduled_time'],
                    $timezone
                )->setTimezone(config('app.timezone'));

                // Check if the new time is in the past (compare in same timezone)
                $now = AcademyContextService::nowInAcademyTimezone();

                if ($newScheduledAt->isBefore($now)) {
                    Notification::make()
                        ->title('خطأ')
                        ->body('لا يمكن جدولة جلسة في وقت ماضي')
                        ->danger()
                        ->send();

                    return;
                }

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
     * Open edit dialog for a session
     */
    public function openEditDialog(string $eventId): void
    {
        $this->editingEventId = $eventId;
        $this->mountAction('edit', [
            'event' => ['id' => $eventId],
        ]);
    }

    /**
     * Day sessions action for viewing all sessions on a clicked day
     */
    protected function daySessionsAction(): Action
    {
        return Action::make('daySessions')
            ->label('جلسات اليوم')
            ->icon('heroicon-o-calendar-days')
            ->modalHeading(function (array $arguments) {
                $dateStr = $arguments['date'] ?? $this->selectedDate;
                if (! $dateStr) {
                    return 'جلسات اليوم';
                }
                $timezone = AcademyContextService::getTimezone();
                $date = Carbon::parse($dateStr, $timezone);

                return 'جلسات يوم '.$date->translatedFormat('l j F Y');
            })
            ->modalContent(function (array $arguments) {
                return view('filament.shared.widgets.day-sessions-modal', [
                    'sessions' => $this->daySessions,
                    'widgetId' => $this->getId(),
                ]);
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('إغلاق');
    }

    /**
     * Register modal actions
     */
    protected function modalActions(): array
    {
        return [
            $this->editAction(),
            $this->daySessionsAction(),
        ];
    }

    /**
     * Get session URL for viewing
     */
    protected function getSessionUrl(Model $record): ?string
    {
        $type = $this->getSessionTypeFromModel($record);

        return match ($type) {
            CalendarSessionType::QURAN_INDIVIDUAL,
            CalendarSessionType::QURAN_GROUP,
            CalendarSessionType::QURAN_TRIAL => "/supervisor-panel/monitored-all-sessions/{$record->id}?type=quran",
            CalendarSessionType::ACADEMIC_PRIVATE => "/supervisor-panel/monitored-all-sessions/{$record->id}?type=academic",
            CalendarSessionType::INTERACTIVE_COURSE => "/supervisor-panel/monitored-all-sessions/{$record->id}?type=interactive",
            default => null,
        };
    }
}
