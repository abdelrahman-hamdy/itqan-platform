<?php

namespace App\Filament\Teacher\Widgets;

use App\Filament\Shared\Widgets\BaseFullCalendarWidget;
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

/**
 * Quran Teacher Calendar Widget
 *
 * Extends BaseFullCalendarWidget to provide calendar functionality for Quran teachers.
 * Handles Quran sessions (individual, group, trial) and optionally academic sessions
 * for teachers who have both roles.
 */
class TeacherCalendarWidget extends BaseFullCalendarWidget
{
    // Note: FormatsCalendarData and ValidatesConflicts traits are inherited from BaseFullCalendarWidget

    // Default model for widget initialization
    public Model|string|null $model = QuranSession::class;

    // Properties for circle filtering (Quran-specific)
    public ?int $selectedCircleId = null;

    public ?string $selectedCircleType = null;

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

        // Convert from UTC storage to academy timezone for display
        $scheduledAt = $session->scheduled_at instanceof \Carbon\Carbon
            ? $session->scheduled_at->copy()->timezone($timezone)
            : \Carbon\Carbon::parse($session->scheduled_at)->timezone($timezone);

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

        // Convert from UTC storage to academy timezone for display
        $scheduledAt = $session->scheduled_at instanceof \Carbon\Carbon
            ? $session->scheduled_at->copy()->timezone($timezone)
            : \Carbon\Carbon::parse($session->scheduled_at)->timezone($timezone);

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

        // Convert from UTC storage to academy timezone for display
        $scheduledAtTz = $scheduledAt instanceof \Carbon\Carbon
            ? $scheduledAt->copy()->timezone($timezone)
            : \Carbon\Carbon::parse($scheduledAt)->timezone($timezone);

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
                'isMovable' => true, // Course sessions can be moved within course date range
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

    // headerActions(), modalActions(), and viewAction() are inherited from BaseFullCalendarWidget

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

    // resolveEventRecord() is inherited from BaseFullCalendarWidget

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

        // Get academy timezone for proper display
        $timezone = AcademyContextService::getTimezone();

