<?php

namespace App\Filament\Teacher\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Models\QuranSession;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranCircleSchedule;
use App\Models\QuranTrialRequest;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use App\Filament\Teacher\Widgets\TeacherCalendarWidget;
use App\Filament\Teacher\Widgets\ColorIndicatorsWidget;
use Illuminate\Contracts\View\View;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Support\Enums\Alignment;
use App\Filament\Teacher\Actions\SessionSchedulingActions;
use App\Services\SessionManagementService;
use Illuminate\Support\Str;


class Calendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.teacher.pages.calendar';
    
    protected static ?string $navigationLabel = 'التقويم';
    
    protected static ?string $title = 'تقويم المعلم';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $navigationGroup = 'جلساتي';

    public ?int $selectedCircleId = null;
    public string $selectedCircleType = 'group'; // 'group' or 'individual'
    public string $activeTab = 'group'; // Tab state for circles, trials
    public ?int $selectedTrialRequestId = null;
    
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
     * Get session statistics for the top 4 boxes
     */
    public function getSessionStatistics(): array
    {
        $teacherProfileId = Auth::user()?->quranTeacherProfile?->id;
        if (!$teacherProfileId) {
            return [
                ['title' => 'جلسات اليوم', 'value' => 0, 'icon' => 'heroicon-o-calendar-days', 'color' => 'primary'],
                ['title' => 'الجلسات القادمة', 'value' => 0, 'icon' => 'heroicon-o-clock', 'color' => 'warning'],
                ['title' => 'مكتملة هذا الشهر', 'value' => 0, 'icon' => 'heroicon-o-check-circle', 'color' => 'success'],
                ['title' => 'في الانتظار', 'value' => 0, 'icon' => 'heroicon-o-exclamation-triangle', 'color' => 'danger'],
            ];
        }
        
        $userId = Auth::id();
        
        // Get today's sessions
        $todaySessions = QuranSession::where(function($query) use ($teacherProfileId, $userId) {
                $query->where('quran_teacher_id', $teacherProfileId)
                      ->orWhere('quran_teacher_id', $userId);
            })
            ->whereDate('scheduled_at', today())
            ->count();

        // Get upcoming sessions (next 7 days)
        $upcomingSessions = QuranSession::where(function($query) use ($teacherProfileId, $userId) {
                $query->where('quran_teacher_id', $teacherProfileId)
                      ->orWhere('quran_teacher_id', $userId);
            })
            ->where('scheduled_at', '>', now())
            ->where('scheduled_at', '<=', now()->addDays(7))
            ->count();

        // Get completed sessions this month
        $completedThisMonth = QuranSession::where(function($query) use ($teacherProfileId, $userId) {
                $query->where('quran_teacher_id', $teacherProfileId)
                      ->orWhere('quran_teacher_id', $userId);
            })
            ->whereMonth('scheduled_at', now()->month)
            ->whereYear('scheduled_at', now()->year)
            ->where('status', 'completed')
            ->count();

        // Get pending/unscheduled sessions
        $pendingSessions = QuranSession::where(function($query) use ($teacherProfileId, $userId) {
                $query->where('quran_teacher_id', $teacherProfileId)
                      ->orWhere('quran_teacher_id', $userId);
            })
            ->where('status', 'pending')
            ->count();

        return [
            [
                'title' => 'جلسات اليوم',
                'value' => $todaySessions,
                'icon' => 'heroicon-o-calendar-days',
                'color' => 'primary'
            ],
            [
                'title' => 'الجلسات القادمة',
                'value' => $upcomingSessions,
                'icon' => 'heroicon-o-clock',
                'color' => 'warning'
            ],
            [
                'title' => 'مكتملة هذا الشهر',
                'value' => $completedThisMonth,
                'icon' => 'heroicon-o-check-circle',
                'color' => 'success'
            ],
            [
                'title' => 'في الانتظار',
                'value' => $pendingSessions,
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'danger'
            ]
        ];
    }

    /**
     * Get group circles for the teacher
     */
    public function getGroupCircles(): Collection
    {
        $teacherProfileId = Auth::user()?->quranTeacherProfile?->id;
        if (!$teacherProfileId) {
            return collect();
        }
        
        return QuranCircle::where('quran_teacher_id', $teacherProfileId)
            ->whereIn('status', ['planning', 'active', 'pending', 'ongoing']) // Include circles that teachers should see
            ->with(['sessions' => function($query) {
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
                    ->whereIn('status', ['scheduled', 'in_progress'])
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
                              !empty($schedule->weekly_schedule) && 
                              ($upcomingSessions > 0 || !$needsMoreSessions);
                
                // Format schedule days and time for display
                $scheduleDays = [];
                $scheduleTime = null;
                
                if ($schedule && $schedule->weekly_schedule) {
                    foreach ($schedule->weekly_schedule as $entry) {
                        if (isset($entry['day'])) {
                            $scheduleDays[] = $entry['day'];
                        }
                        if (isset($entry['time']) && !$scheduleTime) {
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
                $scheduledSessions = $circle->sessions()->whereIn('status', ['scheduled', 'in_progress', 'completed'])->count();
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
        if (!$this->selectedCircleId || !$this->selectedCircleType) {
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
     * Change active tab
     */
    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        // Clear selections when switching tabs
        $this->selectedCircleId = null;
        $this->selectedTrialRequestId = null;
    }

    /**
     * Get trial requests for the teacher
     */
    public function getTrialRequests(): Collection
    {
        $teacherProfileId = Auth::user()?->quranTeacherProfile?->id;
        if (!$teacherProfileId) {
            return collect();
        }
        
        return QuranTrialRequest::where('teacher_id', $teacherProfileId)
            ->whereIn('status', ['pending', 'approved', 'scheduled', 'completed'])
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
        if (!$this->selectedTrialRequestId) {
            return null;
        }
        
        $trialRequests = $this->getTrialRequests();
        return $trialRequests->firstWhere('id', $this->selectedTrialRequestId);
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
                return 'جدولة جلسات - ' . ($circle['name'] ?? '');
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
                        $circle = $this->getSelectedCircle();
                        if (!$circle) return '';
                        
                        if ($circle['type'] === 'individual') {
                            // Calculate smart recommendation for individual circles
                            $remainingSessions = $circle['sessions_remaining'] ?? 0;
                            $monthlySessionsCount = $circle['monthly_sessions'] ?? 4;
                            
                            // Calculate recommended sessions per week
                            $recommendedPerWeek = ceil($monthlySessionsCount / 4);
                            
                            if ($remainingSessions <= 0) {
                                return '⚠️ لا توجد جلسات متبقية في الاشتراك';
                            }
                            
                            return "💡 التوصية: {$recommendedPerWeek} أيام في الأسبوع (بناءً على {$monthlySessionsCount} جلسات شهرياً). يمكنك اختيار أكثر أو أقل حسب الحاجة. الجلسات المتبقية: {$remainingSessions}";
                        } else {
                            $monthlySessionsCount = $circle['monthly_sessions'] ?? 4;
                            $recommendedDaysPerWeek = ceil($monthlySessionsCount / 4);
                            return "💡 الموصى به: {$recommendedDaysPerWeek} أيام في الأسبوع للحلقات الجماعية (بناءً على {$monthlySessionsCount} جلسات شهرياً)";
                        }
                    })
                    ->rules([
                        function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                $circle = $this->getSelectedCircle();
                                if (!$circle || !$value) return;
                                
                                $selectedDaysCount = count($value);
                                
                                if ($circle['type'] === 'group') {
                                    // For group circles: strict limit based on monthly sessions
                                    $monthlySessionsCount = $circle['monthly_sessions'] ?? 4;
                                    $maxDaysPerWeek = ceil($monthlySessionsCount / 4);
                                    
                                    if ($selectedDaysCount > $maxDaysPerWeek) {
                                        $fail("الحد الأقصى للأيام المسموح بها للحلقات الجماعية هو {$maxDaysPerWeek} أيام بناءً على عدد الجلسات الشهرية ({$monthlySessionsCount})");
                                    }
                                } else {
                                    // For individual circles: flexible limits with smart warnings
                                    if ($selectedDaysCount > 7) {
                                        $fail('لا يمكن اختيار أكثر من 7 أيام في الأسبوع');
                                    }
                                    
                                    // Use SessionManagementService for accurate remaining sessions
                                    $circleModel = \App\Models\QuranIndividualCircle::find($circle['id']);
                                    if ($circleModel) {
                                        $sessionService = app(\App\Services\SessionManagementService::class);
                                        $remaining = $sessionService->getRemainingIndividualSessions($circleModel);
                                        
                                        if ($remaining <= 0) {
                                            $fail('لا توجد جلسات متبقية لجدولتها في هذه الحلقة');
                                        }
                                        
                                        // Smart warning for potentially excessive scheduling
                                        $monthlySessionsCount = $circle['monthly_sessions'] ?? 4;
                                        $recommendedPerWeek = ceil($monthlySessionsCount / 4);
                                        
                                        if ($selectedDaysCount > $recommendedPerWeek + 2) {
                                            // Just a warning, not a failure
                                            $fail("تحذير: اخترت {$selectedDaysCount} أيام، وهو أكثر من المعتاد للحلقات الفردية ({$recommendedPerWeek} موصى به). تأكد من وجود جلسات كافية ({$remaining} متبقية)");
                                        }
                                    }
                                }
                            };
                        }
                    ])
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
                            $display = sprintf('%02d:00', $hour) . ' (' . ($hour > 12 ? $hour - 12 : ($hour == 0 ? 12 : $hour)) . ' ' . ($hour >= 12 ? 'م' : 'ص') . ')';
                            $options[$time] = $display;
                        }
                        return $options;
                    })
                    ->helperText('الوقت الذي ستبدأ فيه الجلسات'),
                    
                Forms\Components\TextInput::make('session_count')
                    ->label('عدد الجلسات المطلوب إنشاؤها')
                    ->helperText('حدد عدد الجلسات التي تريد جدولتها (للحلقات الجماعية فقط)')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(100)
                    ->default(function () {
                        $circle = $this->getSelectedCircle();
                        return $circle['monthly_sessions'] ?? 4;
                    })
                    ->placeholder('أدخل العدد')
                    ->visible(fn () => $this->getSelectedCircle()['type'] === 'group'),
                    
                Forms\Components\Placeholder::make('circle_info')
                    ->label('معلومات الحلقة')
                    ->content(function () {
                        $circle = $this->getSelectedCircle();
                        if (!$circle) return 'لم يتم اختيار حلقة';
                        
                        $content = "نوع الحلقة: " . ($circle['type'] === 'group' ? 'جماعية' : 'فردية') . "<br>";
                        
                        if ($circle['type'] === 'group') {
                            $content .= "عدد الطلاب: " . ($circle['students_count'] ?? 0) . "/" . ($circle['max_students'] ?? 0) . "<br>";
                            $content .= "الجلسات الشهرية: " . ($circle['monthly_sessions'] ?? 4);
                        } else {
                            $content .= "الطالب: " . ($circle['student_name'] ?? 'غير محدد') . "<br>";
                            $content .= "إجمالي الجلسات: " . ($circle['sessions_count'] ?? 0) . "<br>";
                            $content .= "المجدولة: " . ($circle['sessions_scheduled'] ?? 0) . " | المتبقية: " . ($circle['sessions_remaining'] ?? 0);
                        }
                        
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
                if (!$selectedCircle) return true;
                
                return !($selectedCircle['type'] === 'group' || ($selectedCircle['can_schedule'] ?? false));
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
            'selectedCircleId' => 'required|integer',
        ]);

        $selectedCircle = $this->getSelectedCircle();
        if (!$selectedCircle) {
            Notification::make()
                ->title('خطأ')
                ->body('يرجى اختيار حلقة أولاً')
                ->danger()
                ->send();
            return;
        }

        try {
            $sessionsCreated = 0;
            
            if ($selectedCircle['type'] === 'group') {
                $sessionsCreated = $this->createGroupCircleSchedule($selectedCircle);
            } else {
                $sessionsCreated = $this->createIndividualCircleSchedule($selectedCircle);
            }

            // Modal closes automatically after successful action
            
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
                ->body('حدث خطأ أثناء إنشاء الجدول: ' . $e->getMessage())
                ->danger()
                ->send();
        }
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
        $teacherProfileId = Auth::user()?->quranTeacherProfile?->id;
        $existingSchedule = QuranCircleSchedule::where([
            'academy_id' => $circle->academy_id,
            'circle_id' => $circle->id,
            'quran_teacher_id' => $teacherProfileId,
            'is_active' => true,
        ])->first();
        
        if ($existingSchedule) {
            // Compare existing schedule with new one
            $existingWeeklySchedule = $existingSchedule->weekly_schedule ?? [];
            
            // Sort both arrays for comparison
            $sortedExisting = collect($existingWeeklySchedule)->sortBy('day')->values()->toArray();
            $sortedNew = collect($weeklySchedule)->sortBy('day')->values()->toArray();
            
            if ($sortedExisting === $sortedNew) {
                throw new \Exception('هذه الحلقة مجدولة بالفعل بنفس الأيام والأوقات المحددة');
            }
            
            // If different, update the existing schedule
            $existingSchedule->update([
                'weekly_schedule' => $weeklySchedule,
                'updated_by' => Auth::id(),
            ]);
            
            $schedule = $existingSchedule;
        } else {
            // Create new schedule
            $schedule = QuranCircleSchedule::create([
                'academy_id' => $circle->academy_id,
                'circle_id' => $circle->id,
                'quran_teacher_id' => $teacherProfileId,
                'weekly_schedule' => $weeklySchedule,
                'timezone' => config('app.timezone', 'UTC'),
                'default_duration_minutes' => 60,
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
        if (!$circle->subscription) {
            throw new \Exception('لا يمكن جدولة جلسات لحلقة بدون اشتراك صالح');
        }
        
        // Check if subscription is active
        if ($circle->subscription->subscription_status !== 'active') {
            throw new \Exception('الاشتراك غير نشط. يجب تفعيل الاشتراك لجدولة الجلسات');
        }
        
        // Check subscription end date if it exists
        if ($circle->subscription->expires_at && $circle->subscription->expires_at->isPast()) {
            throw new \Exception('انتهى الاشتراك ولا يمكن جدولة جلسات جديدة');
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
        $weeksToSchedule = 8; // Schedule for next 8 weeks
        
        // CRITICAL: Never exceed subscription remaining sessions limit
        $maxSessionsToSchedule = min($selectedDaysCount * $weeksToSchedule, $remainingSessions);
        
        // Use custom start date if provided, otherwise start from now
        $startDate = $this->scheduleStartDate ? Carbon::parse($this->scheduleStartDate) : Carbon::now();
        $scheduledCount = 0;
        $weekCount = 0;
        
        // Schedule sessions across multiple weeks in the selected days
        while ($scheduledCount < $maxSessionsToSchedule && $weekCount < $weeksToSchedule) {
            foreach ($this->scheduleDays as $day) {
                // CRITICAL: Double-check we haven't exceeded the limit
                if ($scheduledCount >= $maxSessionsToSchedule) break;
                
                // CRITICAL: Re-check remaining sessions in real-time to prevent race conditions
                $currentRemaining = $sessionService->getRemainingIndividualSessions($circle);
                if ($currentRemaining <= 0) {
                    break;
                }
                
                // Find the next occurrence of this day
                $sessionDate = $this->getNextDateForDay($startDate->copy()->addWeeks($weekCount), $day);
                $sessionDateTime = $sessionDate->setTimeFromTimeString($this->scheduleTime);
                
                // Check if session date is beyond subscription end date (if end date exists)
                if ($circle->subscription->expires_at && $sessionDateTime->isAfter($circle->subscription->expires_at)) {
                    // Skip this session as it's beyond the subscription period
                    continue;
                }
                
                // Check if session already exists for this date/time
                $existingSession = QuranSession::where('individual_circle_id', $circle->id)
                    ->where('quran_teacher_id', Auth::id())
                    ->whereDate('scheduled_at', $sessionDateTime->toDateString())
                    ->whereTime('scheduled_at', $sessionDateTime->toTimeString())
                    ->first();
                
                if (!$existingSession) {
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
                        'counts_toward_subscription' => true, // CRITICAL: Ensure this counts toward limits
                        'title' => "جلسة فردية - {$circle->student->name}",
                        'scheduled_at' => $sessionDateTime,
                        'duration_minutes' => $circle->session_duration_minutes ?? 60,
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
            $sessionCode = $baseCode . "-A{$attempt}";
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
                return 'جدولة جلسة تجريبية - ' . ($trialRequest['student_name'] ?? '');
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
                    ->helperText('اختر التاريخ والوقت المناسب للطالب')
                    ->rules([
                        function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                if (!$value) return;
                                
                                $scheduledAt = Carbon::parse($value);
                                
                                // Check if date is in the past
                                if ($scheduledAt->isPast()) {
                                    $fail('لا يمكن جدولة الجلسة في وقت ماضي');
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
                        }
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
                        if (!$trialRequest) return 'لم يتم اختيار طلب';
                        
                        $content = "الطالب: " . $trialRequest['student_name'] . "<br>";
                        $content .= "الهاتف: " . $trialRequest['phone'] . "<br>";
                        $content .= "المستوى: " . $trialRequest['level_label'] . "<br>";
                        $content .= "الوقت المفضل: " . $trialRequest['preferred_time_label'] . "<br>";
                        if ($trialRequest['notes']) {
                            $content .= "ملاحظات: " . Str::limit($trialRequest['notes'], 100);
                        }
                        
                        return new \Illuminate\Support\HtmlString($content);
                    })
                    ->columnSpanFull(),
            ])
            ->action(function (array $data) {
                try {
                    $trialRequest = QuranTrialRequest::find($this->selectedTrialRequestId);
                    if (!$trialRequest) {
                        throw new \Exception('طلب الجلسة التجريبية غير موجود');
                    }

                    $scheduledAt = Carbon::parse($data['scheduled_at']);
                    $teacherResponse = $data['teacher_response'] ?? 'تم جدولة الجلسة التجريبية';

                    // Update trial request with scheduled date
                    $trialRequest->update([
                        'scheduled_at' => $scheduledAt,
                        'status' => QuranTrialRequest::STATUS_SCHEDULED,
                        'teacher_response' => $teacherResponse,
                        'responded_at' => now(),
                    ]);

                    // Create a session record for the trial
                    $sessionCode = $this->generateTrialSessionCode($trialRequest, $scheduledAt);
                    $teacherProfileId = Auth::user()?->quranTeacherProfile?->id ?? Auth::id();
                    
                    $session = QuranSession::create([
                        'academy_id' => $trialRequest->academy_id,
                        'session_code' => $sessionCode,
                        'session_type' => 'trial',
                        'quran_teacher_id' => $teacherProfileId,
                        'student_id' => $trialRequest->student_id,
                        'trial_request_id' => $trialRequest->id,
                        'scheduled_at' => $scheduledAt,
                        'duration_minutes' => 30,
                        'status' => 'scheduled',
                        'title' => "جلسة تجريبية - {$trialRequest->student_name}",
                        'description' => $teacherResponse,
                        'notes' => 'جلسة تجريبية مجدولة',
                        'location_type' => 'online',
                        'created_by' => Auth::id(),
                        'scheduled_by' => Auth::id(),
                        'teacher_scheduled_at' => now(),
                        'session_data' => json_encode([
                            'is_trial' => true,
                            'student_level' => $trialRequest->current_level,
                            'preferred_time' => $trialRequest->preferred_time,
                            'contact_phone' => $trialRequest->phone,
                            'learning_goals' => $trialRequest->learning_goals,
                            'teacher_response' => $teacherResponse,
                        ])
                    ]);

                    // Link trial session to the request
                    $trialRequest->update(['trial_session_id' => $session->id]);

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
                    Notification::make()
                        ->title('خطأ في جدولة الجلسة التجريبية')
                        ->body($e->getMessage())
                        ->danger()
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
            $sessionCode = $baseCode . "-T{$attempt}";
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
                ->body('حدث خطأ أثناء تمديد جدول الجلسات: ' . $e->getMessage())
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
        
        $startDate = $this->scheduleStartDate ? Carbon::parse($this->scheduleStartDate) : Carbon::now();
        $sessionsCreated = 0;
        $currentDate = $startDate->copy();
        $maxWeeks = 52; // Limit to 1 year to prevent infinite loops
        $weekOffset = 0;
        
        while ($sessionsCreated < $sessionCount && $weekOffset < $maxWeeks) {
            foreach ($weeklySchedule as $scheduleEntry) {
                if ($sessionsCreated >= $sessionCount) break;
                
                $dayName = $scheduleEntry['day'] ?? null;
                $timeString = $scheduleEntry['time'] ?? null;
                
                if (!$dayName || !$timeString) continue;
                
                // Find the next occurrence of this day in the current week
                $sessionDate = $this->getNextDateForDay($currentDate->copy(), $dayName);
                $sessionDateTime = $sessionDate->setTimeFromTimeString($timeString);
                
                // Skip if this datetime is in the past
                if ($sessionDateTime->isPast()) {
                    continue;
                }
                
                // Check if session already exists for this exact datetime
                $existingSession = QuranSession::where('circle_id', $circle->id)
                    ->where('quran_teacher_id', Auth::id())
                    ->where('scheduled_at', $sessionDateTime)
                    ->first();
                
                if (!$existingSession) {
                    // Use SessionManagementService to create the session
                    $sessionService = app(\App\Services\SessionManagementService::class);
                    $sessionService->createGroupSession(
                        $circle,
                        $sessionDateTime,
                        60, // duration
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
        
        $startDate = $this->scheduleStartDate ? Carbon::parse($this->scheduleStartDate) : Carbon::now();
        $sessionsCreated = 0;
        $weekOffset = 0;
        $maxWeeks = 20; // Limit to prevent infinite loops
        
        while ($sessionsCreated < $additionalCount && $weekOffset < $maxWeeks) {
            foreach ($weeklySchedule as $scheduleEntry) {
                if ($sessionsCreated >= $additionalCount) break;
                
                $day = $scheduleEntry['day'];
                $time = $scheduleEntry['time'];
                
                // Calculate session date
                $sessionDate = $this->getNextDateForDay($startDate->copy()->addWeeks($weekOffset), $day);
                $sessionDateTime = $sessionDate->setTimeFromTimeString($time);
                
                // Check if session already exists
                $existingSession = QuranSession::where('circle_id', $circle->id)
                    ->where('quran_teacher_id', Auth::user()->quranTeacherProfile->id ?? Auth::id())
                    ->whereDate('scheduled_at', $sessionDateTime->toDateString())
                    ->whereTime('scheduled_at', $sessionDateTime->toTimeString())
                    ->first();
                
                if (!$existingSession) {
                    // Create new session
                    QuranSession::create([
                        'academy_id' => $circle->academy_id,
                        'quran_teacher_id' => Auth::user()->quranTeacherProfile->id ?? Auth::id(),
                        'circle_id' => $circle->id,
                        'session_code' => $this->generateGroupSessionCode($circle, $sessionDateTime),
                        'session_type' => 'group',
                        'status' => 'scheduled',
                        'is_scheduled' => true,
                        'title' => "جلسة {$circle->name_ar} - " . $this->getDayNameInArabic($day),
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
            $sessionCode = $baseCode . "-G{$attempt}";
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