<?php

namespace App\Filament\AcademicTeacher\Pages;

use App\Models\AcademicSession;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class AcademicCalendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'التقويم';

    protected static ?string $title = 'تقويم المعلم الأكاديمي';

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.academic-teacher.pages.academic-calendar';

    public ?int $selectedItemId = null;

    public string $selectedItemType = 'private_lesson'; // 'private_lesson' or 'interactive_course'

    public string $activeTab = 'private_lessons'; // Tab state for lessons, courses

    // Scheduling form properties
    public array $scheduleDays = [];

    public ?string $scheduleTime = null;

    public ?string $scheduleStartDate = null;

    public int $sessionCount = 4;

    /**
     * Get the widgets for this page
     */
    protected function getHeaderWidgets(): array
    {
        return [
            // Calendar widget will be rendered in the custom view
        ];
    }

    /**
     * Get actions for this page
     */
    protected function getActions(): array
    {
        return [
            //
        ];
    }

    /**
     * Get private lessons for the current academic teacher (Livewire Property)
     */
    public function getPrivateLessonsProperty(): Collection
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        if (! $teacherProfile) {
            return collect();
        }

        return \App\Models\AcademicSubscription::where('teacher_id', $teacherProfile->id)
            ->where('academy_id', $user->academy_id)
            ->whereIn('status', ['active', 'approved'])
            ->with(['student', 'subject', 'sessions'])
            ->get()
            ->map(function ($subscription) {
                // Use actual session records from database instead of calculations
                $allSessions = $subscription->sessions;
                $totalSessions = $allSessions->count();
                $scheduledSessions = $allSessions->filter(function ($session) {
                    return $session->status->value === 'scheduled' && ! is_null($session->scheduled_at);
                })->count();
                $unscheduledSessions = $allSessions->filter(function ($session) {
                    return $session->status->value === 'unscheduled' || is_null($session->scheduled_at);
                })->count();

                // Determine scheduling status (similar to Quran circles)
                $status = 'not_scheduled';
                if ($scheduledSessions > 0) {
                    if ($unscheduledSessions > 0) {
                        $status = 'partially_scheduled';
                    } else {
                        $status = 'fully_scheduled';
                    }
                }

                return [
                    'id' => $subscription->id,
                    'type' => 'private_lesson',
                    'name' => 'درس خاص - '.($subscription->subject_name ?? 'مادة أكاديمية'),
                    'status' => $status,
                    'total_sessions' => $totalSessions,
                    'sessions_scheduled' => $scheduledSessions,
                    'sessions_remaining' => $unscheduledSessions,
                    'student_name' => $subscription->student->name ?? 'طالب',
                    'subject_name' => $subscription->subject_name ?? 'مادة أكاديمية',
                ];
            });
    }

    /**
     * Get interactive courses for the current academic teacher
     */
    public function getInteractiveCoursesProperty(): Collection
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        if (! $teacherProfile) {
            return collect();
        }

        return InteractiveCourse::where('assigned_teacher_id', $teacherProfile->id)
            ->where('academy_id', $user->academy_id)
            ->whereIn('status', ['active', 'ongoing'])
            ->withCount(['enrollments', 'sessions as scheduled_sessions_count'])
            ->get();
    }

    /**
     * Get today's sessions for the current academic teacher
     */
    public function getTodaySessionsProperty(): Collection
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        if (! $teacherProfile) {
            return collect();
        }

        $today = now()->toDateString();

        // Get individual academic sessions for today
        $individualSessions = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->where('academy_id', $user->academy_id)
            ->whereDate('scheduled_at', $today)
            ->with(['student', 'academicIndividualLesson.academicSubject'])
            ->get();

        // Get interactive course sessions for today
        $courseSessions = InteractiveCourseSession::whereHas('course', function ($query) use ($teacherProfile, $user) {
            $query->where('assigned_teacher_id', $teacherProfile->id)
                ->where('academy_id', $user->academy_id);
        })
            ->whereDate('scheduled_date', $today)
            ->with(['course'])
            ->get();

        // Combine both types of sessions
        return $individualSessions->concat($courseSessions);
    }

    /**
     * Check if user can access this page
     */
    public static function canAccess(): bool
    {
        return Auth::check();
    }



    /**
     * Get session statistics for the top 4 boxes
     */
    public function getSessionStatistics(): array
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        if (! $teacherProfile) {
            return [
                ['title' => 'جلسات اليوم', 'value' => 0, 'icon' => 'heroicon-o-calendar-days', 'color' => 'primary'],
                ['title' => 'الجلسات القادمة', 'value' => 0, 'icon' => 'heroicon-o-clock', 'color' => 'warning'],
                ['title' => 'مكتملة هذا الشهر', 'value' => 0, 'icon' => 'heroicon-o-check-circle', 'color' => 'success'],
                ['title' => 'في الانتظار', 'value' => 0, 'icon' => 'heroicon-o-exclamation-triangle', 'color' => 'danger'],
            ];
        }

        // Get today's sessions
        $todaySessions = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->whereDate('scheduled_at', today())
            ->count();

        // Get upcoming sessions (next 7 days)
        $upcomingSessions = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->where('scheduled_at', '>', now())
            ->where('scheduled_at', '<=', now()->addDays(7))
            ->count();

        // Get completed sessions this month
        $completedThisMonth = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->whereMonth('scheduled_at', now()->month)
            ->whereYear('scheduled_at', now()->year)
            ->where('status', 'completed')
            ->count();

        // Get pending/unscheduled sessions
        $pendingSessions = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->where('status', 'pending')
            ->count();

        return [
            [
                'title' => 'جلسات اليوم',
                'value' => $todaySessions,
                'icon' => 'heroicon-o-calendar-days',
                'color' => 'primary',
            ],
            [
                'title' => 'الجلسات القادمة',
                'value' => $upcomingSessions,
                'icon' => 'heroicon-o-clock',
                'color' => 'warning',
            ],
            [
                'title' => 'مكتملة هذا الشهر',
                'value' => $completedThisMonth,
                'icon' => 'heroicon-o-check-circle',
                'color' => 'success',
            ],
            [
                'title' => 'في الانتظار',
                'value' => $pendingSessions,
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'danger',
            ],
        ];
    }

    /**
     * Get private lessons (academic subscriptions) for the teacher
     */
    public function getPrivateLessons(): Collection
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        if (! $teacherProfile) {
            return collect();
        }

        return \App\Models\AcademicSubscription::where('teacher_id', $teacherProfile->id)
            ->where('academy_id', $user->academy_id)
            ->whereIn('status', ['active', 'approved'])
            ->with(['student', 'subject', 'sessions'])
            ->get()
            ->map(function ($subscription) {
                // Use actual session records from database instead of calculations
                $allSessions = $subscription->sessions;
                $totalSessions = $allSessions->count();
                $scheduledSessions = $allSessions->filter(function ($session) {
                    return $session->status->value === 'scheduled' && ! is_null($session->scheduled_at);
                })->count();
                $unscheduledSessions = $allSessions->filter(function ($session) {
                    return $session->status->value === 'unscheduled' || is_null($session->scheduled_at);
                })->count();

                // Determine scheduling status (similar to Quran circles)
                $status = 'not_scheduled';
                if ($scheduledSessions > 0) {
                    if ($unscheduledSessions > 0) {
                        $status = 'partially_scheduled';
                    } else {
                        $status = 'fully_scheduled';
                    }
                }

                return [
                    'id' => $subscription->id,
                    'type' => 'private_lesson',
                    'name' => 'درس خاص - '.($subscription->subject_name ?? 'مادة أكاديمية'),
                    'status' => $status, // Use scheduling status instead of subscription status
                    'total_sessions' => $totalSessions,
                    'sessions_scheduled' => $scheduledSessions,
                    'sessions_remaining' => $unscheduledSessions,
                    'student_name' => $subscription->student?->name ?? 'غير محدد',
                    'subject_name' => $subscription->subject_name ?? 'مادة أكاديمية',
                    'can_schedule' => $unscheduledSessions > 0,
                ];
            });
    }

    /**
     * Get interactive courses for the teacher
     */
    public function getInteractiveCourses(): Collection
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        if (! $teacherProfile) {
            return collect();
        }

        return InteractiveCourse::where('assigned_teacher_id', $teacherProfile->id)
            ->where('academy_id', $user->academy_id)
            ->whereIn('status', ['active', 'published'])
            ->with(['subject', 'sessions', 'enrollments'])
            ->get()
            ->map(function ($course) {
                $scheduledSessions = $course->sessions()->whereIn('status', ['scheduled', 'in_progress', 'completed'])->count();
                $totalSessions = $course->total_sessions;
                $remainingSessions = max(0, $totalSessions - $scheduledSessions);
                $enrolledStudents = $course->enrollments()->where('enrollment_status', 'enrolled')->count();

                return [
                    'id' => $course->id,
                    'type' => 'interactive_course',
                    'title' => $course->title,
                    'status' => $course->status,
                    'status_arabic' => $course->getStatusInArabicAttribute(),
                    'total_sessions' => $totalSessions,
                    'sessions_scheduled' => $scheduledSessions,
                    'sessions_remaining' => $remainingSessions,
                    'start_date' => $course->start_date?->format('Y/m/d'),
                    'end_date' => $course->end_date?->format('Y/m/d'),
                    'subject_name' => $course->subject?->name ?? 'مادة أكاديمية',
                    'enrolled_students' => $enrolledStudents,
                    'max_students' => $course->max_students ?? 20,
                    'can_schedule' => $remainingSessions > 0,
                ];
            });
    }

    /**
     * Select an item for scheduling
     */
    public function selectItem(int $itemId, string $type): void
    {
        $this->selectedItemId = $itemId;
        $this->selectedItemType = $type;
    }

    /**
     * Get currently selected item data
     */
    public function getSelectedItem(): ?array
    {
        if (! $this->selectedItemId || ! $this->selectedItemType) {
            return null;
        }

        if ($this->selectedItemType === 'private_lesson') {
            $lessons = $this->getPrivateLessons();

            return $lessons->firstWhere('id', $this->selectedItemId);
        } elseif ($this->selectedItemType === 'interactive_course') {
            $courses = $this->getInteractiveCourses();

            return $courses->firstWhere('id', $this->selectedItemId);
        }

        return null;
    }

    /**
     * Change active tab
     */
    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        // Clear selections when switching tabs
        $this->selectedItemId = null;
        $this->selectedItemType = '';
    }

    /**
     * Get schedule action for the selected lesson/course
     */
    public function scheduleAction(): Action
    {
        return Action::make('schedule')
            ->label('جدولة جلسات')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->size('lg')
            ->modalHeading(function () {
                $item = $this->getSelectedItem();
                if ($item) {
                    $name = $item['name'] ?? $item['title'] ?? '';

                    return 'جدولة جلسات - '.$name;
                }

                return 'جدولة جلسات';
            })
            ->modalDescription('اختر أيام الأسبوع ووقت الجلسات لإنشاء جدول تلقائي')
            ->modalSubmitActionLabel('إنشاء الجدول')
            ->modalCancelActionLabel('إلغاء')
            ->form([
                Forms\Components\CheckboxList::make('schedule_days')
                    ->label('أيام الأسبوع')
                    ->required()
                    ->options([
                        'saturday' => 'السبت',
                        'sunday' => 'الأحد',
                        'monday' => 'الاثنين',
                        'tuesday' => 'الثلاثاء',
                        'wednesday' => 'الأربعاء',
                        'thursday' => 'الخميس',
                        'friday' => 'الجمعة',
                    ])
                    ->columns(2)
                    ->reactive(),

                Forms\Components\DatePicker::make('schedule_start_date')
                    ->label('تاريخ بداية الجدولة')
                    ->helperText('تاريخ البداية لجدولة الجلسات الجديدة (اتركه فارغاً للبدء من اليوم)')
                    ->default(null)
                    ->minDate(now()->format('Y-m-d'))
                    ->native(false)
                    ->displayFormat('Y/m/d')
                    ->closeOnDateSelection(),

                Forms\Components\Select::make('schedule_time')
                    ->label('وقت الجلسة')
                    ->required()
                    ->placeholder('اختر الساعة')
                    ->options(function () {
                        $options = [];
                        for ($hour = 6; $hour <= 23; $hour++) {
                            $time = sprintf('%02d:00', $hour);
                            $display = sprintf('%02d:00', $hour).' ('.($hour > 12 ? $hour - 12 : ($hour == 0 ? 12 : $hour)).' '.($hour >= 12 ? 'م' : 'ص').')';
                            $options[$time] = $display;
                        }

                        return $options;
                    })
                    ->helperText('الوقت الذي ستبدأ فيه الجلسات'),

                Forms\Components\TextInput::make('session_count')
                    ->label('عدد الجلسات المطلوب إنشاؤها')
                    ->helperText('حدد عدد الجلسات التي تريد جدولتها')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(100)
                    ->default(function () {
                        $item = $this->getSelectedItem();

                        return $item['sessions_remaining'] ?? 4;
                    })
                    ->placeholder('أدخل العدد'),
            ])
            ->action(function (array $data) {
                $this->scheduleDays = $data['schedule_days'] ?? [];
                $this->scheduleTime = $data['schedule_time'] ?? null;
                $this->scheduleStartDate = $data['schedule_start_date'] ?? null;
                $this->sessionCount = $data['session_count'] ?? 4;

                $this->createBulkSchedule();
            })
            ->visible(fn () => $this->selectedItemId !== null)
            ->disabled(function () {
                $selectedItem = $this->getSelectedItem();
                if (! $selectedItem) {
                    return true;
                }

                return ! ($selectedItem['can_schedule'] ?? false);
            });
    }

    /**
     * Create bulk schedule for selected lesson/course
     */
    public function createBulkSchedule(): void
    {
        $this->validate([
            'scheduleDays' => 'required|array|min:1',
            'scheduleTime' => 'required|string',
        ]);

        $selectedItem = $this->getSelectedItem();
        if (! $selectedItem) {
            Notification::make()
                ->title('خطأ')
                ->body('يرجى اختيار درس أو دورة أولاً')
                ->danger()
                ->send();

            return;
        }

        try {
            $sessionsCreated = 0;

            if ($selectedItem['type'] === 'private_lesson') {
                $sessionsCreated = $this->createPrivateLessonSchedule($selectedItem);
            } else {
                $sessionsCreated = $this->createInteractiveCourseSchedule($selectedItem);
            }

            Notification::make()
                ->title('تم بنجاح')
                ->body("تم إنشاء {$sessionsCreated} جلسة بنجاح")
                ->success()
                ->duration(5000)
                ->send();

            // Refresh the page after a short delay to show the notification
            $this->js('setTimeout(() => window.location.reload(), 2000)');

        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ')
                ->body('حدث خطأ أثناء إنشاء الجدول: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Create schedule for private lesson (academic subscription)
     */
    private function createPrivateLessonSchedule(array $itemData): int
    {
        $subscription = \App\Models\AcademicSubscription::findOrFail($itemData['id']);

        $remainingSessions = $itemData['sessions_remaining'];
        if ($remainingSessions <= 0) {
            throw new \Exception('لا توجد جلسات متبقية للجدولة في هذا الاشتراك.');
        }

        // Get unscheduled sessions for this subscription
        $unscheduledSessions = $subscription->sessions()
            ->where('status', 'unscheduled')
            ->orderBy('session_sequence')
            ->limit($remainingSessions)
            ->get();

        if ($unscheduledSessions->isEmpty()) {
            throw new \Exception('لا توجد جلسات غير مجدولة متاحة للجدولة.');
        }

        // Calculate sessions to schedule
        $selectedDaysCount = count($this->scheduleDays);
        $weeksToSchedule = 8; // Schedule for next 8 weeks
        $maxSessionsToSchedule = min($selectedDaysCount * $weeksToSchedule, $unscheduledSessions->count());

        // Use custom start date if provided, otherwise start from now
        $startDate = $this->scheduleStartDate ? Carbon::parse($this->scheduleStartDate) : Carbon::now();
        $scheduledCount = 0;
        $weekCount = 0;
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;
        $sessionIndex = 0;

        // Schedule existing unscheduled sessions across multiple weeks in the selected days
        while ($scheduledCount < $maxSessionsToSchedule && $weekCount < $weeksToSchedule && $sessionIndex < $unscheduledSessions->count()) {
            foreach ($this->scheduleDays as $day) {
                if ($scheduledCount >= $maxSessionsToSchedule || $sessionIndex >= $unscheduledSessions->count()) {
                    break;
                }

                // Find the next occurrence of this day
                $sessionDate = $this->getNextDateForDay($startDate->copy()->addWeeks($weekCount), $day);
                $sessionDateTime = $sessionDate->setTimeFromTimeString($this->scheduleTime);

                // Check if teacher has conflict at this time
                $hasConflict = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
                    ->whereDate('scheduled_at', $sessionDateTime->toDateString())
                    ->whereTime('scheduled_at', $sessionDateTime->toTimeString())
                    ->where('status', '!=', 'cancelled')
                    ->exists();

                if (! $hasConflict) {
                    // Update the existing unscheduled session
                    $session = $unscheduledSessions[$sessionIndex];
                    $session->update([
                        'status' => 'scheduled',
                        'is_scheduled' => true,
                        'scheduled_at' => $sessionDateTime,
                        'duration_minutes' => $subscription->session_duration_minutes ?? 60,
                        'location_type' => 'online',
                        'meeting_source' => 'livekit',
                        'scheduled_by' => $user->id,
                        'teacher_scheduled_at' => now(),
                    ]);

                    $scheduledCount++;
                    $sessionIndex++;
                }
            }
            $weekCount++;
        }

        if ($scheduledCount === 0) {
            throw new \Exception('لم يتم جدولة أي جلسات. تأكد من عدم وجود تعارض في المواعيد المختارة');
        }

        return $scheduledCount;
    }

    /**
     * Create schedule for interactive course
     */
    private function createInteractiveCourseSchedule(array $itemData): int
    {
        $course = InteractiveCourse::findOrFail($itemData['id']);

        $remainingSessions = $itemData['sessions_remaining'];
        if ($remainingSessions <= 0) {
            throw new \Exception('لا توجد جلسات متبقية للجدولة في هذه الدورة.');
        }

        // Calculate sessions to schedule based on user input
        $maxSessionsToSchedule = min($this->sessionCount, $remainingSessions);

        // Use custom start date if provided, otherwise start from now
        $startDate = $this->scheduleStartDate ? Carbon::parse($this->scheduleStartDate) : Carbon::now();
        $scheduledCount = 0;
        $weekCount = 0;
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        // Schedule sessions across multiple weeks in the selected days
        while ($scheduledCount < $maxSessionsToSchedule && $weekCount < 12) { // Max 12 weeks
            foreach ($this->scheduleDays as $day) {
                if ($scheduledCount >= $maxSessionsToSchedule) {
                    break;
                }

                // Find the next occurrence of this day
                $sessionDate = $this->getNextDateForDay($startDate->copy()->addWeeks($weekCount), $day);
                $sessionDateTime = $sessionDate->setTimeFromTimeString($this->scheduleTime);

                // Check if session already exists for this date/time
                $existingSession = InteractiveCourseSession::where('course_id', $course->id)
                    ->whereDate('scheduled_date', $sessionDateTime->toDateString())
                    ->whereTime('scheduled_time', $sessionDateTime->toTimeString())
                    ->first();

                if (! $existingSession) {
                    // Create new course session
                    $sessionNumber = $scheduledCount + 1;
                    InteractiveCourseSession::create([
                        'academy_id' => $course->academy_id,
                        'course_id' => $course->id,
                        'session_number' => $sessionNumber,
                        'title' => "جلسة {$sessionNumber} - {$course->title}",
                        'description' => "جلسة من دورة {$course->title}",
                        'scheduled_date' => $sessionDateTime->toDateString(),
                        'scheduled_time' => $sessionDateTime->toTimeString(),
                        'duration_minutes' => $course->session_duration_minutes ?? 60,
                        'status' => 'scheduled',
                        'meeting_link' => null, // Will be generated when session starts
                        'created_by' => $user->id,
                    ]);

                    $scheduledCount++;
                }
            }
            $weekCount++;
        }

        if ($scheduledCount === 0) {
            throw new \Exception('لم يتم إنشاء أي جلسات. تأكد من عدم وجود تعارض في المواعيد');
        }

        return $scheduledCount;
    }

    /**
     * Get next date for specific day
     */
    private function getNextDateForDay(Carbon $startDate, string $day): Carbon
    {
        $dayMapping = [
            'saturday' => Carbon::SATURDAY,
            'sunday' => Carbon::SUNDAY,
            'monday' => Carbon::MONDAY,
            'tuesday' => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday' => Carbon::THURSDAY,
            'friday' => Carbon::FRIDAY,
        ];

        $targetDay = $dayMapping[$day] ?? Carbon::MONDAY;

        // If today is the target day and we haven't passed the time, use today
        if ($startDate->dayOfWeek === $targetDay) {
            return $startDate->copy();
        }

        // Otherwise, get the next occurrence
        return $startDate->next($targetDay);
    }

    /**
     * Generate session code for academic sessions
     */
    private function generateAcademicSessionCode($lesson, Carbon $sessionDateTime): string
    {
        $lessonCode = $lesson->lesson_code ?? 'AC';
        $dateCode = $sessionDateTime->format('Ymd-Hi');
        $teacherId = str_pad(Auth::id(), 3, '0', STR_PAD_LEFT);

        $baseCode = "{$lessonCode}-{$teacherId}-{$dateCode}";

        // Check for uniqueness and add suffix if needed
        $attempt = 0;
        $sessionCode = $baseCode;
        while (
            AcademicSession::where('academy_id', $lesson->academy_id)
                ->where('session_code', $sessionCode)
                ->exists() && $attempt < 50
        ) {
            $attempt++;
            $sessionCode = $baseCode."-A{$attempt}";
        }

        return $sessionCode;
    }

    /**
     * Get the footer widgets (includes calendar)
     */
    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\AcademicTeacher\Widgets\AcademicFullCalendarWidget::make([
                'selectedItemId' => $this->selectedItemId,
                'selectedItemType' => $this->selectedItemType,
            ]),
            \App\Filament\AcademicTeacher\Widgets\AcademicColorIndicatorsWidget::make(),
        ];
    }
}
