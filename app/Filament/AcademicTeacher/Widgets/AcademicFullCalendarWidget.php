<?php

namespace App\Filament\AcademicTeacher\Widgets;

use App\Filament\Shared\Traits\FormatsCalendarData;
use App\Filament\Shared\Traits\ValidatesConflicts;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Services\AcademyContextService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Filament\Actions\Action;

class AcademicFullCalendarWidget extends FullCalendarWidget
{
    use FormatsCalendarData;
    use ValidatesConflicts;

    // Default model for academic sessions
    public Model|string|null $model = AcademicSession::class;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    // Properties for day sessions modal
    public ?string $selectedDate = null;
    public $daySessions = [];

    // Event listeners
    protected $listeners = [
        'refresh-calendar' => 'refreshCalendar',
    ];

    public function config(): array
    {
        return [
            'firstDay' => 6, // Saturday start
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay',
            ],
            'slotMinTime' => '00:00:00',
            'slotMaxTime' => '24:00:00',
            'scrollTime' => '08:00:00',
            'height' => 'auto',
            'expandRows' => true,
            'nowIndicator' => true,
            'slotDuration' => '00:30:00',
            'businessHours' => [
                'daysOfWeek' => [6, 0, 1, 2, 3, 4, 5],
                'startTime' => '08:00',
                'endTime' => '22:00',
            ],
            'eventColor' => '#3b82f6',
            'eventTextColor' => '#ffffff',
            'weekends' => true,
            'allDaySlot' => false,
            'selectMirror' => true,
            'unselectAuto' => false,
            'editable' => true,
            'eventStartEditable' => true,
            'eventDurationEditable' => true,
            'eventOverlap' => false,
        ];
    }

    /**
     * Override eventClassNames to add custom class for passed sessions
     */
    public function eventClassNames(): string
    {
        return <<<'JS'
            function(arg) {
                if (arg.event.extendedProps && arg.event.extendedProps.isPassed) {
                    return ["event-passed"];
                }
                return [];
            }
        JS;
    }

    /**
     * Override eventDidMount to add click handler for day numbers
     */
    public function eventDidMount(): string
    {
        $widgetId = $this->getId();

        return <<<JS
            function(info) {
                const calendarEl = info.el.closest('.filament-fullcalendar');

                if (calendarEl && !calendarEl.hasAttribute('data-click-initialized')) {
                    calendarEl.setAttribute('data-click-initialized', 'true');

                    const dayNumbers = calendarEl.querySelectorAll('.fc-daygrid-day-number');
                    dayNumbers.forEach(dayNum => {
                        dayNum.style.cursor = 'pointer';
                        dayNum.style.color = '#3b82f6';
                        dayNum.style.textDecoration = 'none';
                        dayNum.addEventListener('mouseenter', function() {
                            this.style.textDecoration = 'underline';
                        });
                        dayNum.addEventListener('mouseleave', function() {
                            this.style.textDecoration = 'none';
                        });
                    });

                    calendarEl.addEventListener('click', function(e) {
                        if (e.target.closest('.fc-event')) return;

                        const dateCell = e.target.closest('.fc-daygrid-day, .fc-timegrid-col, .fc-timegrid-slot');
                        if (!dateCell) return;

                        let dateStr = dateCell.dataset.date;

                        if (!dateStr && dateCell.classList.contains('fc-timegrid-slot')) {
                            const col = dateCell.closest('.fc-timegrid-col');
                            if (col) dateStr = col.dataset.date;
                        }

                        if (!dateStr) return;

                        window.Livewire.find('{$widgetId}').call('showDaySessionsModal', dateStr);
                    });
                }
            }
        JS;
    }

    /**
     * Fetch events for the calendar
     */
    public function fetchEvents(array $fetchInfo): array
    {
        $user = Auth::user();
        if (!$user || !$user->academicTeacherProfile) {
            return [];
        }

        $events = collect();
        $timezone = AcademyContextService::getTimezone();

        // Fetch Academic sessions
        $events = $events->merge($this->fetchAcademicSessions($user->academicTeacherProfile->id, $fetchInfo, $timezone));

        // Fetch Interactive Course sessions
        $events = $events->merge($this->fetchInteractiveCourseSessions($user->academicTeacherProfile->id, $fetchInfo, $timezone));

        return $events->toArray();
    }

    /**
     * Fetch Academic sessions for the calendar
     */
    protected function fetchAcademicSessions(int $teacherProfileId, array $fetchInfo, string $timezone): array
    {
        $query = AcademicSession::query()
            ->where('academic_teacher_id', $teacherProfileId)
            ->where('scheduled_at', '>=', $fetchInfo['start'])
            ->where('scheduled_at', '<=', $fetchInfo['end'])
            ->whereNotNull('scheduled_at')
            ->whereNull('deleted_at');

        return $query
            ->with(['subscription.student', 'student', 'academicIndividualLesson.academicSubject'])
            ->whereIn('status', ['scheduled', 'ready', 'ongoing', 'completed'])
            ->get()
            ->map(function (AcademicSession $session) use ($timezone) {
                return $this->mapAcademicSessionToEvent($session, $timezone);
            })
            ->toArray();
    }

    /**
     * Fetch Interactive Course sessions for the calendar
     */
    protected function fetchInteractiveCourseSessions(int $teacherProfileId, array $fetchInfo, string $timezone): array
    {
        $query = InteractiveCourseSession::query()
            ->whereHas('course', function ($q) use ($teacherProfileId) {
                $q->where('assigned_teacher_id', $teacherProfileId);
            })
            ->whereDate('scheduled_at', '>=', Carbon::parse($fetchInfo['start'])->toDateString())
            ->whereDate('scheduled_at', '<=', Carbon::parse($fetchInfo['end'])->toDateString())
            ->whereNotNull('scheduled_at');

        return $query
            ->with(['course', 'course.subject'])
            ->whereIn('status', ['scheduled', 'ready', 'ongoing', 'completed'])
            ->get()
            ->map(function (InteractiveCourseSession $session) use ($timezone) {
                return $this->mapInteractiveCourseSessionToEvent($session, $timezone);
            })
            ->toArray();
    }

    /**
     * Map Academic session to calendar event
     */
    protected function mapAcademicSessionToEvent(AcademicSession $session, string $timezone): EventData
    {
        $isPassed = $session->scheduled_at < Carbon::now($timezone);

        // Determine title and color
        $studentName = $session->subscription?->student?->name ?? $session->student?->name ?? 'درس خاص';
        $subject = $session->academicIndividualLesson?->academicSubject?->name ?? 'مادة أكاديمية';
        $sessionNumber = $session->monthly_session_number ? "({$session->monthly_session_number})" : '';
        $title = "درس خاص: {$studentName} {$sessionNumber}";
        $color = $this->getSessionColor('individual', $session->status->value, true);

        $classNames = '';
        if ($isPassed && $session->status !== 'ongoing') {
            $classNames = 'event-passed';
        }

        $scheduledAt = $session->scheduled_at instanceof \Carbon\Carbon
            ? $session->scheduled_at->copy()->timezone($timezone)
            : \Carbon\Carbon::parse($session->scheduled_at, $timezone);

        $startString = $scheduledAt->format('Y-m-d\TH:i:s');
        $endString = $scheduledAt->copy()->addMinutes($session->duration_minutes ?? 60)->format('Y-m-d\TH:i:s');

        $eventData = EventData::make()
            ->id('academic-' . $session->id)
            ->title($title)
            ->start($startString)
            ->end($endString)
            ->backgroundColor($color)
            ->borderColor($color)
            ->textColor('#ffffff')
            ->extendedProps([
                'modelType' => 'academic',
                'sessionType' => 'individual',
                'status' => $session->status,
                'subscriptionId' => $session->subscription_id,
                'studentId' => $session->subscription?->student_id ?? $session->student_id,
                'subject' => $subject,
                'duration' => $session->duration_minutes,
                'monthlySessionNumber' => $session->monthly_session_number,
                'sessionMonth' => $session->session_month,
                'isMovable' => true,
                'isPassed' => $isPassed,
            ]);

        if ($classNames) {
            $eventData->extraProperties(['classNames' => [$classNames]]);
        }

        return $eventData;
    }

    /**
     * Map Interactive Course session to calendar event
     */
    protected function mapInteractiveCourseSessionToEvent(InteractiveCourseSession $session, string $timezone): EventData
    {
        $courseName = $session->course?->title ?? 'دورة تفاعلية';
        $sessionNumber = $session->session_number ? "({$session->session_number})" : '';
        $title = "دورة تفاعلية: {$courseName} {$sessionNumber}";

        $scheduledAt = $session->scheduled_at;
        $isPassed = $scheduledAt < Carbon::now($timezone);
        $status = $session->status ?? 'scheduled';
        $color = $this->getSessionColor('interactive_course', $status, true);

        $classNames = '';
        if ($isPassed && $status !== 'ongoing') {
            $classNames = 'event-passed';
        }

        $scheduledAtTz = $scheduledAt instanceof \Carbon\Carbon
            ? $scheduledAt->copy()->timezone($timezone)
            : \Carbon\Carbon::parse($scheduledAt, $timezone);

        $startString = $scheduledAtTz->format('Y-m-d\TH:i:s');
        $endString = $scheduledAtTz->copy()->addMinutes($session->duration_minutes ?? 60)->format('Y-m-d\TH:i:s');

        $eventData = EventData::make()
            ->id('course-' . $session->id)
            ->title($title)
            ->start($startString)
            ->end($endString)
            ->backgroundColor($color)
            ->borderColor($color)
            ->textColor('#ffffff')
            ->extendedProps([
                'modelType' => 'course',
                'sessionType' => 'interactive_course',
                'status' => $status,
                'courseId' => $session->course_id,
                'sessionNumber' => $session->session_number,
                'duration' => $session->duration_minutes,
                'isMovable' => false,
                'isPassed' => $isPassed,
            ]);

        if ($classNames) {
            $eventData->extraProperties(['classNames' => [$classNames]]);
        }

        return $eventData;
    }

    /**
     * Override resolveRecord to handle prefixed event IDs
     */
    public function resolveRecord(int | string $key): Model
    {
        // Handle Academic sessions with 'academic-' prefix
        if (is_string($key) && str_starts_with($key, 'academic-')) {
            $id = substr($key, 9);
            $record = AcademicSession::find($id);

            if (!$record) {
                throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(AcademicSession::class, [$id]);
            }

            return $record;
        }

        // Handle Interactive Course sessions with 'course-' prefix
        if (is_string($key) && str_starts_with($key, 'course-')) {
            $id = substr($key, 7);
            $record = InteractiveCourseSession::find($id);

            if (!$record) {
                throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(InteractiveCourseSession::class, [$id]);
            }

            return $record;
        }

        return parent::resolveRecord($key);
    }

    /**
     * Helper method for resolving event records in modal actions
     */
    protected function resolveEventRecord(string $eventId): ?Model
    {
        try {
            return $this->resolveRecord($eventId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return null;
        }
    }

    /**
     * Modal actions for event management
     */
    protected function modalActions(): array
    {
        return [
            Actions\EditAction::make('editSession')
                ->label('تعديل')
                ->icon('heroicon-o-pencil-square')
                ->visible(function (array $arguments): bool {
                    $eventId = $arguments['event']['id'] ?? null;
                    if (!$eventId) {
                        return false;
                    }

                    // Show edit for academic sessions, hide for course sessions
                    return str_starts_with($eventId, 'academic-');
                })
                ->modalHeading('تعديل الجلسة الأكاديمية')
                ->modalSubmitActionLabel('حفظ التغييرات')
                ->modalCancelActionLabel('إلغاء')
                ->fillForm(function (array $arguments): array {
                    $eventId = $arguments['event']['id'] ?? null;
                    $record = $eventId ? $this->resolveEventRecord($eventId) : null;

                    if (!$record) {
                        return [];
                    }

                    return [
                        'scheduled_at' => $record->scheduled_at,
                        'description' => $record->description,
                    ];
                })
                ->form([
                    Forms\Components\DateTimePicker::make('scheduled_at')
                        ->label('موعد الجلسة')
                        ->required()
                        ->seconds(false)
                        ->minutesStep(15)
                        ->minDate(fn () => Carbon::now()->toDateString())
                        ->maxDate(fn () => Carbon::now()->addMonths(6)->toDateString())
                        ->native(false)
                        ->displayFormat('Y-m-d H:i')
                        ->timezone(AcademyContextService::getTimezone())
                        ->helperText('اختر التاريخ والوقت الجديد للجلسة'),

                    Forms\Components\Textarea::make('description')
                        ->label('ملاحظات الجلسة')
                        ->rows(3)
                        ->maxLength(500)
                        ->placeholder('اكتب أي ملاحظات حول الجلسة...'),
                ])
                ->action(function (array $arguments, array $data): void {
                    $eventId = $arguments['event']['id'] ?? null;
                    $record = $eventId ? $this->resolveEventRecord($eventId) : null;

                    if (!$record) {
                        Notification::make()
                            ->title('خطأ')
                            ->body('لم يتم العثور على الجلسة')
                            ->danger()
                            ->send();
                        return;
                    }

                    $scheduledAt = Carbon::parse($data['scheduled_at']);

                    $record->update([
                        'scheduled_at' => $scheduledAt,
                        'description' => $data['description'] ?? $record->description,
                    ]);

                    Notification::make()
                        ->title('تم تحديث الجلسة بنجاح')
                        ->success()
                        ->send();

                    $this->dispatch('refresh');
                })
                ->extraModalFooterActions([
                    Action::make('view_full_edit')
                        ->label('عرض الصفحة الكاملة للتعديل')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->color('gray')
                        ->size('sm')
                        ->url(function (array $arguments) {
                            $eventId = $arguments['event']['id'] ?? null;
                            $record = $eventId ? $this->resolveEventRecord($eventId) : null;

                            if (!$record) {
                                return '#';
                            }

                            $panelId = filament()->getCurrentPanel()->getId();

                            if ($record instanceof \App\Models\InteractiveCourseSession) {
                                return route("filament.{$panelId}.resources.interactive-course-sessions.edit", [
                                    'tenant' => filament()->getTenant(),
                                    'record' => $record,
                                ]);
                            }

                            return route("filament.{$panelId}.resources.academic-sessions.edit", [
                                'tenant' => filament()->getTenant(),
                                'record' => $record,
                            ]);
                        })
                        ->openUrlInNewTab()
                        ->visible(function (array $arguments) {
                            $eventId = $arguments['event']['id'] ?? null;
                            $record = $eventId ? $this->resolveEventRecord($eventId) : null;

                            if (!$record) {
                                return false;
                            }

                            $user = Auth::user();
                            return $user->academicTeacherProfile
                                && $record->academic_teacher_id === $user->academicTeacherProfile->id;
                        }),
                ]),

            // Action for viewing all sessions for a specific day
            Action::make('viewDaySessions')
                ->label('جلسات اليوم')
                ->icon('heroicon-o-calendar-days')
                ->modalHeading(fn () => 'جلسات يوم ' . ($this->selectedDate ? Carbon::parse($this->selectedDate)->locale('ar')->translatedFormat('l، j F Y') : ''))
                ->modalContent(fn () => view('filament.widgets.day-sessions-list', [
                    'sessions' => $this->daySessions
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('إغلاق')
                ->closeModalByClickingAway(true),
        ];
    }

    /**
     * View action for displaying session details
     */
    protected function viewAction(): Action
    {
        return Actions\ViewAction::make()
            ->label('عرض التفاصيل')
            ->icon('heroicon-o-eye')
            ->modalHeading(function (array $arguments) {
                $eventId = $arguments['event']['id'] ?? null;
                $record = $eventId ? $this->resolveEventRecord($eventId) : null;

                if (!$record) {
                    return 'تفاصيل الجلسة';
                }

                return "تفاصيل الجلسة: {$record->title}";
            })
            ->infolist(function (array $arguments) {
                $eventId = $arguments['event']['id'] ?? null;
                $record = $eventId ? $this->resolveEventRecord($eventId) : null;

                if (!$record) {
                    return [];
                }

                return [
                    \Filament\Infolists\Components\TextEntry::make('title')
                        ->label('عنوان الجلسة')
                        ->state($record->title),
                    \Filament\Infolists\Components\TextEntry::make('description')
                        ->label('وصف الجلسة')
                        ->state($record->description)
                        ->placeholder('لا يوجد وصف'),
                    \Filament\Infolists\Components\TextEntry::make('scheduled_at')
                        ->label('موعد الجلسة')
                        ->state($record->scheduled_at)
                        ->dateTime()
                        ->timezone(AcademyContextService::getTimezone()),
                    \Filament\Infolists\Components\TextEntry::make('duration_minutes')
                        ->label('مدة الجلسة')
                        ->state(($record->duration_minutes ?? 60).' دقيقة'),
                    \Filament\Infolists\Components\TextEntry::make('status')
                        ->label('حالة الجلسة')
                        ->state($record->status)
                        ->badge()
                        ->color(fn ($state): string => match ($state instanceof \App\Enums\SessionStatus ? $state->value : $state) {
                            'unscheduled' => 'gray',
                            'scheduled' => 'warning',
                            'ready' => 'info',
                            'ongoing' => 'primary',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                            'absent' => 'warning',
                            default => 'gray',
                        })
                        ->formatStateUsing(fn ($state): string => match ($state instanceof \App\Enums\SessionStatus ? $state->value : $state) {
                            'unscheduled' => 'غير مجدولة',
                            'scheduled' => 'مجدولة',
                            'ready' => 'جاهزة للبدء',
                            'ongoing' => 'جارية',
                            'completed' => 'مكتملة',
                            'cancelled' => 'ملغية',
                            'absent' => 'غياب الطالب',
                            default => $state instanceof \App\Enums\SessionStatus ? $state->value : $state,
                        }),
                ];
            })
            ->modalFooterActions(function (Action $action): array {
                $record = $this->record;

                if (!$record) {
                    return [$action->getModalCancelAction()];
                }

                $isCourseSession = $record instanceof InteractiveCourseSession;

                if ($isCourseSession) {
                    $panelId = filament()->getCurrentPanel()->getId();
                    $viewFullUrl = route("filament.{$panelId}.resources.interactive-courses.view", [
                        'tenant' => filament()->getTenant(),
                        'record' => $record->course_id,
                    ]);
                } else {
                    $panelId = filament()->getCurrentPanel()->getId();
                    $viewFullUrl = route("filament.{$panelId}.resources.academic-sessions.view", [
                        'tenant' => filament()->getTenant(),
                        'record' => $record,
                    ]);
                }

                $eventId = $isCourseSession ? 'course-' . $record->id : 'academic-' . $record->id;

                $editButton = Action::make('edit')
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->action(function () use ($eventId) {
                        $this->replaceMountedAction('editSession', ['event' => ['id' => $eventId]]);
                    })
                    ->visible(function () use ($record, $isCourseSession) {
                        $scheduledAt = $record->scheduled_at;
                        if ($scheduledAt && $scheduledAt < Carbon::now()) {
                            return false;
                        }

                        if ($isCourseSession) {
                            return false; // Course sessions cannot be edited from calendar
                        }

                        $user = Auth::user();
                        if (!$user->academicTeacherProfile) {
                            return false;
                        }

                        return $record->academic_teacher_id === $user->academicTeacherProfile->id;
                    });

                $viewFullButton = Action::make('view_full')
                    ->label('فتح الصفحة الكاملة')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url($viewFullUrl)
                    ->openUrlInNewTab();

                return [
                    $editButton,
                    $viewFullButton,
                    $action->getModalCancelAction(),
                ];
            });
    }

    /**
     * Show all sessions for a specific day
     */
    public function showDaySessionsModal(string $dateStr): void
    {
        $clickedDate = Carbon::parse($dateStr);
        $user = Auth::user();

        if (!$user || !$user->academicTeacherProfile) {
            return;
        }

        $teacherProfile = $user->academicTeacherProfile;

        // Fetch all sessions for this day
        $academicSessions = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->whereDate('scheduled_at', $clickedDate->toDateString())
            ->whereNotNull('scheduled_at')
            ->with(['student', 'academicIndividualLesson.academicSubject'])
            ->orderBy('scheduled_at')
            ->get();

        $courseSessions = InteractiveCourseSession::whereHas('course', function ($query) use ($teacherProfile) {
                $query->where('assigned_teacher_id', $teacherProfile->id);
            })
            ->whereDate('scheduled_at', $clickedDate->toDateString())
            ->whereNotNull('scheduled_at')
            ->with(['course.subject'])
            ->orderBy('scheduled_at')
            ->get();

        $allSessions = $academicSessions->merge($courseSessions);

        $this->selectedDate = $dateStr;

        $this->daySessions = $allSessions->map(function ($session) use ($user) {
            $isCourse = $session instanceof InteractiveCourseSession;

            $scheduledAt = $session->scheduled_at;
            $isPassed = $scheduledAt < Carbon::now();

            $sessionData = [
                'type' => $isCourse ? 'course' : 'academic',
                'isPassed' => $isPassed,
                'time' => $scheduledAt->format('h:i A'),
                'duration' => $session->duration_minutes ?? 60,
                'studentName' => $session->student?->name ?? ($isCourse ? $session->course?->title : 'طالب'),
                'subject' => '',
                'sessionType' => '',
                'color' => '',
                'eventId' => '',
                'canEdit' => false,
                'status' => $session->status instanceof \App\Enums\SessionStatus ? $session->status->value : ($session->status ?? 'scheduled'),
            ];

            if ($isCourse) {
                $sessionData['sessionType'] = 'دورة تفاعلية';
                $sessionData['subject'] = $session->course?->subject?->name ?? 'مادة أكاديمية';
                $sessionData['color'] = $this->getSessionColor('interactive_course', $sessionData['status'], true);
                $sessionData['eventId'] = 'course-' . $session->id;
                $sessionData['canEdit'] = false;
            } else {
                $sessionData['sessionType'] = 'درس أكاديمي';
                $sessionData['subject'] = $session->academicIndividualLesson?->academicSubject?->name ?? 'مادة';
                $sessionData['color'] = $this->getSessionColor('individual', $sessionData['status'], true);
                $sessionData['eventId'] = 'academic-' . $session->id;
                $sessionData['canEdit'] = $user->academicTeacherProfile && $user->academicTeacherProfile->id === $session->academic_teacher_id;
            }

            return $sessionData;
        })->toArray();

        $this->mountAction('viewDaySessions');
    }

    /**
     * Edit session from day modal
     */
    public function editSessionFromDayModal(string $eventId): void
    {
        $record = $this->resolveEventRecord($eventId);

        if (!$record) {
            Notification::make()
                ->title('خطأ')
                ->body('لم يتم العثور على الجلسة')
                ->danger()
                ->send();
            return;
        }

        $this->record = $record;
        $this->replaceMountedAction('editSession', ['event' => ['id' => $eventId]]);
    }

    /**
     * Handle event drop (drag and drop) - only for academic individual sessions
     */
    public function onEventDrop(array $event, array $oldEvent, array $relatedEvents, array $delta, ?array $oldResource, ?array $newResource): bool
    {
        $eventId = $event['id'];
        $modelType = $event['extendedProps']['modelType'] ?? 'academic';

        // Don't allow moving course sessions
        if ($modelType === 'course') {
            Notification::make()
                ->title('غير مسموح')
                ->body('لا يمكن تحريك جلسات الدورات التفاعلية.')
                ->warning()
                ->send();

            $this->dispatch('refresh');
            return false;
        }

        $numericId = (int) str_replace('academic-', '', $eventId);
        $record = AcademicSession::find($numericId);

        if (!$record) {
            return false;
        }

        $newStart = Carbon::parse($event['start']);
        $newEnd = Carbon::parse($event['end']);
        $duration = $newStart->diffInMinutes($newEnd);

        // Validate the new date is not in the past
        if ($newStart->isPast()) {
            Notification::make()
                ->title('غير مسموح')
                ->body('لا يمكن جدولة الجلسات في الماضي.')
                ->warning()
                ->send();

            $this->dispatch('refresh');
            return false;
        }

        // Validate subscription constraints
        if ($record->subscription_id) {
            $subscription = $record->subscription;

            if ($subscription) {
                // Check if subscription is active
                if ($subscription->subscription_status !== 'active') {
                    Notification::make()
                        ->title('غير مسموح')
                        ->body('الاشتراك غير نشط.')
                        ->danger()
                        ->send();

                    $this->dispatch('refresh');
                    return false;
                }

                // Check if new date is within subscription period
                if ($subscription->starts_at && $newStart->isBefore($subscription->starts_at)) {
                    Notification::make()
                        ->title('غير مسموح')
                        ->body('لا يمكن جدولة الجلسة قبل تاريخ بدء الاشتراك (' . $subscription->starts_at->format('Y/m/d') . ')')
                        ->danger()
                        ->send();

                    $this->dispatch('refresh');
                    return false;
                }

                if ($subscription->ends_at && $newStart->isAfter($subscription->ends_at)) {
                    Notification::make()
                        ->title('غير مسموح')
                        ->body('لا يمكن جدولة الجلسة بعد نهاية الاشتراك (' . $subscription->ends_at->format('Y/m/d') . ')')
                        ->danger()
                        ->send();

                    $this->dispatch('refresh');
                    return false;
                }
            }
        }

        // Validate no conflicts
        try {
            $user = Auth::user();
            $teacherId = $user->academicTeacherProfile->id;

            $conflictData = [
                'scheduled_at' => $newStart,
                'duration_minutes' => $duration,
                'teacher_id' => $teacherId,
            ];

            $this->validateSessionConflicts($conflictData, $numericId, 'academic');
        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في تحديث الجلسة')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->dispatch('refresh');
            return false;
        }

        // Update the record
        try {
            $record->update([
                'scheduled_at' => $newStart,
                'duration_minutes' => $duration,
            ]);

            $result = parent::onEventDrop($event, $oldEvent, $relatedEvents, $delta, $oldResource, $newResource);

            Notification::make()
                ->title('تم تحديث موعد الجلسة بنجاح')
                ->success()
                ->send();

            return $result;

        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في تحديث الجلسة')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->dispatch('refresh');
            return false;
        }
    }

    /**
     * Handle event resize
     */
    public function onEventResize(array $event, array $oldEvent, array $relatedEvents, array $endDelta, array $startDelta): bool
    {
        $eventId = $event['id'];
        $modelType = $event['extendedProps']['modelType'] ?? 'academic';

        // Don't allow resizing course sessions
        if ($modelType === 'course') {
            Notification::make()
                ->title('غير مسموح')
                ->body('لا يمكن تغيير مدة جلسات الدورات التفاعلية.')
                ->warning()
                ->send();

            $this->dispatch('refresh');
            return false;
        }

        $numericId = (int) str_replace('academic-', '', $eventId);
        $record = AcademicSession::find($numericId);

        if (!$record) {
            return false;
        }

        $newStart = Carbon::parse($event['start']);
        $newEnd = Carbon::parse($event['end']);
        $newDuration = $newStart->diffInMinutes($newEnd);

        // Validate duration is acceptable
        if (!in_array($newDuration, [30, 45, 60, 90, 120])) {
            Notification::make()
                ->title('خطأ في المدة')
                ->body('مدة الجلسة يجب أن تكون 30، 45، 60، 90، أو 120 دقيقة')
                ->danger()
                ->send();

            $this->dispatch('refresh');
            return false;
        }

        // Validate no conflicts with new duration
        try {
            $user = Auth::user();
            $teacherId = $user->academicTeacherProfile->id;

            $conflictData = [
                'scheduled_at' => $newStart,
                'duration_minutes' => $newDuration,
                'teacher_id' => $teacherId,
            ];

            $this->validateSessionConflicts($conflictData, $numericId, 'academic');
        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في تحديث الجلسة')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->dispatch('refresh');
            return false;
        }

        // Update duration
        try {
            $record->update([
                'duration_minutes' => $newDuration,
            ]);

            $result = parent::onEventResize($event, $oldEvent, $relatedEvents, $endDelta, $startDelta);

            Notification::make()
                ->title('تم تحديث مدة الجلسة بنجاح')
                ->success()
                ->send();

            return $result;

        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في تحديث الجلسة')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->dispatch('refresh');
            return false;
        }
    }

    /**
     * Handle calendar refresh event
     */
    public function refreshCalendar(): void
    {
        $this->dispatch('refresh');
    }
}
