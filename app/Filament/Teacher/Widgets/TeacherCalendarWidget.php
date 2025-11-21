<?php

namespace App\Filament\Teacher\Widgets;

use App\Filament\Shared\Traits\FormatsCalendarData;
use App\Filament\Shared\Traits\ValidatesConflicts;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class TeacherCalendarWidget extends FullCalendarWidget
{
    use FormatsCalendarData;
    use ValidatesConflicts;

    // Default model for widget initialization (overridden by resolveEventRecord for Academic sessions)
    public Model|string|null $model = QuranSession::class;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    // Properties for circle filtering
    public ?int $selectedCircleId = null;

    public ?string $selectedCircleType = null;

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
            'scrollTime' => '08:00:00', // Initial scroll position
            'height' => 'auto',
            'expandRows' => true,
            'nowIndicator' => true,
            'slotDuration' => '00:30:00',
            'businessHours' => [
                'daysOfWeek' => [6, 0, 1, 2, 3, 4, 5], // Sunday to Saturday
                'startTime' => '08:00',
                'endTime' => '22:00',
            ],
            'eventColor' => '#10b981',
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
     * Override eventDidMount to add click handler for day numbers (initialized only once)
     */
    public function eventDidMount(): string
    {
        $widgetId = $this->getId();

        return <<<JS
            function(info) {
                // Get the calendar container element
                const calendarEl = info.el.closest('.filament-fullcalendar');

                // Only initialize once using data attribute
                if (calendarEl && !calendarEl.hasAttribute('data-click-initialized')) {
                    calendarEl.setAttribute('data-click-initialized', 'true');

                    // Style day numbers as clickable links
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

                    // Add click handler for day cells
                    calendarEl.addEventListener('click', function(e) {
                        // Check if click is on an event - if so, skip day modal handling
                        if (e.target.closest('.fc-event')) return;

                        // Find the date cell (works for both day grid and time grid, including other months)
                        const dateCell = e.target.closest('.fc-daygrid-day, .fc-timegrid-col, .fc-timegrid-slot');
                        if (!dateCell) return;

                        // Get date from data attribute
                        let dateStr = dateCell.dataset.date;

                        // For time grid, we might need to look at parent col
                        if (!dateStr && dateCell.classList.contains('fc-timegrid-slot')) {
                            const col = dateCell.closest('.fc-timegrid-col');
                            if (col) dateStr = col.dataset.date;
                        }

                        if (!dateStr) return;

                        // Single-click: Open day sessions modal immediately
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
        if (! $user) {
            return [];
        }

        $events = collect();
        $timezone = AcademyContextService::getTimezone();

        // Fetch Quran sessions if user is a Quran teacher
        if ($user->user_type === 'quran_teacher') {
            $events = $events->merge($this->fetchQuranSessions($user->id, $fetchInfo, $timezone));
        }

        // Fetch Academic sessions if user is an Academic teacher
        if ($user->user_type === 'academic_teacher' && $user->academicTeacherProfile) {
            $events = $events->merge($this->fetchAcademicSessions($user->academicTeacherProfile->id, $fetchInfo, $timezone));
            $events = $events->merge($this->fetchInteractiveCourseSessions($user->academicTeacherProfile->id, $fetchInfo, $timezone));
        }

        return $events->toArray();
    }

    /**
     * Fetch Quran sessions for the calendar
     */
    protected function fetchQuranSessions(int $teacherId, array $fetchInfo, string $timezone): array
    {
        $query = QuranSession::query()
            ->where('quran_teacher_id', $teacherId)
            ->where('scheduled_at', '>=', $fetchInfo['start'])
            ->where('scheduled_at', '<=', $fetchInfo['end'])
            ->whereNotNull('scheduled_at')
            ->whereNull('deleted_at');

        // Apply circle filtering if selected
        if ($this->selectedCircleId && $this->selectedCircleType) {
            if ($this->selectedCircleType === 'group') {
                $query->where('circle_id', $this->selectedCircleId);
            } elseif ($this->selectedCircleType === 'individual') {
                $query->where('individual_circle_id', $this->selectedCircleId);
            }
        }

        return $query
            ->with(['circle', 'individualCircle', 'individualCircle.subscription', 'individualCircle.subscription.package', 'student', 'trialRequest'])
            ->whereIn('status', ['scheduled', 'ready', 'ongoing', 'completed'])
            ->get()
            ->map(function (QuranSession $session) use ($timezone) {
                return $this->mapQuranSessionToEvent($session, $timezone);
            })
            ->toArray();
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
            ->with(['subscription.student', 'student', 'interactiveCourseSession'])
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
     * Map Quran session to calendar event
     */
    protected function mapQuranSessionToEvent(QuranSession $session, string $timezone): EventData
    {
        $sessionType = $session->session_type;
        $isPassed = $session->scheduled_at < Carbon::now($timezone);

        // Determine title and color based on session type
        if ($sessionType === 'trial') {
            $studentName = $session->student->name ??
                         $session->trialRequest->student_name ??
                         'طالب تجريبي';
            $title = "جلسة تجريبية: {$studentName}";
            $color = $this->getSessionColor('trial', $session->status->value);
        } elseif ($sessionType === 'group' || ! empty($session->circle_id)) {
            $circle = $session->circle;
            $circleName = $circle ? $circle->name : 'حلقة محذوفة';
            $sessionNumber = $session->monthly_session_number ? "({$session->monthly_session_number})" : '';
            $title = "حلقة جماعية: {$circleName} {$sessionNumber}";
            $color = $this->getSessionColor('group', $session->status->value);
        } else {
            $circle = $session->individualCircle;
            $studentName = $session->student->name ?? ($circle ? $circle->name : null) ?? 'حلقة فردية';
            $sessionNumber = $session->monthly_session_number ? "({$session->monthly_session_number})" : '';
            $title = "حلقة فردية: {$studentName} {$sessionNumber}";
            $color = $this->getSessionColor('individual', $session->status->value, false);
        }

        // Add strikethrough class for passed sessions
        $classNames = '';
        if ($isPassed && $session->status !== 'ongoing') {
            $classNames = 'event-passed';
        }

        // Format times
        $scheduledAt = $session->scheduled_at instanceof \Carbon\Carbon
            ? $session->scheduled_at->copy()->timezone($timezone)
            : \Carbon\Carbon::parse($session->scheduled_at, $timezone);

        $startString = $scheduledAt->format('Y-m-d\TH:i:s');
        $endString = $scheduledAt->copy()->addMinutes($session->duration_minutes ?? 60)->format('Y-m-d\TH:i:s');

        $eventData = EventData::make()
            ->id('quran-' . $session->id)
            ->title($title)
            ->start($startString)
            ->end($endString)
            ->backgroundColor($color)
            ->borderColor($color)
            ->textColor('#ffffff')
            ->extendedProps([
                'modelType' => 'quran',
                'sessionType' => $session->session_type,
                'status' => $session->status,
                'circleId' => $session->circle_id,
                'individualCircleId' => $session->individual_circle_id,
                'studentId' => $session->student_id,
                'duration' => $session->duration_minutes,
                'monthlySessionNumber' => $session->monthly_session_number,
                'sessionMonth' => $session->session_month,
                'isMovable' => $session->session_type === 'individual',
                'isPassed' => $isPassed,
            ]);

        // Add classNames as direct property for CSS styling
        if ($classNames) {
            $eventData->extraProperties(['classNames' => [$classNames]]);
        }

        return $eventData;
    }

    /**
     * Map Academic session to calendar event
     */
    protected function mapAcademicSessionToEvent(AcademicSession $session, string $timezone): EventData
    {
        $isPassed = $session->scheduled_at < Carbon::now($timezone);

        // Determine title and color based on session type
        if ($session->interactive_course_id) {
            // Interactive course session
            $courseName = $session->interactiveCourse?->title ?? 'دورة تفاعلية';
            $sessionNumber = $session->monthly_session_number ? "({$session->monthly_session_number})" : '';
            $title = "دورة تفاعلية: {$courseName} {$sessionNumber}";
            $color = $this->getSessionColor('interactive_course', $session->status->value, true);
        } else {
            // Private lesson
            $studentName = $session->subscription?->student?->name ?? 'درس خاص';
            $sessionNumber = $session->monthly_session_number ? "({$session->monthly_session_number})" : '';
            $title = "درس خاص: {$studentName} {$sessionNumber}";
            $color = $this->getSessionColor('individual', $session->status->value, true);
        }

        // Add strikethrough class for passed sessions
        $classNames = '';
        if ($isPassed && $session->status !== 'ongoing') {
            $classNames = 'event-passed';
        }

        // Format times
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
                'sessionType' => $session->interactive_course_id ? 'interactive_course' : 'individual',
                'status' => $session->status,
                'subscriptionId' => $session->subscription_id,
                'interactiveCourseId' => $session->interactive_course_id,
                'studentId' => $session->subscription?->student_id,
                'duration' => $session->duration_minutes,
                'monthlySessionNumber' => $session->monthly_session_number,
                'sessionMonth' => $session->session_month,
                'isMovable' => ! $session->interactive_course_id, // Only private lessons are movable
                'isPassed' => $isPassed,
            ]);

        // Add classNames as direct property for CSS styling
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

        // Add strikethrough class for passed sessions
        $classNames = '';
        if ($isPassed && $status !== 'ongoing') {
            $classNames = 'event-passed';
        }

        // Format times
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
                'isMovable' => false, // Course sessions cannot be moved
                'isPassed' => $isPassed,
            ]);

        // Add classNames as direct property for CSS styling
        if ($classNames) {
            $eventData->extraProperties(['classNames' => [$classNames]]);
        }

        return $eventData;
    }

    /**
     * Form schema for regular session editing
     * @param mixed $record The session record (no type hint to avoid DI issues)
     */
    public function getFormSchema($record = null): array
    {
        return [
            Forms\Components\TextInput::make('title')
                ->label('عنوان الجلسة')
                ->required()
                ->maxLength(255)
                ->default('جلسة قرآن كريم'),

            Forms\Components\Textarea::make('description')
                ->label('وصف الجلسة')
                ->rows(3)
                ->maxLength(500)
                ->placeholder('اكتب وصف أو ملاحظات حول الجلسة...')
                ->default('جلسة تحفيظ وتلاوة القرآن الكريم'),

            Forms\Components\DateTimePicker::make('scheduled_at')
                ->label('موعد الجلسة')
                ->required()
                ->seconds(false)
                ->minutesStep(15)
                ->minDate(fn () => Carbon::now()->toDateString())
                ->maxDate(function () use ($record) {
                    if ($record && $record->session_type === 'individual' && $record->individualCircle?->subscription?->ends_at) {
                        return $record->individualCircle->subscription->ends_at->toDateString();
                    }

                    return Carbon::now()->addMonths(6)->toDateString();
                })
                ->native(false)
                ->displayFormat('Y-m-d H:i')
                ->timezone(AcademyContextService::getTimezone())
                ->rules([
                    function () use ($record) {
                        return function (string $attribute, $value, \Closure $fail) use ($record) {
                            if (! $value || ! $record) {
                                return;
                            }

                            $scheduledAt = Carbon::parse($value);

                            // Check if date is beyond subscription end date (for individual sessions only)
                            if ($record->session_type === 'individual' && $record->individualCircle?->subscription?->ends_at) {
                                $subscriptionEnd = $record->individualCircle->subscription->ends_at->endOfDay();
                                if ($scheduledAt->isAfter($subscriptionEnd)) {
                                    $endDate = $subscriptionEnd->format('Y-m-d');
                                    $fail("لا يمكن جدولة الجلسة بعد تاريخ انتهاء الاشتراك: {$endDate}");
                                }
                            }

                            // Check for session conflicts
                            $conflictData = [
                                'scheduled_at' => $scheduledAt,
                                'duration_minutes' => $record->duration_minutes ?? 60,
                                'quran_teacher_id' => Auth::id(),
                            ];

                            try {
                                $this->validateSessionConflicts($conflictData, $record->id);
                            } catch (\Exception $e) {
                                $fail($e->getMessage());
                            }
                        };
                    },
                ])
                ->helperText(function () use ($record) {
                    if ($record && $record->session_type === 'individual' && $record->individualCircle?->subscription?->ends_at) {
                        $endDate = $record->individualCircle->subscription->ends_at->format('Y-m-d');

                        return "يمكن جدولة الجلسة حتى تاريخ انتهاء الاشتراك: {$endDate}";
                    }

                    return 'اختر تاريخ ووقت الجلسة';
                })
                ->default(fn () => $record?->scheduled_at),
        ];
    }

    /**
     * Form schema for trial session editing (date and notes only)
     */
    public function getTrialFormSchema(): array
    {
        return [
            Forms\Components\DateTimePicker::make('scheduled_at')
                ->label('موعد الجلسة')
                ->required()
                ->seconds(false)
                ->minutesStep(15)
                ->minDate(fn () => Carbon::now()->toDateString())
                ->maxDate(fn () => Carbon::now()->addMonths(2)->toDateString())
                ->native(false)
                ->displayFormat('Y-m-d H:i')
                ->timezone(AcademyContextService::getTimezone())
                ->helperText('اختر التاريخ والوقت الجديد للجلسة التجريبية')
                ->rules([
                    function (?QuranSession $record) {
                        return function (string $attribute, $value, \Closure $fail) use ($record) {
                            if (! $value || ! $record) {
                                return;
                            }

                            $scheduledAt = Carbon::parse($value);

                            // Check if date is in the past
                            if ($scheduledAt->isPast()) {
                                $fail('لا يمكن جدولة الجلسة في وقت ماضي');
                            }

                            // Check for session conflicts
                            $conflictData = [
                                'scheduled_at' => $scheduledAt,
                                'duration_minutes' => 30, // Trial sessions are 30 minutes
                                'quran_teacher_id' => Auth::id(),
                            ];

                            try {
                                $this->validateSessionConflicts($conflictData, $record->id);
                            } catch (\Exception $e) {
                                $fail($e->getMessage());
                            }
                        };
                    },
                ])
                ->default(fn (?QuranSession $record) => $record?->scheduled_at),

            Forms\Components\Textarea::make('description')
                ->label('ملاحظات إضافية')
                ->rows(3)
                ->maxLength(500)
                ->placeholder('اكتب أي ملاحظات للطالب حول تعديل الموعد...')
                ->helperText('سيتم إرسال هذه الملاحظات مع إشعار تغيير الموعد'),
        ];
    }

    /**
     * Header actions for creating sessions - removed as scheduling is now done via circles section
     */
    protected function headerActions(): array
    {
        return [
            // No actions - scheduling is handled by the circles management section
        ];
    }

    /**
     * Override resolveRecord to handle prefixed event IDs
     * This is called by Filament when clicking on calendar events
     * Handles both 'quran-{id}' and 'academic-{id}' formats
     */
    public function resolveRecord(int | string $key): Model
    {
        // Handle Quran sessions with 'quran-' prefix
        if (is_string($key) && str_starts_with($key, 'quran-')) {
            $id = substr($key, 6); // Remove 'quran-' prefix
            $record = QuranSession::find($id);

            if (! $record) {
                throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(QuranSession::class, [$id]);
            }

            return $record;
        }

        // Handle Academic sessions with 'academic-' prefix
        if (is_string($key) && str_starts_with($key, 'academic-')) {
            $id = substr($key, 9); // Remove 'academic-' prefix
            $record = AcademicSession::find($id);

            if (! $record) {
                throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(AcademicSession::class, [$id]);
            }

            return $record;
        }

        // Handle Interactive Course sessions with 'course-' prefix
        if (is_string($key) && str_starts_with($key, 'course-')) {
            $id = substr($key, 7); // Remove 'course-' prefix
            $record = InteractiveCourseSession::find($id);

            if (! $record) {
                throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(InteractiveCourseSession::class, [$id]);
            }

            return $record;
        }

        // Fallback: try to resolve using parent method (shouldn't happen with our prefixed IDs)
        return parent::resolveRecord($key);
    }

    /**
     * Helper method for resolving event records in modal actions
     * Delegates to resolveRecord for consistency
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
                    // Show edit action for all session types (Quran, Academic, Course)
                    $eventId = $arguments['event']['id'] ?? null;
                    if (!$eventId) {
                        return false;
                    }

                    // Allow editing for all session types
                    return str_starts_with($eventId, 'quran-')
                        || str_starts_with($eventId, 'academic-')
                        || str_starts_with($eventId, 'course-');
                })
                ->modalHeading(function (array $arguments) {
                    $eventId = $arguments['event']['id'] ?? null;
                    $record = $eventId ? $this->resolveEventRecord($eventId) : null;

                    if (!$record) {
                        return 'تعديل الجلسة';
                    }

                    // Determine heading based on session type
                    if ($record instanceof \App\Models\QuranSession) {
                        return $record->session_type === 'trial'
                            ? 'تعديل موعد الجلسة التجريبية'
                            : 'تعديل بيانات الجلسة';
                    } elseif ($record instanceof \App\Models\InteractiveCourseSession) {
                        return 'تعديل جلسة الدورة التفاعلية';
                    } else {
                        return 'تعديل الدرس الأكاديمي';
                    }
                })
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

                    // All session types now use scheduled_at
                    $record->update([
                        'scheduled_at' => $scheduledAt,
                        'description' => $data['description'] ?? $record->description,
                    ]);

                    // Also update the linked trial request if it exists (for trial sessions)
                    if ($record instanceof \App\Models\QuranSession && $record->session_type === 'trial' && $record->trial_request_id) {
                        $trialRequest = \App\Models\QuranTrialRequest::find($record->trial_request_id);
                        if ($trialRequest) {
                            $trialRequest->update([
                                'scheduled_at' => $scheduledAt,
                                'teacher_response' => $data['description'] ?? $trialRequest->teacher_response,
                            ]);
                        }
                    }

                    Notification::make()
                        ->title('تم تحديث الجلسة بنجاح')
                        ->success()
                        ->send();

                    // Refresh calendar to show updated data
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

                            // Quran sessions
                            if ($record instanceof \App\Models\QuranSession) {
                                if ($record->session_type === 'trial') {
                                    return route('filament.teacher.resources.quran-trial-requests.edit', [
                                        'tenant' => filament()->getTenant(),
                                        'record' => $record->trial_request_id,
                                    ]);
                                } else {
                                    return route('filament.teacher.resources.quran-sessions.edit', [
                                        'tenant' => filament()->getTenant(),
                                        'record' => $record,
                                    ]);
                                }
                            }

                            // Interactive Course sessions
                            if ($record instanceof \App\Models\InteractiveCourseSession) {
                                return route("filament.{$panelId}.resources.interactive-course-sessions.edit", [
                                    'tenant' => filament()->getTenant(),
                                    'record' => $record,
                                ]);
                            }

                            // Academic sessions
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

                            // Check permissions for different session types
                            if ($record instanceof \App\Models\QuranSession) {
                                return Auth::id() === $record->quran_teacher_id;
                            }

                            if ($record instanceof \App\Models\InteractiveCourseSession) {
                                $user = Auth::user();
                                return $user->academicTeacherProfile
                                    && $record->course
                                    && $record->course->assigned_teacher_id === $user->academicTeacherProfile->id;
                            }

                            // Academic sessions
                            $user = Auth::user();
                            return $user->academicTeacherProfile
                                && $record->academic_teacher_id === $user->academicTeacherProfile->id;
                        }),
                ]),

            Actions\DeleteAction::make('deleteSession')
                ->label('حذف')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalDescription('هل أنت متأكد من حذف هذه الجلسة؟ لن يمكن التراجع عن هذا الإجراء.')
                ->successNotificationTitle('تم حذف الجلسة بنجاح')
                ->visible(function (array $arguments): bool {
                    // Only show delete action for Quran sessions
                    $eventId = $arguments['event']['id'] ?? null;
                    if (!$eventId || !str_starts_with($eventId, 'quran-')) {
                        return false;
                    }

                    $record = $this->resolveEventRecord($eventId);
                    if (!$record) {
                        return false;
                    }

                    return in_array($record->session_type, ['individual', 'group']) && Auth::id() === $record->quran_teacher_id;
                })
                ->before(function (array $arguments) {
                    $eventId = $arguments['event']['id'] ?? null;
                    $record = $eventId ? $this->resolveEventRecord($eventId) : null;

                    if (!$record) {
                        return;
                    }

                    // Update the circle to recalculate available sessions
                    if ($record->individualCircle) {
                        $record->individualCircle->updateSessionCounts();
                    }
                    // For group sessions, we don't need to update counts as they're tracked differently
                    // Group circles use the schedule-based system for session management
                }),

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
     * This is called when clicking on a calendar event
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

                if ($record instanceof QuranSession && $record->session_type === 'trial') {
                    $studentName = $record->student->name ??
                                 $record->trialRequest->student_name ??
                                 'طالب تجريبي';
                    return "تفاصيل الجلسة التجريبية: {$studentName}";
                }

                return "تفاصيل الجلسة: {$record->title}";
            })
            ->infolist(function (array $arguments) {
                $eventId = $arguments['event']['id'] ?? null;
                $record = $eventId ? $this->resolveEventRecord($eventId) : null;

                if (!$record) {
                    return [];
                }

            $isQuranSession = $record instanceof QuranSession;
            $isTrial = $isQuranSession && $record->session_type === 'trial';

                if ($isTrial) {
                    return [
                        \Filament\Infolists\Components\TextEntry::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->state($record->scheduled_at)
                            ->dateTime()
                            ->timezone(AcademyContextService::getTimezone()),
                        \Filament\Infolists\Components\TextEntry::make('description')
                            ->label('ملاحظات إضافية')
                            ->state($record->description)
                            ->placeholder('لا توجد ملاحظات'),
                    ];
                } else {
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
                                'teacher_absent' => 'danger',
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
                                'teacher_absent' => 'غياب المعلم',
                                default => $state instanceof \App\Enums\SessionStatus ? $state->value : $state,
                            }),
                    ];
                }
            })
            ->modalFooterActions(function (Action $action): array {
                // Get the record that was already resolved by the ViewAction
                $record = $this->record;

                if (!$record) {
                    return [$action->getModalCancelAction()];
                }

                $isQuranSession = $record instanceof QuranSession;
                $isCourseSession = $record instanceof InteractiveCourseSession;

                // Build URL for view full page
                if ($isQuranSession) {
                    if ($record->session_type === 'trial') {
                        $viewFullUrl = route('filament.teacher.resources.quran-trial-requests.view', [
                            'tenant' => filament()->getTenant(),
                            'record' => $record->trial_request_id,
                        ]);
                    } else {
                        $viewFullUrl = route('filament.teacher.resources.quran-sessions.view', [
                            'tenant' => filament()->getTenant(),
                            'record' => $record,
                        ]);
                    }
                } elseif ($isCourseSession) {
                    // Interactive Course sessions - link to parent course view
                    $panelId = filament()->getCurrentPanel()->getId();
                    $viewFullUrl = route("filament.{$panelId}.resources.interactive-courses.view", [
                        'tenant' => filament()->getTenant(),
                        'record' => $record->course_id,
                    ]);
                } else {
                    // Academic sessions
                    $panelId = filament()->getCurrentPanel()->getId();
                    $viewFullUrl = route("filament.{$panelId}.resources.academic-sessions.view", [
                        'tenant' => filament()->getTenant(),
                        'record' => $record,
                    ]);
                }

                // Build event ID with prefix for editSession action
                if ($isQuranSession) {
                    $eventId = 'quran-' . $record->id;
                } elseif ($isCourseSession) {
                    $eventId = 'course-' . $record->id;
                } else {
                    $eventId = 'academic-' . $record->id;
                }

                $editButton = Action::make('edit')
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->action(function () use ($eventId) {
                        // Replace current view modal with edit modal
                        $this->replaceMountedAction('editSession', ['event' => ['id' => $eventId]]);
                    })
                    ->visible(function () use ($record, $isQuranSession, $isCourseSession) {
                        // Don't show edit button for passed sessions
                        $scheduledAt = $isCourseSession ? $record->scheduled_at : $record->scheduled_at;
                        if ($scheduledAt && $scheduledAt < Carbon::now()) {
                            return false;
                        }

                        // Show edit for Quran sessions
                        if ($isQuranSession) {
                            return Auth::id() === $record->quran_teacher_id;
                        }

                        // Show edit for Interactive Course sessions
                        if ($isCourseSession) {
                            $user = Auth::user();
                            if (!$user->academicTeacherProfile) {
                                return false;
                            }
                            // Check if teacher is assigned to this course
                            return $record->course && $record->course->assigned_teacher_id === $user->academicTeacherProfile->id;
                        }

                        // Show edit for Academic individual sessions
                        $user = Auth::user();
                        if (!$user->academicTeacherProfile) {
                            return false;
                        }

                        // Don't allow editing AcademicSession records linked to courses
                        if ($record->interactive_course_id) {
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
     * Handle date click - disabled (we use double-click instead)
     */
    public function onDateClick(array $data): void
    {
        // Disabled - we use showDaySessionsModal for double-click instead
        return;
    }

    /**
     * Show all sessions for a specific day (called on double-click)
     */
    public function showDaySessionsModal(string $dateStr): void
    {
        $clickedDate = Carbon::parse($dateStr);
        $user = Auth::user();

        if (!$user) {
            return;
        }

        // Fetch all sessions for this day
        $quranSessions = collect();
        $academicSessions = collect();
        $courseSessions = collect();

        if ($user->user_type === 'quran_teacher') {
            $quranSessions = QuranSession::where('quran_teacher_id', $user->id)
                ->whereDate('scheduled_at', $clickedDate->toDateString())
                ->whereNotNull('scheduled_at')
                ->with(['student', 'circle', 'individualCircle'])
                ->orderBy('scheduled_at')
                ->get();
        }

        if ($user->academicTeacherProfile) {
            $academicSessions = AcademicSession::where('academic_teacher_id', $user->academicTeacherProfile->id)
                ->whereDate('scheduled_at', $clickedDate->toDateString())
                ->whereNotNull('scheduled_at')
                ->with(['student', 'academicIndividualLesson.academicSubject'])
                ->orderBy('scheduled_at')
                ->get();

            // Fetch interactive course sessions
            $courseSessions = InteractiveCourseSession::whereHas('course', function ($query) use ($user) {
                    $query->where('assigned_teacher_id', $user->academicTeacherProfile->id);
                })
                ->whereDate('scheduled_at', $clickedDate->toDateString())
                ->whereNotNull('scheduled_at')
                ->with(['course.subject'])
                ->orderBy('scheduled_at')
                ->get();
        }

        $allSessions = $quranSessions->merge($academicSessions)->merge($courseSessions);

        // Set the selected date
        $this->selectedDate = $dateStr;

        // Prepare sessions array for the view
        $this->daySessions = $allSessions->map(function ($session) use ($user) {
            $isQuran = $session instanceof QuranSession;
            $isCourse = $session instanceof InteractiveCourseSession;

            // Get scheduled_at for different session types
            if ($isCourse) {
                $scheduledAt = $session->scheduled_at;
            } else {
                $scheduledAt = $session->scheduled_at;
            }

            $isPassed = $scheduledAt < Carbon::now();

            $sessionData = [
                'type' => $isQuran ? 'quran' : ($isCourse ? 'course' : 'academic'),
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

            if ($isQuran) {
                $sessionData['sessionType'] = match($session->session_type) {
                    'individual' => 'جلسة فردية',
                    'group' => 'حلقة جماعية',
                    'trial' => 'جلسة تجريبية',
                    default => 'جلسة قرآنية',
                };
                // Use proper color based on session type and status
                $sessionData['color'] = $this->getSessionColor(
                    $session->session_type,
                    $sessionData['status'],
                    false
                );
                $sessionData['eventId'] = 'quran-' . $session->id;
                $sessionData['canEdit'] = Auth::id() === $session->quran_teacher_id;
            } elseif ($isCourse) {
                $sessionData['sessionType'] = 'دورة تفاعلية';
                $sessionData['subject'] = $session->course?->subject?->name ?? 'مادة';
                // Use proper color for interactive course sessions
                $sessionData['color'] = $this->getSessionColor(
                    'interactive_course',
                    $sessionData['status'],
                    true
                );
                $sessionData['eventId'] = 'course-' . $session->id;
                $sessionData['canEdit'] = false; // Course sessions cannot be edited from here
            } else {
                $sessionData['sessionType'] = 'درس أكاديمي';
                $sessionData['subject'] = $session->academicIndividualLesson?->academicSubject?->name ?? 'مادة';
                // Use proper color for academic individual sessions
                $sessionData['color'] = $this->getSessionColor(
                    'individual',
                    $sessionData['status'],
                    true
                );
                $sessionData['eventId'] = 'academic-' . $session->id;
                $sessionData['canEdit'] = $user->academicTeacherProfile && $user->academicTeacherProfile->id === $session->academic_teacher_id;
            }

            return $sessionData;
        })->toArray();

        // Mount the action to show the modal
        $this->mountAction('viewDaySessions');
    }

    /**
     * Edit session from day list (legacy method - kept for compatibility)
     */
    public function editSessionFromList(string $eventId): void
    {
        $this->mountAction('editSession', ['event' => ['id' => $eventId]]);
    }

    /**
     * Edit session from day modal
     * Closes the day sessions modal and opens the edit modal
     */
    public function editSessionFromDayModal(string $eventId): void
    {
        // Resolve and set the record first
        $record = $this->resolveEventRecord($eventId);

        if (!$record) {
            Notification::make()
                ->title('خطأ')
                ->body('لم يتم العثور على الجلسة')
                ->danger()
                ->send();
            return;
        }

        // Set the record on the widget
        $this->record = $record;

        // Replace the current modal with the edit modal
        $this->replaceMountedAction('editSession', ['event' => ['id' => $eventId]]);
    }

    /**
     * Handle event drop (drag and drop)
     */
    public function onEventDrop(array $event, array $oldEvent, array $relatedEvents, array $delta, ?array $oldResource, ?array $newResource): bool
    {
        // Parse event ID to determine model type
        $eventId = $event['id'];
        $modelType = $event['extendedProps']['modelType'] ?? 'quran';

        // Extract numeric ID
        $numericId = (int) str_replace(['quran-', 'academic-'], '', $eventId);

        // Find the appropriate record
        if ($modelType === 'academic') {
            $record = AcademicSession::find($numericId);
            $sessionType = 'academic';
        } else {
            $record = QuranSession::find($numericId);
            $sessionType = 'quran';
        }

        if (! $record) {
            return false;
        }

        // Check if session is movable
        if ($modelType === 'quran') {
            // Both individual and group Quran sessions can be moved
            if (! in_array($record->session_type, ['individual', 'group'])) {
                Notification::make()
                    ->title('غير مسموح')
                    ->body('نوع الجلسة غير مدعوم للتحريك.')
                    ->warning()
                    ->send();

                $this->dispatch('refresh');
                return false;
            }
        } else {
            // For academic sessions, only private lessons can be moved (not interactive courses)
            if ($record->interactive_course_id) {
                Notification::make()
                    ->title('غير مسموح')
                    ->body('لا يمكن تحريك جلسات الدورات التفاعلية.')
                    ->warning()
                    ->send();

                $this->dispatch('refresh');
                return false;
            }
        }

        $newStart = Carbon::parse($event['start']);
        $newEnd = Carbon::parse($event['end']);
        $duration = $newStart->diffInMinutes($newEnd);

        // Validate the new date is not in the past
        if ($newStart->isPast()) {
            Notification::make()
                ->title('غير مسموح')
                ->body('لا يمكن جدولة الجلسات في الماضي. يرجى اختيار تاريخ ووقت مستقبلي.')
                ->warning()
                ->send();

            $this->dispatch('refresh');
            return false;
        }

        // Validate subscription constraints for individual sessions
        if ($modelType === 'quran' && $record->session_type === 'individual' && $record->individual_circle_id) {
            if (! $this->validateQuranSubscriptionConstraints($record, $newStart)) {
                $this->dispatch('refresh');
                return false;
            }
        } elseif ($modelType === 'academic' && $record->subscription_id) {
            if (! $this->validateAcademicSubscriptionConstraints($record, $newStart)) {
                $this->dispatch('refresh');
                return false;
            }
        }

        // Validate the new time doesn't conflict using shared trait
        try {
            $user = Auth::user();
            $teacherId = $modelType === 'quran'
                ? $user->id
                : $user->academicTeacherProfile->id;

            $conflictData = [
                'scheduled_at' => $newStart,
                'duration_minutes' => $duration,
                'teacher_id' => $teacherId,
            ];

            $this->validateSessionConflicts($conflictData, $numericId, $sessionType);
        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في تحديث الجلسة')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->dispatch('refresh');
            return false;
        }

        // If we reach here, all validations passed
        try {
            // Update the record
            $record->update([
                'scheduled_at' => $newStart,
                'duration_minutes' => $duration,
            ]);

            // Call parent to allow the visual change
            $result = parent::onEventDrop($event, $oldEvent, $relatedEvents, $delta, $oldResource, $newResource);

            Notification::make()
                ->title('تم تحديث موعد الجلسة بنجاح')
                ->body($modelType === 'academic' ? 'تم تحديث موعد الدرس بنجاح' : 'تم تحديث موعد الجلسة بنجاح')
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
     * Validate Quran subscription constraints
     */
    protected function validateQuranSubscriptionConstraints(QuranSession $record, Carbon $newStart): bool
    {
        $circle = \App\Models\QuranIndividualCircle::find($record->individual_circle_id);

        if (! $circle || ! $circle->subscription) {
            return true;
        }

        $subscription = $circle->subscription;

        // Check if subscription is active
        if ($subscription->subscription_status !== 'active') {
            Notification::make()
                ->title('غير مسموح')
                ->body('الاشتراك غير نشط. لا يمكن تحريك الجلسة.')
                ->danger()
                ->send();
            return false;
        }

        // Check if new date is before subscription start
        if ($subscription->starts_at && $newStart->isBefore($subscription->starts_at)) {
            Notification::make()
                ->title('غير مسموح')
                ->body('لا يمكن جدولة الجلسة قبل تاريخ بدء الاشتراك ('.$subscription->starts_at->format('Y/m/d').')')
                ->danger()
                ->send();
            return false;
        }

        // Calculate subscription end date based on billing cycle
        $subscriptionEndDate = null;
        if ($subscription->starts_at && $subscription->billing_cycle) {
            $subscriptionEndDate = match ($subscription->billing_cycle) {
                'weekly' => $subscription->starts_at->copy()->addWeek(),
                'monthly' => $subscription->starts_at->copy()->addMonth(),
                'quarterly' => $subscription->starts_at->copy()->addMonths(3),
                'yearly' => $subscription->starts_at->copy()->addYear(),
                default => null,
            };
        }

        // Check if new date is beyond subscription billing period
        if ($subscriptionEndDate && $newStart->isAfter($subscriptionEndDate)) {
            Notification::make()
                ->title('غير مسموح')
                ->body('لا يمكن جدولة الجلسة بعد نهاية دورة الفوترة ('.$subscriptionEndDate->format('Y/m/d').'). يرجى تجديد الاشتراك أولاً.')
                ->danger()
                ->send();
            return false;
        }

        // Check if subscription has remaining sessions
        if ($subscription->sessions_remaining <= 0) {
            Notification::make()
                ->title('غير مسموح')
                ->body('لا توجد جلسات متبقية في الاشتراك. يرجى تجديد الاشتراك أولاً.')
                ->danger()
                ->send();
            return false;
        }

        return true;
    }

    /**
     * Validate Academic subscription constraints
     */
    protected function validateAcademicSubscriptionConstraints(AcademicSession $record, Carbon $newStart): bool
    {
        $subscription = $record->subscription;

        if (! $subscription) {
            return true;
        }

        // Check if subscription is active
        if ($subscription->subscription_status !== 'active') {
            Notification::make()
                ->title('غير مسموح')
                ->body('الاشتراك غير نشط. لا يمكن تحريك الجلسة.')
                ->danger()
                ->send();
            return false;
        }

        // Check if new date is before subscription start
        if ($subscription->starts_at && $newStart->isBefore($subscription->starts_at)) {
            Notification::make()
                ->title('غير مسموح')
                ->body('لا يمكن جدولة الجلسة قبل تاريخ بدء الاشتراك ('.$subscription->starts_at->format('Y/m/d').')')
                ->danger()
                ->send();
            return false;
        }

        // Check if new date is after subscription end
        if ($subscription->ends_at && $newStart->isAfter($subscription->ends_at)) {
            Notification::make()
                ->title('غير مسموح')
                ->body('لا يمكن جدولة الجلسة بعد نهاية الاشتراك ('.$subscription->ends_at->format('Y/m/d').')')
                ->danger()
                ->send();
            return false;
        }

        // Check if subscription has remaining sessions
        if ($subscription->sessions_remaining <= 0) {
            Notification::make()
                ->title('غير مسموح')
                ->body('لا توجد جلسات متبقية في الاشتراك. يرجى تجديد الاشتراك أولاً.')
                ->danger()
                ->send();
            return false;
        }

        return true;
    }

    /**
     * Handle event resize
     */
    public function onEventResize(array $event, array $oldEvent, array $relatedEvents, array $endDelta, array $startDelta): bool
    {
        // Parse event ID to determine model type
        $eventId = $event['id'];
        $modelType = $event['extendedProps']['modelType'] ?? 'quran';

        // Extract numeric ID
        $numericId = (int) str_replace(['quran-', 'academic-'], '', $eventId);

        // Find the appropriate record
        if ($modelType === 'academic') {
            $record = AcademicSession::find($numericId);
            $sessionType = 'academic';
        } else {
            $record = QuranSession::find($numericId);
            $sessionType = 'quran';
        }

        if (! $record) {
            return false;
        }

        // Check if session is resizable
        if ($modelType === 'quran') {
            // Group sessions cannot be resized
            if ($record->session_type === 'group') {
                Notification::make()
                    ->title('غير مسموح')
                    ->body('لا يمكن تغيير مدة جلسات الحلقات الجماعية يدوياً.')
                    ->warning()
                    ->send();

                $this->dispatch('refresh');
                return false;
            }

            // Only individual sessions can be resized
            if ($record->session_type !== 'individual') {
                Notification::make()
                    ->title('غير مسموح')
                    ->body('يمكن تغيير مدة الجلسات الفردية فقط.')
                    ->warning()
                    ->send();

                $this->dispatch('refresh');
                return false;
            }
        } else {
            // For academic sessions, interactive course sessions cannot be resized
            if ($record->interactive_course_id) {
                Notification::make()
                    ->title('غير مسموح')
                    ->body('لا يمكن تغيير مدة جلسات الدورات التفاعلية.')
                    ->warning()
                    ->send();

                $this->dispatch('refresh');
                return false;
            }
        }

        $newStart = Carbon::parse($event['start']);
        $newEnd = Carbon::parse($event['end']);
        $newDuration = $newStart->diffInMinutes($newEnd);

        // Validate the new date is not in the past
        if ($newStart->isPast()) {
            Notification::make()
                ->title('غير مسموح')
                ->body('لا يمكن تغيير مدة الجلسات في الماضي.')
                ->warning()
                ->send();

            $this->dispatch('refresh');
            return false;
        }

        // Validate duration is acceptable
        if (! in_array($newDuration, [30, 45, 60, 90, 120])) {
            Notification::make()
                ->title('خطأ في المدة')
                ->body('مدة الجلسة يجب أن تكون 30، 45، 60، 90، أو 120 دقيقة')
                ->danger()
                ->send();

            $this->dispatch('refresh');
            return false;
        }

        // Validate no conflicts with new duration using shared trait
        try {
            $user = Auth::user();
            $teacherId = $modelType === 'quran'
                ? $user->id
                : $user->academicTeacherProfile->id;

            $conflictData = [
                'scheduled_at' => $newStart,
                'duration_minutes' => $newDuration,
                'teacher_id' => $teacherId,
            ];

            $this->validateSessionConflicts($conflictData, $numericId, $sessionType);
        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في تحديث الجلسة')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->dispatch('refresh');
            return false;
        }

        // If we reach here, all validations passed
        try {
            // Update duration
            $record->update([
                'duration_minutes' => $newDuration,
            ]);

            // Call parent to allow the visual change
            $result = parent::onEventResize($event, $oldEvent, $relatedEvents, $endDelta, $startDelta);

            Notification::make()
                ->title('تم تحديث مدة الجلسة بنجاح')
                ->body($modelType === 'academic' ? 'تم تحديث مدة الدرس بنجاح' : 'تم تحديث مدة الجلسة بنجاح')
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
        // Force the calendar to refresh its events
        $this->dispatch('refresh');
    }
}
