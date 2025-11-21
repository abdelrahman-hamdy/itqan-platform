<?php

namespace App\Filament\Teacher\Pages;

use App\Filament\Shared\Traits\FormatsCalendarData;
use App\Filament\Shared\Traits\HandlesScheduling;
use App\Filament\Shared\Traits\ManagesSessionStatistics;
use App\Filament\Shared\Traits\ValidatesConflicts;
use App\Filament\Teacher\Widgets\ColorIndicatorsWidget;
use App\Filament\Teacher\Widgets\TeacherCalendarWidget;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Models\QuranCircle;
use App\Models\QuranCircleSchedule;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranTrialRequest;
use App\Services\AcademyContextService;
use App\Services\Scheduling\Validators\AcademicLessonValidator;
use App\Services\Scheduling\Validators\GroupCircleValidator;
use App\Services\Scheduling\Validators\IndividualCircleValidator;
use App\Services\Scheduling\Validators\InteractiveCourseValidator;
use App\Services\Scheduling\Validators\TrialSessionValidator;
use App\Services\SessionManagementService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Calendar extends Page
{
    use HandlesScheduling;
    use ManagesSessionStatistics;
    use FormatsCalendarData;
    use ValidatesConflicts;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.teacher.pages.calendar';

    protected static ?string $navigationLabel = 'التقويم';

    protected static ?string $title = 'تقويم المعلم';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'جلساتي';

    public ?int $selectedCircleId = null;

    public ?string $selectedCircleType = 'group'; // 'group' or 'individual'

    public string $activeTab = 'group'; // Tab state for circles, trials

    public ?int $selectedTrialRequestId = null;

    // Academic teacher properties
    public ?int $selectedItemId = null;

    public ?string $selectedItemType = 'private_lesson'; // 'private_lesson' or 'interactive_course'

    // Scheduling form properties
    public array $scheduleDays = [];

    public ?string $scheduleTime = null;

    public ?string $scheduleStartDate = null;

    public int $sessionCount = 4; // NEW: Manual session count from user

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
     * Check if user can access this page
     */
    public static function canAccess(): bool
    {
        return Auth::check();
    }

    /**
     * Check if current user is a Quran teacher
     *
     * @return bool
     */
    protected function isQuranTeacher(): bool
    {
        return Auth::user()?->user_type === 'quran_teacher';
    }

    /**
     * Check if current user is an Academic teacher
     *
     * @return bool
     */
    protected function isAcademicTeacher(): bool
    {
        return Auth::user()?->user_type === 'academic_teacher';
    }

    // getSessionStatistics() is now provided by ManagesSessionStatistics trait

    /**
     * Get group circles for the teacher
     */
    public function getGroupCircles(): Collection
    {
        $userId = Auth::id();
        if (! $userId) {
            return collect();
        }

        return QuranCircle::where('quran_teacher_id', $userId)
            ->where('status', true) // Only show active circles
            ->with(['sessions' => function ($query) {
                $query->where('scheduled_at', '>=', now()->startOfWeek())
                    ->where('scheduled_at', '<=', now()->addMonths(2));
            }, 'schedule'])
            ->get()
            ->map(function ($circle) {
                $schedule = $circle->schedule;
                $sessionsCount = $circle->sessions()->count();

                // Enhanced scheduling status logic
                $upcomingSessions = $circle->sessions()
                    ->where('scheduled_at', '>', now())
                    ->whereIn('status', ['scheduled', 'ready', 'ongoing'])
                    ->count();

                $currentMonthSessions = $circle->sessions()
                    ->whereRaw("DATE_FORMAT(scheduled_at, '%Y-%m') = ?", [now()->format('Y-m')])
                    ->count();

                $monthlyLimit = $circle->monthly_sessions_count ?? 4;
                $needsMoreSessions = $currentMonthSessions < $monthlyLimit;

                // Circle is considered scheduled if:
                // 1. Has active schedule AND
                // 2. Either has upcoming sessions OR current month is already full
                $isScheduled = $schedule &&
                              $schedule->is_active &&
                              ! empty($schedule->weekly_schedule) &&
                              ($upcomingSessions > 0 || ! $needsMoreSessions);

                // Format schedule days and time for display
                $scheduleDays = [];
                $scheduleTime = null;

                if ($schedule && $schedule->weekly_schedule) {
                    foreach ($schedule->weekly_schedule as $entry) {
                        if (isset($entry['day'])) {
                            $scheduleDays[] = $entry['day'];
                        }
                        if (isset($entry['time']) && ! $scheduleTime) {
                            $scheduleTime = $entry['time'];
                        }
                    }
                }

                return [
                    'id' => $circle->id,
                    'type' => 'group',
                    'name' => $circle->name,
                    'status' => $isScheduled ? 'scheduled' : 'not_scheduled',
                    'sessions_count' => $sessionsCount,
                    'schedule_days' => $scheduleDays,
                    'schedule_time' => $scheduleTime,
                    'monthly_sessions' => $circle->monthly_sessions_count,
                    'students_count' => $circle->enrolled_students,
                    'max_students' => $circle->max_students,
                    'session_duration_minutes' => $circle->session_duration_minutes ?? 60,
                ];
            });
    }

    /**
     * Get individual circles for the teacher
     */
    public function getIndividualCircles(): Collection
    {
        $teacherId = Auth::id(); // Individual circles use user ID, not teacher profile ID

        return QuranIndividualCircle::where('quran_teacher_id', $teacherId)
            ->with(['subscription.package', 'sessions', 'student'])
            ->whereIn('status', ['pending', 'active'])
            ->whereHas('student')
            ->get()
            ->map(function ($circle) {
                $subscription = $circle->subscription;
                $scheduledSessions = $circle->sessions()->whereIn('status', ['scheduled', 'ready', 'ongoing', 'completed'])->count();
                $totalSessions = $circle->total_sessions;
                $remainingSessions = max(0, $totalSessions - $scheduledSessions);

                // Determine accurate status
                $status = 'not_scheduled';
                if ($scheduledSessions > 0) {
                    if ($remainingSessions > 0) {
                        $status = 'partially_scheduled';
                    } else {
                        $status = 'fully_scheduled';
                    }
                }

                return [
                    'id' => $circle->id,
                    'type' => 'individual',
                    'name' => $circle->name,
                    'status' => $status,
                    'sessions_count' => $totalSessions,
                    'sessions_scheduled' => $scheduledSessions,
                    'sessions_remaining' => $remainingSessions,
                    'subscription_start' => $subscription?->starts_at,
                    'subscription_end' => $subscription?->expires_at,
                    'student_name' => $circle->student->name ?? 'غير محدد',
                    'monthly_sessions' => $subscription?->package?->monthly_sessions ?? 4,
                    'can_schedule' => $remainingSessions > 0,
                    'session_duration_minutes' => $circle->default_duration_minutes ?? $subscription?->package?->session_duration_minutes ?? 60,
                ];
            });
    }

    /**
     * Select a circle for scheduling
     */
    public function selectCircle(int $circleId, string $type): void
    {
        $this->selectedCircleId = $circleId;
        $this->selectedCircleType = $type;
    }

    /**
     * Get currently selected circle data
     */
    public function getSelectedCircle(): ?array
    {
        if (! $this->selectedCircleId || ! $this->selectedCircleType) {
            return null;
        }

        if ($this->selectedCircleType === 'group') {
            $circles = $this->getGroupCircles();
        } else {
            $circles = $this->getIndividualCircles();
        }

        return $circles->firstWhere('id', $this->selectedCircleId);
    }

    /**
     * Get validator for the selected circle
     */
    // getSelectedCircleValidator() is now defined later with academic support

    /**
     * Get validator for the selected trial request
     */
    private function getSelectedTrialValidator()
    {
        if (!$this->selectedTrialRequestId) {
            return null;
        }

        $trialRequest = QuranTrialRequest::find($this->selectedTrialRequestId);
        return $trialRequest ? new TrialSessionValidator($trialRequest) : null;
    }

    // setActiveTab() is now provided by HandlesScheduling trait

    /**
     * Get trial requests for the teacher
     */
    public function getTrialRequests(): Collection
    {
        $teacherProfileId = Auth::user()?->quranTeacherProfile?->id;
        if (! $teacherProfileId) {
            return collect();
        }

        return QuranTrialRequest::where('teacher_id', $teacherProfileId)
            ->whereIn('status', ['pending', 'approved', 'scheduled'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($trialRequest) {
                return [
                    'id' => $trialRequest->id,
                    'student_name' => $trialRequest->student_name,
                    'phone' => $trialRequest->phone,
                    'email' => $trialRequest->email,
                    'current_level' => $trialRequest->current_level,
                    'level_label' => $trialRequest->level_label,
                    'preferred_time' => $trialRequest->preferred_time,
                    'preferred_time_label' => $trialRequest->time_label,
                    'notes' => $trialRequest->notes,
                    'status' => $trialRequest->status,
                    'status_label' => $trialRequest->status_label,
                    'scheduled_at' => $trialRequest->scheduled_at,
                    'scheduled_at_formatted' => $trialRequest->scheduled_at ? $trialRequest->scheduled_at->format('Y/m/d H:i') : null,
                    'meeting_link' => $trialRequest->meeting_link,
                    'can_schedule' => in_array($trialRequest->status, ['pending', 'approved']),
                ];
            });
    }

    /**
     * Select a trial request for scheduling
     */
    public function selectTrialRequest(int $trialRequestId): void
    {
        $this->selectedTrialRequestId = $trialRequestId;
    }

    /**
     * Get currently selected trial request data
     */
    public function getSelectedTrialRequest(): ?array
    {
        if (! $this->selectedTrialRequestId) {
            return null;
        }

        $trialRequests = $this->getTrialRequests();

        return $trialRequests->firstWhere('id', $this->selectedTrialRequestId);
    }

    /**
     * Get private lessons (academic subscriptions) for the academic teacher
     */
    public function getPrivateLessonsProperty(): Collection
    {
        if (! $this->isAcademicTeacher()) {
            return collect();
        }

        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        if (! $teacherProfile) {
            return collect();
        }

        return AcademicSubscription::where('teacher_id', $teacherProfile->id)
            ->where('academy_id', $user->academy_id)
            ->whereIn('status', ['active', 'approved'])
            ->with(['student', 'subject', 'sessions'])
            ->get()
            ->map(function ($subscription) {
                $allSessions = $subscription->sessions;
                $totalSessions = $allSessions->count();
                $scheduledSessions = $allSessions->filter(function ($session) {
                    return $session->status->value === 'scheduled' && ! is_null($session->scheduled_at);
                })->count();
                $unscheduledSessions = $allSessions->filter(function ($session) {
                    return $session->status->value === 'unscheduled' || is_null($session->scheduled_at);
                })->count();

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
                    'can_schedule' => $unscheduledSessions > 0,
                ];
            });
    }

    /**
     * Get interactive courses for the academic teacher
     */
    public function getInteractiveCoursesProperty(): Collection
    {
        if (! $this->isAcademicTeacher()) {
            return collect();
        }

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
                    'name' => $course->title, // For consistency with other items
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
     * Select an item (private lesson or interactive course) for scheduling
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
            $lessons = $this->privateLessons;

            return $lessons->firstWhere('id', $this->selectedItemId);
        } elseif ($this->selectedItemType === 'interactive_course') {
            $courses = $this->interactiveCourses;

            return $courses->firstWhere('id', $this->selectedItemId);
        }

        return null;
    }

    /**
     * Get header actions for the page
     */
    protected function getActions(): array
    {
        return [
            // No header actions - keep schedule action only under circles tabs
        ];
    }

    /**
     * Get schedule action for the selected circle
     */
    public function scheduleAction(): Action
    {
        return Action::make('schedule')
            ->label('جدولة جلسات')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->size('lg')
            ->modalHeading(function () {
                $circle = $this->getSelectedCircle();

                return 'جدولة جلسات - '.($circle['name'] ?? '');
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
                    ->helperText(function () {
                        $validator = $this->getSelectedCircleValidator();
                        if (!$validator) {
                            return '';
                        }

                        $recommendations = $validator->getRecommendations();
                        return "💡 {$recommendations['reason']}";
                    })
                    ->rules([
                        function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                if (!$value) {
                                    return;
                                }

                                $validator = $this->getSelectedCircleValidator();
                                if (!$validator) {
                                    return;
                                }

                                $result = $validator->validateDaySelection($value);

                                if ($result->isError()) {
                                    $fail($result->getMessage());
                                }
                                // Warnings don't fail validation, they're shown in helper text
                            };
                        },
                    ])
                    ->reactive(),

                Forms\Components\DatePicker::make('schedule_start_date')
                    ->label('تاريخ بداية الجدولة')
                    ->helperText(function () {
                        $circle = $this->getSelectedCircle();
                        if ($circle && $circle['type'] === 'individual') {
                            if (isset($circle['subscription_end']) && $circle['subscription_end']) {
                                $expiryDate = Carbon::parse($circle['subscription_end']);
                                return 'تاريخ البداية لجدولة الجلسات (يجب أن يكون قبل انتهاء الاشتراك في '.$expiryDate->format('Y/m/d').')';
                            }
                        }
                        return 'تاريخ البداية لجدولة الجلسات الجديدة (اتركه فارغاً للبدء من اليوم)';
                    })
                    ->default(null)
                    ->minDate(now()->format('Y-m-d'))
                    ->maxDate(function () {
                        $circle = $this->getSelectedCircle();
                        if ($circle && $circle['type'] === 'individual') {
                            if (isset($circle['subscription_end']) && $circle['subscription_end']) {
                                return Carbon::parse($circle['subscription_end'])->format('Y-m-d');
                            }
                        }
                        return null;
                    })
                    ->native(false)
                    ->displayFormat('Y/m/d')
                    ->closeOnDateSelection()
                    ->reactive(),

                Forms\Components\Select::make('schedule_time')
                    ->label('وقت الجلسة')
                    ->required()
                    ->placeholder('اختر الساعة')
                    ->options(function () {
                        $options = [];
                        for ($hour = 0; $hour <= 23; $hour++) {
                            $time = sprintf('%02d:00', $hour);
                            // Convert to 12-hour format
                            $hour12 = $hour > 12 ? $hour - 12 : ($hour == 0 ? 12 : $hour);
                            $period = $hour >= 12 ? 'م' : 'ص';
                            $display = sprintf('%02d:00', $hour).' ('.$hour12.' '.$period.')';
                            $options[$time] = $display;
                        }

                        return $options;
                    })
                    ->searchable()
                    ->helperText(function () {
                        $timezone = AcademyContextService::getTimezone();
                        $currentTime = Carbon::now($timezone)->format('H:i');
                        return "الوقت الذي ستبدأ فيه الجلسات (التوقيت المحلي - الوقت الحالي: {$currentTime})";
                    }),

                Forms\Components\TextInput::make('session_count')
                    ->label('عدد الجلسات المطلوب إنشاؤها')
                    ->helperText(function () {
                        $circle = $this->getSelectedCircle();
                        if (!$circle) {
                            return 'حدد عدد الجلسات التي تريد جدولتها';
                        }

                        if ($circle['type'] === 'group') {
                            return 'حدد عدد الجلسات التي تريد جدولتها (الحد الأقصى: 100 جلسة)';
                        } else {
                            $remaining = $circle['sessions_remaining'] ?? 0;
                            return "حدد عدد الجلسات التي تريد جدولتها (المتبقية: {$remaining} جلسة)";
                        }
                    })
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(function () {
                        $circle = $this->getSelectedCircle();
                        if (!$circle) {
                            return 100;
                        }

                        if ($circle['type'] === 'group') {
                            return 100; // No hard limit for group circles
                        } else {
                            // For individual circles, max is remaining sessions
                            return max(1, $circle['sessions_remaining'] ?? 1);
                        }
                    })
                    ->default(function () {
                        $circle = $this->getSelectedCircle();
                        if (!$circle) {
                            return 4;
                        }

                        if ($circle['type'] === 'group') {
                            return $circle['monthly_sessions'] ?? 4;
                        } else {
                            // For individual circles, default to remaining sessions or 4, whichever is smaller
                            $remaining = $circle['sessions_remaining'] ?? 4;
                            return min($remaining, 8); // Default to 8 or remaining, whichever is smaller
                        }
                    })
                    ->placeholder('أدخل العدد')
                    ->reactive(),

                Forms\Components\Placeholder::make('circle_info')
                    ->label('معلومات الحلقة')
                    ->content(function () {
                        $circle = $this->getSelectedCircle();
                        if (! $circle) {
                            return 'لم يتم اختيار حلقة';
                        }

                        $content = '<div class="space-y-2">';
                        $content .= '<div><strong>نوع الحلقة:</strong> '.($circle['type'] === 'group' ? 'جماعية' : 'فردية').'</div>';
                        $content .= '<div><strong>مدة الجلسة:</strong> <span class="text-blue-600 font-semibold">'.($circle['session_duration_minutes'] ?? 60).' دقيقة</span></div>';

                        if ($circle['type'] === 'group') {
                            $content .= '<div><strong>عدد الطلاب:</strong> '.($circle['students_count'] ?? 0).'/'.($circle['max_students'] ?? 0).'</div>';
                            $content .= '<div><strong>الجلسات الشهرية المستهدفة:</strong> '.($circle['monthly_sessions'] ?? 4).' جلسة</div>';
                        } else {
                            // Individual circle - show subscription details
                            $content .= '<div><strong>الطالب:</strong> '.($circle['student_name'] ?? 'غير محدد').'</div>';
                            $content .= '<div><strong>إجمالي الجلسات:</strong> '.($circle['sessions_count'] ?? 0).'</div>';
                            $content .= '<div><strong>المجدولة:</strong> <span class="text-blue-600 font-semibold">'.($circle['sessions_scheduled'] ?? 0).'</span></div>';
                            $content .= '<div><strong>المتبقية:</strong> <span class="text-green-600 font-semibold">'.($circle['sessions_remaining'] ?? 0).'</span></div>';

                            // Show subscription dates if available
                            if (isset($circle['subscription_start']) && $circle['subscription_start']) {
                                $content .= '<div class="mt-2 pt-2 border-t border-gray-200">';
                                $content .= '<div><strong>بداية الاشتراك:</strong> '.Carbon::parse($circle['subscription_start'])->format('Y/m/d').'</div>';

                                if (isset($circle['subscription_end']) && $circle['subscription_end']) {
                                    $expiryDate = Carbon::parse($circle['subscription_end']);
                                    $daysRemaining = now()->diffInDays($expiryDate, false);

                                    $expiryColor = 'text-gray-600';
                                    $expiryWarning = '';
                                    if ($daysRemaining < 0) {
                                        $expiryColor = 'text-red-600 font-bold';
                                        $expiryWarning = ' ⚠️ منتهي';
                                    } elseif ($daysRemaining <= 7) {
                                        $expiryColor = 'text-orange-600 font-semibold';
                                        $expiryWarning = ' ⚠️ ينتهي خلال '.$daysRemaining.' يوم';
                                    }

                                    $content .= '<div><strong>انتهاء الاشتراك:</strong> <span class="'.$expiryColor.'">'.$expiryDate->format('Y/m/d').$expiryWarning.'</span></div>';
                                }
                                $content .= '</div>';
                            }
                        }

                        $content .= '</div>';

                        return new \Illuminate\Support\HtmlString($content);
                    })
                    ->columnSpanFull(),
            ])
            ->action(function (array $data) {
                $this->scheduleDays = $data['schedule_days'] ?? [];
                $this->scheduleTime = $data['schedule_time'] ?? null;
                $this->scheduleStartDate = $data['schedule_start_date'] ?? null;
                $this->sessionCount = $data['session_count'] ?? 4; // NEW: Store user-specified session count

                $this->createBulkSchedule();
            })
            ->visible(fn () => $this->selectedCircleId !== null)
            ->disabled(function () {
                $selectedCircle = $this->getSelectedCircle();
                if (! $selectedCircle) {
                    return true;
                }

                return ! ($selectedCircle['type'] === 'group' || ($selectedCircle['can_schedule'] ?? false));
            });
    }

    /**
     * Create bulk schedule for selected circle
     */
    public function createBulkSchedule(): void
    {
        $this->validate([
            'scheduleDays' => 'required|array|min:1',
            'scheduleTime' => 'required|string',
        ]);

        // Get selected item (circle or academic lesson/course)
        $selectedItem = null;
        $validator = null;

        if ($this->isQuranTeacher()) {
            if (! $this->selectedCircleId) {
                Notification::make()
                    ->title('خطأ')
                    ->body('يرجى اختيار حلقة أولاً')
                    ->danger()
                    ->send();
                return;
            }

            $selectedItem = $this->getSelectedCircle();
            $validator = $this->getSelectedCircleValidator();
        } elseif ($this->isAcademicTeacher()) {
            if (! $this->selectedItemId) {
                Notification::make()
                    ->title('خطأ')
                    ->body('يرجى اختيار درس أو دورة أولاً')
                    ->danger()
                    ->send();
                return;
            }

            $selectedItem = $this->getSelectedItem();
            $validator = $this->getSelectedItemValidator();
        }

        if (! $selectedItem) {
            Notification::make()
                ->title('خطأ')
                ->body('لم يتم العثور على العنصر المحدد')
                ->danger()
                ->send();
            return;
        }

        try {
            // CRITICAL: Use validator to validate BEFORE creating any sessions
            if ($validator) {
                // Validate day selection
                $dayResult = $validator->validateDaySelection($this->scheduleDays);
                if ($dayResult->isError()) {
                    throw new \Exception($dayResult->getMessage());
                }

                // Validate session count
                $countResult = $validator->validateSessionCount($this->sessionCount);
                if ($countResult->isError()) {
                    throw new \Exception($countResult->getMessage());
                }

                // Validate date range
                $startDate = $this->scheduleStartDate ? Carbon::parse($this->scheduleStartDate) : null;
                $weeksAhead = ceil($this->sessionCount / count($this->scheduleDays));

                $dateResult = $validator->validateDateRange($startDate, $weeksAhead);
                if ($dateResult->isError()) {
                    throw new \Exception($dateResult->getMessage());
                }

                // Validate weekly pacing
                $pacingResult = $validator->validateWeeklyPacing($this->scheduleDays, $weeksAhead);
                if ($pacingResult->isError()) {
                    throw new \Exception($pacingResult->getMessage());
                }
            }

            $sessionsCreated = 0;

            // Route to appropriate scheduling method based on type
            if ($this->isQuranTeacher()) {
                if ($selectedItem['type'] === 'group') {
                    $sessionsCreated = $this->createGroupCircleSchedule($selectedItem);
                } elseif ($selectedItem['type'] === 'individual') {
                    $sessionsCreated = $this->createIndividualCircleSchedule($selectedItem);
                } elseif ($selectedItem['type'] === 'trial') {
                    $sessionsCreated = $this->createTrialSessionSchedule($selectedItem);
                }
            } elseif ($this->isAcademicTeacher()) {
                if ($selectedItem['type'] === 'private_lesson') {
                    $sessionsCreated = $this->createPrivateLessonSchedule($selectedItem);
                } elseif ($selectedItem['type'] === 'interactive_course') {
                    $sessionsCreated = $this->createInteractiveCourseSchedule($selectedItem);
                }
            }

            Notification::make()
                ->title('تم بنجاح')
                ->body("تم إنشاء {$sessionsCreated} جلسة بنجاح")
                ->success()
                ->duration(5000)
                ->send();

            // Refresh the page after a short delay
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
     * Get validator for the selected circle (Quran teacher)
     */
    protected function getSelectedCircleValidator()
    {
        $selectedCircle = $this->getSelectedCircle();
        if (! $selectedCircle) {
            return null;
        }

        if ($selectedCircle['type'] === 'group') {
            $circle = QuranCircle::find($selectedCircle['id']);
            return $circle ? new GroupCircleValidator($circle) : null;
        } elseif ($selectedCircle['type'] === 'individual') {
            $circle = QuranIndividualCircle::find($selectedCircle['id']);
            return $circle ? new IndividualCircleValidator($circle) : null;
        } elseif ($selectedCircle['type'] === 'trial') {
            $trial = QuranTrialRequest::find($selectedCircle['id']);
            return $trial ? new TrialSessionValidator($trial) : null;
        }

        return null;
    }

    /**
     * Get validator for the selected item (Academic teacher)
     */
    protected function getSelectedItemValidator()
    {
        if (!$this->selectedItemId || !$this->selectedItemType) {
            return null;
        }

        if ($this->selectedItemType === 'private_lesson') {
            $subscription = AcademicSubscription::find($this->selectedItemId);
            return $subscription ? new AcademicLessonValidator($subscription) : null;
        } elseif ($this->selectedItemType === 'interactive_course') {
            $course = InteractiveCourse::find($this->selectedItemId);
            return $course ? new InteractiveCourseValidator($course) : null;
        }

        return null;
    }

    /**
     * Create schedule for group circle
     */
    private function createGroupCircleSchedule(array $circleData): int
    {
        $circle = QuranCircle::findOrFail($circleData['id']);

        // Create weekly schedule array for QuranCircleSchedule
        $weeklySchedule = [];
        foreach ($this->scheduleDays as $day) {
            $weeklySchedule[] = [
                'day' => $day,
                'time' => $this->scheduleTime,
            ];
        }

        // Check if schedule already exists with same configuration
        $teacherId = Auth::id();
        $existingSchedule = QuranCircleSchedule::where([
            'academy_id' => $circle->academy_id,
            'circle_id' => $circle->id,
            'quran_teacher_id' => $teacherId,
            'is_active' => true,
        ])->first();

        if ($existingSchedule) {
            // Compare existing schedule with new one
            $existingWeeklySchedule = $existingSchedule->weekly_schedule ?? [];

            // Sort both arrays for comparison
            $sortedExisting = collect($existingWeeklySchedule)->sortBy('day')->values()->toArray();
            $sortedNew = collect($weeklySchedule)->sortBy('day')->values()->toArray();

            if ($sortedExisting === $sortedNew) {
                // CRITICAL FIX: Schedule already exists with same configuration - reuse it
                // This allows re-scheduling sessions after deleting them
                $schedule = $existingSchedule;
            } else {
                // If different, update the existing schedule
                $existingSchedule->update([
                    'weekly_schedule' => $weeklySchedule,
                    'updated_by' => Auth::id(),
                ]);

                $schedule = $existingSchedule;
            }
        } else {
            // Create new schedule
            $schedule = QuranCircleSchedule::create([
                'academy_id' => $circle->academy_id,
                'circle_id' => $circle->id,
                'quran_teacher_id' => $teacherId,
                'weekly_schedule' => $weeklySchedule,
                'timezone' => config('app.timezone', 'UTC'),
                'default_duration_minutes' => $circle->session_duration_minutes ?? 60,
                'is_active' => true,
                'schedule_starts_at' => $this->scheduleStartDate ? Carbon::parse($this->scheduleStartDate)->startOfDay() : Carbon::now()->startOfDay(),
                'generate_ahead_days' => 30, // Generate 1 month ahead
                'generate_before_hours' => 1,
                'session_title_template' => 'جلسة {circle_name} - {day} {time}',
                'session_description_template' => 'جلسة حلقة القرآن المجدولة تلقائياً',
                'recording_enabled' => false,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        }

        // NEW: Generate ONLY the user-requested number of sessions, not based on time periods
        $userRequestedCount = $this->sessionCount;

        // Activate the schedule (this updates circle status but we'll control session generation)
        $schedule->update(['is_active' => true]);
        $schedule->circle->update([
            'status' => 'active',
            'enrollment_status' => 'open',
            'schedule_configured' => true,
            'schedule_configured_at' => now(),
        ]);

        // Generate exactly the number of sessions the user requested
        $totalGenerated = $this->generateExactGroupSessions($schedule, $userRequestedCount);

        return $totalGenerated;
    }

    /**
     * Create schedule for individual circle
     */
    private function createIndividualCircleSchedule(array $circleData): int
    {
        $circle = QuranIndividualCircle::findOrFail($circleData['id']);

        // Validate subscription exists and is active
        if (! $circle->subscription) {
            throw new \Exception('لا يمكن جدولة جلسات لحلقة بدون اشتراك صالح');
        }

        // Check if subscription is active
        if ($circle->subscription->subscription_status !== 'active') {
            throw new \Exception('الاشتراك غير نشط. يجب تفعيل الاشتراك لجدولة الجلسات');
        }

        // Use SessionManagementService to get ACCURATE remaining sessions count
        $sessionService = app(\App\Services\SessionManagementService::class);
        $remainingSessions = $sessionService->getRemainingIndividualSessions($circle);

        if ($remainingSessions <= 0) {
            throw new \Exception('لا توجد جلسات متبقية للجدولة في هذه الحلقة. تم استنفاد جميع جلسات الاشتراك.');
        }

        // For individual circles, allow flexible scheduling
        // Calculate how many sessions to schedule per week cycle
        $selectedDaysCount = count($this->scheduleDays);

        // CRITICAL: Calculate weeks needed based on user's session count and selected days
        // This ensures we only schedule the exact number of sessions requested
        $weeksToSchedule = ceil($this->sessionCount / $selectedDaysCount);

        // Use custom start date if provided, otherwise start from now
        // Ensure we're working in the academy's timezone
        $appTimezone = AcademyContextService::getTimezone();
        $startDate = $this->scheduleStartDate
            ? Carbon::parse($this->scheduleStartDate, $appTimezone)
            : Carbon::now($appTimezone);

        // CRITICAL: Calculate subscription end date based on billing cycle
        // NOTE: expires_at field was removed from subscriptions table
        // Subscriptions are now managed by billing_cycle + starts_at
        $subscriptionExpiryDate = null;
        if ($circle->subscription->starts_at && $circle->subscription->billing_cycle) {
            $subscriptionExpiryDate = match ($circle->subscription->billing_cycle) {
                'weekly' => $circle->subscription->starts_at->copy()->addWeek(),
                'monthly' => $circle->subscription->starts_at->copy()->addMonth(),
                'quarterly' => $circle->subscription->starts_at->copy()->addMonths(3),
                'yearly' => $circle->subscription->starts_at->copy()->addYear(),
                default => null,
            };
        }

        // Calculate maximum weeks based on subscription billing period
        if ($subscriptionExpiryDate) {
            $daysUntilExpiry = max(1, $startDate->diffInDays($subscriptionExpiryDate, false));
            $weeksUntilExpiry = (int) ceil($daysUntilExpiry / 7);

            // Don't schedule beyond subscription billing period
            if ($weeksUntilExpiry < $weeksToSchedule) {
                $weeksToSchedule = $weeksUntilExpiry;

                if ($weeksToSchedule <= 0) {
                    throw new \Exception(
                        'لا يمكن جدولة جلسات. دورة الفوترة تنتهي في ' .
                        $subscriptionExpiryDate->format('Y/m/d') .
                        '. يرجى تجديد الاشتراك أو اختيار تاريخ بداية قبل نهاية الدورة.'
                    );
                }
            }
        }

        // CRITICAL: Never exceed subscription remaining sessions limit
        $maxSessionsToSchedule = min($selectedDaysCount * $weeksToSchedule, $remainingSessions);

        $scheduledCount = 0;
        $weekCount = 0;
        $reachedSubscriptionExpiry = false;

        // Schedule sessions across multiple weeks in the selected days
        while ($scheduledCount < $maxSessionsToSchedule && $weekCount < $weeksToSchedule && !$reachedSubscriptionExpiry) {
            foreach ($this->scheduleDays as $day) {
                // CRITICAL: Double-check we haven't exceeded the limit
                if ($scheduledCount >= $maxSessionsToSchedule) {
                    break 2; // Break out of both loops
                }

                // CRITICAL: Re-check remaining sessions in real-time to prevent race conditions
                $currentRemaining = $sessionService->getRemainingIndividualSessions($circle);
                if ($currentRemaining <= 0) {
                    break 2; // Break out of both loops
                }

                // Find the next occurrence of this day
                $sessionDate = $this->getNextDateForDay($startDate->copy()->addWeeks($weekCount), $day);
                $sessionDateTime = $sessionDate->setTimeFromTimeString($this->scheduleTime);

                // CRITICAL: Check if session date is beyond subscription end date
                if ($subscriptionExpiryDate && $sessionDateTime->isAfter($subscriptionExpiryDate)) {
                    // Stop scheduling completely - don't create any more sessions
                    $reachedSubscriptionExpiry = true;
                    break 2; // Break out of both loops
                }

                // Check if session already exists for this date/time
                $existingSession = QuranSession::where('individual_circle_id', $circle->id)
                    ->where('quran_teacher_id', Auth::id())
                    ->whereDate('scheduled_at', $sessionDateTime->toDateString())
                    ->whereTime('scheduled_at', $sessionDateTime->toTimeString())
                    ->first();

                if (! $existingSession) {
                    // Create new session with counts_toward_subscription = true
                    QuranSession::create([
                        'academy_id' => $circle->academy_id,
                        'quran_teacher_id' => Auth::id(),
                        'individual_circle_id' => $circle->id,
                        'student_id' => $circle->student_id,
                        'session_code' => $this->generateIndividualSessionCode($circle, $sessionDateTime),
                        'session_type' => 'individual',
                        'status' => 'scheduled',
                        'is_scheduled' => true,
                        'title' => "جلسة فردية - {$circle->student->name}",
                        'scheduled_at' => $sessionDateTime,
                        'duration_minutes' => $circle->default_duration_minutes ?? 60,
                        'location_type' => 'online',
                        'created_by' => Auth::id(),
                        'scheduled_by' => Auth::id(),
                        'teacher_scheduled_at' => now(),
                    ]);

                    $scheduledCount++;
                }
            }
            $weekCount++;
        }

        if ($scheduledCount === 0) {
            throw new \Exception('لم يتم إنشاء أي جلسات. تأكد من عدم وجود تعارض في المواعيد أو أن الاشتراك يحتوي على جلسات متبقية');
        }

        // Update circle session counts
        if (method_exists($circle, 'updateSessionCounts')) {
            $circle->updateSessionCounts();
        }

        return $scheduledCount;
    }

    /**
     * Get next date for specific day
     */
    // getNextDateForDay() is now provided by HandlesScheduling trait

    /**
     * Create schedule for trial session (simple - just one session)
     */
    private function createTrialSessionSchedule(array $trialData): int
    {
        $trial = QuranTrialRequest::findOrFail($trialData['id']);

        if (!$trial->session) {
            throw new \Exception('لا توجد جلسة تجريبية للجدولة');
        }

        if (count($this->scheduleDays) !== 1) {
            throw new \Exception('يمكن اختيار يوم واحد فقط للجلسة التجريبية');
        }

        $timezone = AcademyContextService::getTimezone();
        $startDate = $this->scheduleStartDate
            ? Carbon::parse($this->scheduleStartDate, $timezone)
            : Carbon::now($timezone);

        $day = $this->scheduleDays[0];
        $sessionDate = $this->getNextDateForDay($startDate, $day);
        $sessionDateTime = $sessionDate->setTimeFromTimeString($this->scheduleTime);

        // Update the trial session
        $trial->session->update([
            'scheduled_at' => $sessionDateTime,
            'status' => 'scheduled',
        ]);

        // Update trial request
        $trial->update([
            'scheduled_at' => $sessionDateTime,
            'status' => 'scheduled',
        ]);

        return 1; // Always 1 session for trials
    }

    /**
     * Create schedule for private lesson (academic subscription)
     */
    private function createPrivateLessonSchedule(array $itemData): int
    {
        $subscription = AcademicSubscription::findOrFail($itemData['id']);

        $remainingSessions = $itemData['sessions_remaining'];
        if ($remainingSessions <= 0) {
            throw new \Exception('لا توجد جلسات متبقية للجدولة في هذا الاشتراك.');
        }

        // Get unscheduled sessions
        $unscheduledSessions = $subscription->sessions()
            ->where('status', 'unscheduled')
            ->orderBy('session_sequence')
            ->limit($remainingSessions)
            ->get();

        if ($unscheduledSessions->isEmpty()) {
            throw new \Exception('لا توجد جلسات غير مجدولة متاحة.');
        }

        $selectedDaysCount = count($this->scheduleDays);
        $weeksToSchedule = 8; // Schedule for next 8 weeks
        $maxSessionsToSchedule = min($selectedDaysCount * $weeksToSchedule, $unscheduledSessions->count(), $this->sessionCount);

        $timezone = AcademyContextService::getTimezone();
        $startDate = $this->scheduleStartDate
            ? Carbon::parse($this->scheduleStartDate, $timezone)
            : Carbon::now($timezone);

        $scheduledCount = 0;
        $weekCount = 0;
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;
        $sessionIndex = 0;

        while ($scheduledCount < $maxSessionsToSchedule && $weekCount < $weeksToSchedule && $sessionIndex < $unscheduledSessions->count()) {
            foreach ($this->scheduleDays as $day) {
                if ($scheduledCount >= $maxSessionsToSchedule || $sessionIndex >= $unscheduledSessions->count()) {
                    break;
                }

                $sessionDate = $this->getNextDateForDay($startDate->copy()->addWeeks($weekCount), $day);
                $sessionDateTime = $sessionDate->setTimeFromTimeString($this->scheduleTime);

                // Check for conflicts
                $hasConflict = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
                    ->whereDate('scheduled_at', $sessionDateTime->toDateString())
                    ->whereTime('scheduled_at', $sessionDateTime->toTimeString())
                    ->where('status', '!=', 'cancelled')
                    ->exists();

                if (!$hasConflict) {
                    $session = $unscheduledSessions[$sessionIndex];
                    $session->update([
                        'status' => 'scheduled',
                        'is_scheduled' => true,
                        'scheduled_at' => $sessionDateTime,
                        'duration_minutes' => $subscription->session_duration_minutes ?? 60,
                        'location_type' => 'online',
                        'meeting_source' => 'livekit',
                        'scheduled_by' => $user->id,
                        'teacher_scheduled_at' => Carbon::now($timezone),
                    ]);

                    $scheduledCount++;
                    $sessionIndex++;
                }
            }
            $weekCount++;
        }

        if ($scheduledCount === 0) {
            throw new \Exception('لم يتم جدولة أي جلسات. تأكد من عدم وجود تعارض في المواعيد.');
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

        $maxSessionsToSchedule = min($this->sessionCount, $remainingSessions);

        $timezone = AcademyContextService::getTimezone();
        $startDate = $this->scheduleStartDate
            ? Carbon::parse($this->scheduleStartDate, $timezone)
            : Carbon::now($timezone);

        $scheduledCount = 0;
        $weekCount = 0;
        $user = Auth::user();

        while ($scheduledCount < $maxSessionsToSchedule && $weekCount < 12) {
            foreach ($this->scheduleDays as $day) {
                if ($scheduledCount >= $maxSessionsToSchedule) {
                    break;
                }

                $sessionDate = $this->getNextDateForDay($startDate->copy()->addWeeks($weekCount), $day);
                $sessionDateTime = $sessionDate->setTimeFromTimeString($this->scheduleTime);

                // Check if session already exists
                $existingSession = InteractiveCourseSession::where('course_id', $course->id)
                    ->where('scheduled_at', $sessionDateTime)
                    ->first();

                if (!$existingSession) {
                    $sessionNumber = $scheduledCount + 1;
                    InteractiveCourseSession::create([
                        'academy_id' => $course->academy_id,
                        'course_id' => $course->id,
                        'session_number' => $sessionNumber,
                        'title' => "جلسة {$sessionNumber} - {$course->title}",
                        'description' => "جلسة من دورة {$course->title}",
                        'scheduled_at' => $sessionDateTime,
                        'duration_minutes' => $course->session_duration_minutes ?? 60,
                        'status' => 'scheduled',
                        'meeting_link' => null,
                        'created_by' => $user->id,
                    ]);

                    $scheduledCount++;
                }
            }
            $weekCount++;
        }

        if ($scheduledCount === 0) {
            throw new \Exception('لم يتم إنشاء أي جلسات. تأكد من عدم وجود تعارض.');
        }

        return $scheduledCount;
    }

    /**
     * Generate session code for individual sessions
     */
    private function generateIndividualSessionCode($circle, Carbon $sessionDateTime): string
    {
        $circleCode = $circle->circle_code ?? 'IC';
        $dateCode = $sessionDateTime->format('Ymd-Hi');
        $teacherId = str_pad(Auth::id(), 3, '0', STR_PAD_LEFT);

        $baseCode = "{$circleCode}-{$teacherId}-{$dateCode}";

        // Check for uniqueness and add suffix if needed
        $attempt = 0;
        $sessionCode = $baseCode;
        while (
            QuranSession::where('academy_id', $circle->academy_id)
                ->where('session_code', $sessionCode)
                ->exists() && $attempt < 50
        ) {
            $attempt++;
            $sessionCode = $baseCode."-A{$attempt}";
        }

        return $sessionCode;
    }

    /**
     * Schedule trial session action with form
     */
    public function scheduleTrialAction(): Action
    {
        return Action::make('scheduleTrialAction')
            ->label('جدولة الجلسة التجريبية')
            ->icon('heroicon-o-calendar-days')
            ->color('primary')
            ->size('lg')
            ->modalHeading(function () {
                $trialRequest = $this->getSelectedTrialRequest();

                return 'جدولة جلسة تجريبية - '.($trialRequest['student_name'] ?? '');
            })
            ->modalDescription('اختر التاريخ والوقت المناسب للجلسة التجريبية')
            ->modalSubmitActionLabel('جدولة الجلسة')
            ->modalCancelActionLabel('إلغاء')
            ->form([
                Forms\Components\DateTimePicker::make('scheduled_at')
                    ->label('موعد الجلسة التجريبية')
                    ->required()
                    ->native(false)
                    ->seconds(false)
                    ->minutesStep(15)
                    ->minDate(now()->toDateString())
                    ->maxDate(now()->addMonths(2)->toDateString())
                    ->default(now()->addDay()->setTime(16, 0))
                    ->displayFormat('Y-m-d H:i')
                    ->timezone(config('app.timezone', 'UTC'))
                    ->helperText(function () {
                        $validator = $this->getSelectedTrialValidator();
                        if (!$validator) {
                            return 'اختر التاريخ والوقت المناسب للطالب';
                        }

                        $recommendations = $validator->getRecommendations();
                        return "💡 {$recommendations['reason']}";
                    })
                    ->rules([
                        function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                if (! $value) {
                                    return;
                                }

                                $scheduledAt = Carbon::parse($value);

                                // Use validator for date range validation
                                $validator = $this->getSelectedTrialValidator();
                                if ($validator) {
                                    $result = $validator->validateDateRange($scheduledAt, 1);
                                    if ($result->isError()) {
                                        $fail($result->getMessage());
                                        return;
                                    }
                                }

                                // Check for conflicts with other sessions
                                $conflicts = QuranSession::where('quran_teacher_id', Auth::id())
                                    ->where(function ($query) use ($scheduledAt) {
                                        $endTime = $scheduledAt->copy()->addMinutes(30);
                                        $query->where(function ($q) use ($scheduledAt, $endTime) {
                                            $q->whereRaw('? BETWEEN scheduled_at AND DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE)', [$scheduledAt])
                                                ->orWhereRaw('? BETWEEN scheduled_at AND DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE)', [$endTime])
                                                ->orWhere(function ($subQ) use ($scheduledAt, $endTime) {
                                                    $subQ->where('scheduled_at', '>=', $scheduledAt)
                                                        ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) <= ?', [$endTime]);
                                                });
                                        });
                                    })
                                    ->first();

                                if ($conflicts) {
                                    $conflictTime = $conflicts->scheduled_at->format('Y/m/d H:i');
                                    $fail("يوجد تعارض مع جلسة أخرى في {$conflictTime}");
                                }
                            };
                        },
                    ]),

                Forms\Components\Textarea::make('teacher_response')
                    ->label('رسالة للطالب (اختياري)')
                    ->rows(3)
                    ->placeholder('اكتب رسالة ترحيبية أو تعليمات للطالب...')
                    ->helperText('سيتم إرسال هذه الرسالة مع تأكيد موعد الجلسة'),

                Forms\Components\Placeholder::make('trial_info')
                    ->label('معلومات الطلب')
                    ->content(function () {
                        $trialRequest = $this->getSelectedTrialRequest();
                        if (! $trialRequest) {
                            return 'لم يتم اختيار طلب';
                        }

                        $content = 'الطالب: '.$trialRequest['student_name'].'<br>';
                        $content .= 'الهاتف: '.$trialRequest['phone'].'<br>';
                        $content .= 'المستوى: '.$trialRequest['level_label'].'<br>';
                        $content .= 'الوقت المفضل: '.$trialRequest['preferred_time_label'].'<br>';
                        if ($trialRequest['notes']) {
                            $content .= 'ملاحظات: '.Str::limit($trialRequest['notes'], 100);
                        }

                        return new \Illuminate\Support\HtmlString($content);
                    })
                    ->columnSpanFull(),
            ])
            ->action(function (array $data) {
                try {
                    $trialRequest = QuranTrialRequest::find($this->selectedTrialRequestId);
                    if (! $trialRequest) {
                        throw new \Exception('طلب الجلسة التجريبية غير موجود');
                    }

                    $scheduledAt = Carbon::parse($data['scheduled_at']);
                    $teacherResponse = $data['teacher_response'] ?? 'تم جدولة الجلسة التجريبية';

                    // Get the current teacher's Quran teacher profile
                    $user = Auth::user();
                    $teacherProfile = $user->quranTeacherProfile;

                    if (!$teacherProfile) {
                        throw new \Exception('لم يتم العثور على ملف المعلم');
                    }

                    // Create a session record for the trial
                    $sessionCode = $this->generateTrialSessionCode($trialRequest, $scheduledAt);

                    \Log::info('Creating trial session', [
                        'trial_request_id' => $trialRequest->id,
                        'teacher_user_id' => $user->id,
                        'teacher_profile_id' => $teacherProfile->id,
                        'student_id' => $trialRequest->student_id,
                        'scheduled_at' => $scheduledAt,
                        'session_code' => $sessionCode,
                    ]);

                    $session = QuranSession::create([
                        'academy_id' => $trialRequest->academy_id,
                        'session_code' => $sessionCode,
                        'session_type' => 'trial',
                        'quran_teacher_id' => $user->id, // This is the user ID, not profile ID
                        'student_id' => $trialRequest->student_id,
                        'trial_request_id' => $trialRequest->id,
                        'scheduled_at' => $scheduledAt,
                        'duration_minutes' => 30,
                        'status' => \App\Enums\SessionStatus::SCHEDULED, // Use enum
                        'title' => "جلسة تجريبية - {$trialRequest->student_name}",
                        'description' => $teacherResponse,
                        'notes' => 'جلسة تجريبية مجدولة',
                        'location_type' => 'online',
                        'created_by' => $user->id,
                        'scheduled_by' => $user->id,
                        'teacher_scheduled_at' => now(),
                        'session_data' => json_encode([
                            'is_trial' => true,
                            'student_level' => $trialRequest->current_level,
                            'preferred_time' => $trialRequest->preferred_time,
                            'contact_phone' => $trialRequest->phone,
                            'learning_goals' => $trialRequest->learning_goals,
                            'teacher_response' => $teacherResponse,
                        ]),
                    ]);

                    \Log::info('Trial session created successfully', [
                        'session_id' => $session->id,
                        'session_code' => $session->session_code,
                    ]);

                    // Generate LiveKit meeting room (same as other session types)
                    $session->generateMeetingLink();

                    \Log::info('LiveKit meeting generated', [
                        'session_id' => $session->id,
                        'meeting_id' => $session->meeting?->id,
                    ]);

                    // Status sync happens automatically via QuranSessionObserver
                    // Observer will link session to trial request and update status

                    Notification::make()
                        ->title('تم جدولة الجلسة التجريبية')
                        ->body("جُدولت جلسة تجريبية للطالب {$trialRequest->student_name} في {$scheduledAt->format('Y/m/d H:i')}")
                        ->success()
                        ->duration(5000)
                        ->send();

                    // Clear selection and refresh calendar
                    $this->selectedTrialRequestId = null;
                    $this->dispatch('refresh-calendar');

                    // Refresh the page to show the new session
                    $this->js('setTimeout(() => window.location.reload(), 2000)');

                } catch (\Exception $e) {
                    \Log::error('Trial session creation failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'trial_request_id' => $this->selectedTrialRequestId,
                    ]);

                    Notification::make()
                        ->title('خطأ في جدولة الجلسة التجريبية')
                        ->body($e->getMessage())
                        ->danger()
                        ->duration(8000)
                        ->send();
                }
            })
            ->visible(function () {
                $trialRequest = $this->getSelectedTrialRequest();

                return $trialRequest && in_array($trialRequest['status'], ['pending', 'approved']);
            });
    }

    /**
     * Generate session code for trial sessions
     */
    private function generateTrialSessionCode($trialRequest, Carbon $sessionDateTime): string
    {
        $dateCode = $sessionDateTime->format('Ymd-Hi');
        $teacherId = str_pad(Auth::id(), 3, '0', STR_PAD_LEFT);

        $baseCode = "TR-{$teacherId}-{$dateCode}";

        // Check for uniqueness and add suffix if needed
        $attempt = 0;
        $sessionCode = $baseCode;
        while (
            QuranSession::where('academy_id', $trialRequest->academy_id)
                ->where('session_code', $sessionCode)
                ->exists() && $attempt < 50
        ) {
            $attempt++;
            $sessionCode = $baseCode."-T{$attempt}";
        }

        return $sessionCode;
    }

    /**
     * Extend session generation for all teacher's group circles
     */
    public function extendSessionGeneration(): void
    {
        try {
            $teacherId = Auth::id();
            $totalGenerated = 0;

            // Get all active schedules for this teacher
            $schedules = QuranCircleSchedule::where('quran_teacher_id', $teacherId)
                ->where('is_active', true)
                ->get();

            foreach ($schedules as $schedule) {
                // Update the generation period to 1 month if it's less
                if ($schedule->generate_ahead_days < 30) {
                    $schedule->update(['generate_ahead_days' => 30]);
                }

                // Generate additional sessions
                $generated = $schedule->generateUpcomingSessions();
                $totalGenerated += $generated;
            }

            Notification::make()
                ->title('تم تمديد الجدول بنجاح')
                ->body("تم إنشاء {$totalGenerated} جلسة إضافية لجميع حلقاتك")
                ->success()
                ->duration(5000)
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في تمديد الجدول')
                ->body('حدث خطأ أثناء تمديد جدول الجلسات: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Generate exact number of group sessions based on user input
     */
    private function generateExactGroupSessions($schedule, int $sessionCount): int
    {
        if ($sessionCount <= 0) {
            return 0;
        }

        $circle = $schedule->circle;
        $weeklySchedule = $schedule->weekly_schedule ?? [];

        if (empty($weeklySchedule)) {
            return 0;
        }

        // Ensure we're working in the academy's timezone
        $appTimezone = AcademyContextService::getTimezone();
        $startDate = $this->scheduleStartDate
            ? Carbon::parse($this->scheduleStartDate, $appTimezone)
            : Carbon::now($appTimezone);

        $sessionsCreated = 0;
        $currentDate = $startDate->copy();
        $maxWeeks = 52; // Limit to 1 year to prevent infinite loops
        $weekOffset = 0;

        while ($sessionsCreated < $sessionCount && $weekOffset < $maxWeeks) {
            foreach ($weeklySchedule as $scheduleEntry) {
                if ($sessionsCreated >= $sessionCount) {
                    break;
                }

                $dayName = $scheduleEntry['day'] ?? null;
                $timeString = $scheduleEntry['time'] ?? null;

                if (! $dayName || ! $timeString) {
                    continue;
                }

                // Find the next occurrence of this day in the current week
                $sessionDate = $this->getNextDateForDay($currentDate->copy(), $dayName);
                $sessionDateTime = $sessionDate->setTimeFromTimeString($timeString);

                // Ensure datetime is in app timezone for proper comparison
                $sessionDateTime->timezone($appTimezone);

                // Get current time in app timezone for accurate comparison
                $now = Carbon::now($appTimezone);

                // Skip if this datetime is in the past (comparing in same timezone)
                if ($sessionDateTime->lessThanOrEqualTo($now)) {
                    continue;
                }

                // Check if session already exists for this exact datetime
                $existingSession = QuranSession::where('circle_id', $circle->id)
                    ->where('quran_teacher_id', Auth::id())
                    ->where('scheduled_at', $sessionDateTime)
                    ->first();

                if (! $existingSession) {
                    // Use SessionManagementService to create the session
                    $sessionService = app(\App\Services\SessionManagementService::class);
                    $sessionService->createGroupSession(
                        $circle,
                        $sessionDateTime,
                        $circle->session_duration_minutes ?? 60, // CRITICAL FIX: Use circle duration, not hardcoded 60
                        "جلسة جماعية - {$circle->name_ar}",
                        'جلسة تحفيظ قرآن جماعية مجدولة'
                    );

                    $sessionsCreated++;
                }
            }

            // Move to next week
            $weekOffset++;
            $currentDate->addWeek();
        }

        if ($sessionsCreated > 0) {
            $schedule->update(['last_generated_at' => now()]);
        }

        return $sessionsCreated;
    }

    /**
     * Generate additional group sessions to reach user-specified count (LEGACY - kept for compatibility)
     */
    private function generateAdditionalGroupSessions($schedule, int $additionalCount): int
    {
        if ($additionalCount <= 0) {
            return 0;
        }

        $circle = $schedule->circle;
        $weeklySchedule = $schedule->weekly_schedule ?? [];

        if (empty($weeklySchedule)) {
            return 0;
        }

        // Ensure we're working in the academy's timezone
        $appTimezone = AcademyContextService::getTimezone();
        $startDate = $this->scheduleStartDate
            ? Carbon::parse($this->scheduleStartDate, $appTimezone)
            : Carbon::now($appTimezone);

        $sessionsCreated = 0;
        $weekOffset = 0;
        $maxWeeks = 20; // Limit to prevent infinite loops

        while ($sessionsCreated < $additionalCount && $weekOffset < $maxWeeks) {
            foreach ($weeklySchedule as $scheduleEntry) {
                if ($sessionsCreated >= $additionalCount) {
                    break;
                }

                $day = $scheduleEntry['day'];
                $time = $scheduleEntry['time'];

                // Calculate session date
                $sessionDate = $this->getNextDateForDay($startDate->copy()->addWeeks($weekOffset), $day);
                $sessionDateTime = $sessionDate->setTimeFromTimeString($time);

                // Check if session already exists
                $existingSession = QuranSession::where('circle_id', $circle->id)
                    ->where('quran_teacher_id', Auth::id())
                    ->whereDate('scheduled_at', $sessionDateTime->toDateString())
                    ->whereTime('scheduled_at', $sessionDateTime->toTimeString())
                    ->first();

                if (! $existingSession) {
                    // Create new session
                    QuranSession::create([
                        'academy_id' => $circle->academy_id,
                        'quran_teacher_id' => Auth::id(),
                        'circle_id' => $circle->id,
                        'session_code' => $this->generateGroupSessionCode($circle, $sessionDateTime),
                        'session_type' => 'group',
                        'status' => 'scheduled',
                        'is_scheduled' => true,
                        'title' => "جلسة {$circle->name_ar} - ".$this->getDayNameInArabic($day),
                        'scheduled_at' => $sessionDateTime,
                        'duration_minutes' => $circle->session_duration_minutes ?? 60,
                        'location_type' => 'online',
                        'created_by' => Auth::id(),
                        'scheduled_by' => Auth::id(),
                        'teacher_scheduled_at' => now(),
                    ]);

                    $sessionsCreated++;
                }
            }
            $weekOffset++;
        }

        return $sessionsCreated;
    }

    /**
     * Generate session code for group sessions
     */
    private function generateGroupSessionCode($circle, Carbon $sessionDateTime): string
    {
        $circleCode = $circle->circle_code ?? 'GC';
        $dateCode = $sessionDateTime->format('Ymd-Hi');
        $teacherId = str_pad(Auth::id(), 3, '0', STR_PAD_LEFT);

        $baseCode = "{$circleCode}-{$teacherId}-{$dateCode}";

        // Check for uniqueness and add suffix if needed
        $attempt = 0;
        $sessionCode = $baseCode;
        while (
            QuranSession::where('academy_id', $circle->academy_id)
                ->where('session_code', $sessionCode)
                ->exists() && $attempt < 50
        ) {
            $attempt++;
            $sessionCode = $baseCode."-G{$attempt}";
        }

        return $sessionCode;
    }

    /**
     * Get Arabic day name
     */
    private function getDayNameInArabic(string $day): string
    {
        $dayNames = [
            'saturday' => 'السبت',
            'sunday' => 'الأحد',
            'monday' => 'الاثنين',
            'tuesday' => 'الثلاثاء',
            'wednesday' => 'الأربعاء',
            'thursday' => 'الخميس',
            'friday' => 'الجمعة',
        ];

        return $dayNames[$day] ?? $day;
    }

    /**
     * Get the footer widgets (includes calendar)
     */
    protected function getFooterWidgets(): array
    {
        return [
            TeacherCalendarWidget::make([
                'selectedCircleId' => $this->selectedCircleId,
                'selectedCircleType' => $this->selectedCircleType,
            ]),
            ColorIndicatorsWidget::make(),
        ];
    }
}