        // Prepare sessions array for the view
        $this->daySessions = $allSessions->map(function ($session) use ($user, $timezone) {
            $isQuran = $session instanceof QuranSession;
            $isCourse = $session instanceof InteractiveCourseSession;

            // Get scheduled_at (all session types now use scheduled_at consistently)
            $scheduledAt = $session->scheduled_at;

            // Convert to academy timezone for display
            $scheduledAtInTz = $scheduledAt instanceof Carbon
                ? $scheduledAt->copy()->timezone($timezone)
                : Carbon::parse($scheduledAt, 'UTC')->timezone($timezone);

            $isPassed = $scheduledAt < Carbon::now();

            $sessionData = [
                'type' => $isQuran ? 'quran' : ($isCourse ? 'course' : 'academic'),
                'isPassed' => $isPassed,
                'time' => $scheduledAtInTz->format('h:i A'),
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

        // Parse times with academy timezone context to prevent offset issues
        $timezone = AcademyContextService::getTimezone();
        $newStart = Carbon::parse($event['start'], $timezone);
        $newEnd = Carbon::parse($event['end'], $timezone);
        $duration = $newStart->diffInMinutes($newEnd);

        // Validate the new date is not in the past
        if ($newStart->isPast()) {
            Notification::make()
                ->title('غير مسموح')
                ->body('لا يمكن جدولة الجلسات في الماضي. يرجى اختيار تاريخ ووقت مستقبلي.')
                ->warning()
                ->send();

            return true; // Revert - past date not allowed
        }

        // Handle interactive course sessions
        if ($modelType === 'course') {
            $numericId = (int) str_replace('course-', '', $eventId);
            $record = InteractiveCourseSession::with('course')->find($numericId);

            if (!$record) {
                return true; // Revert - record not found
            }

            // Validate new date is not before the course start date
            if ($record->course && $record->course->start_date) {
                $courseStartDate = Carbon::parse($record->course->start_date)->startOfDay();
                if ($newStart->startOfDay()->lt($courseStartDate)) {
                    Notification::make()
                        ->title('غير مسموح')
                        ->body('لا يمكن جدولة الجلسة قبل تاريخ بداية الدورة (' . $courseStartDate->format('Y-m-d') . ')')
                        ->warning()
                        ->send();

                    return true; // Revert - validation failed
                }
            }

            // Validate new date is not after course end date (if set)
            if ($record->course && $record->course->end_date) {
                $courseEndDate = Carbon::parse($record->course->end_date)->endOfDay();
                if ($newStart->gt($courseEndDate)) {
                    Notification::make()
                        ->title('غير مسموح')
                        ->body('لا يمكن جدولة الجلسة بعد تاريخ نهاية الدورة (' . $courseEndDate->format('Y-m-d') . ')')
                        ->warning()
                        ->send();

                    return true; // Revert - validation failed
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

                $this->validateSessionConflicts($conflictData, $numericId, 'course');
            } catch (\Exception $e) {
                Notification::make()
                    ->title('خطأ في تحديث الجلسة')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();

                return true; // Revert - conflict found
            }

            $record->update([
                'scheduled_at' => $newStart,
                'duration_minutes' => $duration,
            ]);

            Notification::make()
                ->title('تم تحديث موعد جلسة الدورة بنجاح')
                ->success()
                ->send();

            // Return FALSE to tell FullCalendar to KEEP the new position
            return false;
        }

        // Extract numeric ID for quran/academic sessions
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
            return true; // Revert - record not found
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

                return true; // Revert - session type not movable
            }
        } else {
            // For academic sessions linked to interactive courses, don't allow moving from here
            // (they should be managed via the course's session management)
            if ($record->interactive_course_id) {
                Notification::make()
                    ->title('غير مسموح')
                    ->body('لا يمكن تحريك جلسات الدورات التفاعلية من هنا.')
                    ->warning()
                    ->send();

                return true; // Revert - interactive course sessions managed elsewhere
            }
        }

        // Validate subscription constraints for individual sessions
        if ($modelType === 'quran' && $record->session_type === 'individual' && $record->individual_circle_id) {
            if (! $this->validateQuranSubscriptionConstraints($record, $newStart)) {
                return true; // Revert - subscription validation failed
            }
        } elseif ($modelType === 'academic' && $record->subscription_id) {
            if (! $this->validateAcademicSubscriptionConstraints($record, $newStart)) {
                return true; // Revert - subscription validation failed
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

            return true; // Revert - conflict found
        }

        // If we reach here, all validations passed
        try {
            // Update the record
            $record->update([
                'scheduled_at' => $newStart,
                'duration_minutes' => $duration,
            ]);

            Notification::make()
                ->title('تم تحديث موعد الجلسة بنجاح')
                ->body($modelType === 'academic' ? 'تم تحديث موعد الدرس بنجاح' : 'تم تحديث موعد الجلسة بنجاح')
                ->success()
                ->send();

            // Return FALSE to tell FullCalendar to KEEP the new position
            return false;

        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في تحديث الجلسة')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return true; // Revert - update failed
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
        if ($subscription->status !== 'active') {
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
        if ($subscription->status !== 'active') {
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

        // Parse times with academy timezone context to prevent offset issues
        $timezone = AcademyContextService::getTimezone();
        $newStart = Carbon::parse($event['start'], $timezone);
        $newEnd = Carbon::parse($event['end'], $timezone);
        $newDuration = $newStart->diffInMinutes($newEnd);

        // Validate duration is acceptable
        if (! in_array($newDuration, [30, 45, 60, 90, 120])) {
            Notification::make()
                ->title('خطأ في المدة')
                ->body('مدة الجلسة يجب أن تكون 30، 45، 60، 90، أو 120 دقيقة')
                ->danger()
                ->send();

            return true; // Revert - invalid duration
        }

        // Validate the new date is not in the past
        if ($newStart->isPast()) {
            Notification::make()
                ->title('غير مسموح')
                ->body('لا يمكن تغيير مدة الجلسات في الماضي.')
                ->warning()
                ->send();

            return true; // Revert - past date not allowed
        }

        // Handle interactive course sessions
        if ($modelType === 'course') {
            $numericId = (int) str_replace('course-', '', $eventId);
            $record = InteractiveCourseSession::with('course')->find($numericId);

            if (!$record) {
                return true; // Revert - record not found
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

                $this->validateSessionConflicts($conflictData, $numericId, 'course');
            } catch (\Exception $e) {
                Notification::make()
                    ->title('خطأ في تحديث الجلسة')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();

                return true; // Revert - conflict found
            }

            $record->update([
                'duration_minutes' => $newDuration,
            ]);

            Notification::make()
                ->title('تم تحديث مدة جلسة الدورة بنجاح')
                ->success()
                ->send();

            // Return FALSE to tell FullCalendar to KEEP the new size
            return false;
        }

        // Extract numeric ID for quran/academic sessions
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
            return true; // Revert - record not found
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

                return true; // Revert - group sessions not resizable
            }

            // Only individual sessions can be resized
            if ($record->session_type !== 'individual') {
                Notification::make()
                    ->title('غير مسموح')
                    ->body('يمكن تغيير مدة الجلسات الفردية فقط.')
                    ->warning()
                    ->send();

                return true; // Revert - only individual sessions resizable
            }
        } else {
            // For academic sessions, interactive course sessions cannot be resized from AcademicSession model
            if ($record->interactive_course_id) {
                Notification::make()
                    ->title('غير مسموح')
                    ->body('لا يمكن تغيير مدة جلسات الدورات التفاعلية من هنا.')
                    ->warning()
                    ->send();

                return true; // Revert - interactive course sessions managed elsewhere
            }
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

            return true; // Revert - conflict found
        }

        // If we reach here, all validations passed
        try {
            // Update duration
            $record->update([
                'duration_minutes' => $newDuration,
            ]);

            Notification::make()
                ->title('تم تحديث مدة الجلسة بنجاح')
                ->body($modelType === 'academic' ? 'تم تحديث مدة الدرس بنجاح' : 'تم تحديث مدة الجلسة بنجاح')
                ->success()
                ->send();

            // Return FALSE to tell FullCalendar to KEEP the new size
            return false;

        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في تحديث الجلسة')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return true; // Revert - update failed
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
