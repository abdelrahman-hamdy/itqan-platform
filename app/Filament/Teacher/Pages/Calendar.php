<?php

namespace App\Filament\Teacher\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Models\QuranSession;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranCircleSchedule;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use App\Filament\Teacher\Widgets\TeacherCalendarWidget;
use Illuminate\Contracts\View\View;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Support\Enums\Alignment;


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
    public string $activeTab = 'group'; // Tab state for circles
    
    // Scheduling form properties
    public array $scheduleDays = [];
    public ?string $scheduleTime = null;

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
        $teacherId = Auth::id();
        
        // Get today's sessions
        $todaySessions = QuranSession::where('quran_teacher_id', $teacherId)
            ->whereDate('scheduled_at', today())
            ->count();

        // Get upcoming sessions (next 7 days)
        $upcomingSessions = QuranSession::where('quran_teacher_id', $teacherId)
            ->where('scheduled_at', '>', now())
            ->where('scheduled_at', '<=', now()->addDays(7))
            ->count();

        // Get completed sessions this month
        $completedThisMonth = QuranSession::where('quran_teacher_id', $teacherId)
            ->whereMonth('scheduled_at', now()->month)
            ->whereYear('scheduled_at', now()->year)
            ->where('status', 'completed')
            ->count();

        // Get pending/unscheduled sessions
        $pendingSessions = QuranSession::where('quran_teacher_id', $teacherId)
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
        $teacherId = Auth::id();
        
        return QuranCircle::where('quran_teacher_id', $teacherId)
            ->with(['sessions' => function($query) {
                $query->where('scheduled_at', '>=', now()->startOfWeek())
                      ->where('scheduled_at', '<=', now()->addMonths(2));
            }, 'schedule'])
            ->get()
            ->map(function ($circle) {
                $schedule = $circle->schedule;
                $isScheduled = $schedule && $schedule->is_active && !empty($schedule->weekly_schedule);
                $sessionsCount = $circle->sessions()->count();
                
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
        $teacherId = Auth::id();
        
        return QuranIndividualCircle::where('quran_teacher_id', $teacherId)
            ->with(['subscription.package', 'sessions'])
            ->get()
            ->map(function ($circle) {
                $subscription = $circle->subscription;
                $scheduledSessions = $circle->sessions()->where('is_scheduled', true)->count();
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
        // Clear selection when switching tabs
        $this->selectedCircleId = null;
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
                        
                        if ($circle['type'] === 'group') {
                            $maxDays = ceil(($circle['monthly_sessions'] ?? 4) / 4);
                            return "الحد الأقصى: {$maxDays} أيام في الأسبوع للحلقات الجماعية";
                        } else {
                            $remaining = $circle['sessions_remaining'] ?? 0;
                            $scheduled = $circle['sessions_scheduled'] ?? 0;
                            return "الجلسات المجدولة: {$scheduled} | الجلسات المتبقية: {$remaining}";
                        }
                    })
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        $this->validateDaysSelection();
                    }),
                    
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
        $existingSchedule = QuranCircleSchedule::where([
            'academy_id' => $circle->academy_id,
            'circle_id' => $circle->id,
            'quran_teacher_id' => Auth::id(),
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
                'quran_teacher_id' => Auth::id(),
                'weekly_schedule' => $weeklySchedule,
                'timezone' => config('app.timezone', 'UTC'),
                'default_duration_minutes' => 60,
                'is_active' => true,
                'schedule_starts_at' => Carbon::now()->startOfDay(),
                'generate_ahead_days' => 180, // Generate 6 months ahead
                'generate_before_hours' => 1,
                'session_title_template' => 'جلسة {circle_name} - {day} {time}',
                'session_description_template' => 'جلسة حلقة القرآن المجدولة تلقائياً',
                'recording_enabled' => false,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        }
        
        // Activate the schedule which will update circle status and generate sessions
        $generatedCount = $schedule->activateSchedule();
        
        // Generate additional sessions for the extended period
        $additionalSessions = $schedule->generateUpcomingSessions();
        
        return $generatedCount + $additionalSessions;
    }

    /**
     * Create schedule for individual circle
     */
    private function createIndividualCircleSchedule(array $circleData): int
    {
        $circle = QuranIndividualCircle::findOrFail($circleData['id']);
        
        // Get remaining sessions count
        $remainingSessions = $circleData['sessions_remaining'] ?? 0;
        
        if ($remainingSessions <= 0) {
            throw new \Exception('لا توجد جلسات متبقية للجدولة في هذه الحلقة');
        }

        // Validate subscription end date
        if (!$circle->subscription || !$circle->subscription->ends_at) {
            throw new \Exception('لا يمكن جدولة جلسات لحلقة بدون اشتراك صالح');
        }
        
        $subscriptionEndDate = $circle->subscription->ends_at->endOfDay();
        if ($subscriptionEndDate->isPast()) {
            throw new \Exception('انتهى الاشتراك ولا يمكن جدولة جلسات جديدة');
        }

        // For individual circles, allow flexible scheduling
        // Calculate how many sessions to schedule per week cycle
        $selectedDaysCount = count($this->scheduleDays);
        $weeksToSchedule = 8; // Schedule for next 8 weeks
        $maxSessionsToSchedule = min($selectedDaysCount * $weeksToSchedule, $remainingSessions);
        
        $startDate = Carbon::now();
        $scheduledCount = 0;
        $weekCount = 0;
        
        // Schedule sessions across multiple weeks in the selected days
        while ($scheduledCount < $maxSessionsToSchedule && $weekCount < $weeksToSchedule) {
            foreach ($this->scheduleDays as $day) {
                if ($scheduledCount >= $maxSessionsToSchedule) break;
                
                // Find the next occurrence of this day
                $sessionDate = $this->getNextDateForDay($startDate->copy()->addWeeks($weekCount), $day);
                $sessionDateTime = $sessionDate->setTimeFromTimeString($this->scheduleTime);
                
                // Check if session date is beyond subscription end date
                if ($sessionDateTime->isAfter($subscriptionEndDate)) {
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
                    // Create new session
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
            throw new \Exception('لم يتم إنشاء أي جلسات. تأكد من عدم وجود تعارض في المواعيد');
        }
        
        // Update circle session counts if method exists
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
     * Validate selected days count based on circle type and available sessions
     */
    public function validateDaysSelection(): void
    {
        $selectedCircle = $this->getSelectedCircle();
        if (!$selectedCircle) return;
        
        if ($selectedCircle['type'] === 'group') {
            // For group circles: validate based on monthly sessions and weekly distribution
            $monthlySessionsCount = $selectedCircle['monthly_sessions'] ?? 4;
            $maxDaysPerWeek = ceil($monthlySessionsCount / 4);
            
            if (count($this->scheduleDays) > $maxDaysPerWeek) {
                $this->scheduleDays = array_slice($this->scheduleDays, 0, $maxDaysPerWeek);
                
                Notification::make()
                    ->title('تنبيه')
                    ->body("الحد الأقصى للأيام المسموح بها هو {$maxDaysPerWeek} أيام بناءً على عدد الجلسات الشهرية")
                    ->warning()
                    ->send();
            }
        } else {
            // For individual circles: more flexible, but warn if selecting too many days at once
            $remainingSessions = $selectedCircle['sessions_remaining'] ?? 0;
            $selectedDaysCount = count($this->scheduleDays);
            
            // Allow any number of days but warn if trying to schedule more than 7 days per week
            if ($selectedDaysCount > 7) {
                $this->scheduleDays = array_slice($this->scheduleDays, 0, 7);
                
                Notification::make()
                    ->title('تنبيه')
                    ->body("لا يمكن اختيار أكثر من 7 أيام في الأسبوع")
                    ->warning()
                    ->send();
            } elseif ($remainingSessions <= 0) {
                Notification::make()
                    ->title('تنبيه')
                    ->body("لا توجد جلسات متبقية لجدولتها في هذه الحلقة")
                    ->warning()
                    ->send();
            }
        }
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
                // Update the generation period to 6 months if it's less
                if ($schedule->generate_ahead_days < 180) {
                    $schedule->update(['generate_ahead_days' => 180]);
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
     * Get the footer widgets (includes calendar)
     */
    protected function getFooterWidgets(): array
    {
        return [
            TeacherCalendarWidget::make([
                'selectedCircleId' => $this->selectedCircleId,
                'selectedCircleType' => $this->selectedCircleType,
            ]),
        ];
    }
}