<?php

namespace App\Filament\Supervisor\Pages;

use Filament\Schemas\Components\Group;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use App\Enums\CalendarSessionType;
use App\Filament\Shared\Traits\HandlesScheduling;
use App\Models\User;
use App\Services\AcademyContextService;
use App\Services\Calendar\SessionStrategyFactory;
use App\Services\Calendar\SessionStrategyInterface;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Supervisor Calendar Page
 *
 * Allows supervisors to view and manage sessions for their assigned teachers.
 * Uses a dropdown to select the teacher, then displays their calendar.
 *
 * @property Collection $resourceItems Livewire computed property
 */
class SupervisorCalendar extends Page implements HasForms
{
    use HandlesScheduling;
    use InteractsWithForms;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected string $view = 'filament.supervisor.pages.supervisor-calendar';

    protected static ?string $navigationLabel = 'تقويم الجلسات';

    protected static ?string $title = 'تقويم المعلمين';

    protected static ?int $navigationSort = 1;

    protected static string | \UnitEnum | null $navigationGroup = '';

    // Selected teacher data
    public ?int $selectedTeacherId = null;

    public ?string $selectedTeacherType = null;

    public ?string $selectedTeacherKey = null;

    // Resource tabs state
    public string $activeResourceTab = '';

    // Selected resource item for scheduling
    public ?int $selectedItemId = null;

    public ?string $selectedItemType = null;

    // Scheduling form properties
    public array $scheduleDays = [];

    public ?string $scheduleTime = null;

    public ?string $scheduleStartDate = null;

    public int $sessionCount = 4;

    // Strategy pattern for resource fetching
    protected ?SessionStrategyInterface $strategy = null;

    protected SessionStrategyFactory $strategyFactory;

    public function boot(SessionStrategyFactory $strategyFactory): void
    {
        $this->strategyFactory = $strategyFactory;
    }

    public function mount(): void
    {
        $user = Auth::user();

        // Auto-select first available teacher
        $teachers = $this->getTeacherOptions();
        if (! empty($teachers)) {
            $firstKey = array_key_first($teachers);
            $this->selectedTeacherKey = $firstKey;
            $this->selectTeacher($firstKey);
        }
    }

    /**
     * Get assigned Quran teacher IDs
     */
    protected function getAssignedQuranTeacherIds(): array
    {
        $profile = Auth::user()?->supervisorProfile;

        return $profile?->getAssignedQuranTeacherIds() ?? [];
    }

    /**
     * Get assigned Academic teacher IDs (User IDs)
     */
    protected function getAssignedAcademicTeacherIds(): array
    {
        $profile = Auth::user()?->supervisorProfile;

        return $profile?->getAssignedAcademicTeacherIds() ?? [];
    }

    /**
     * Get all teacher options for dropdown
     */
    public function getTeacherOptions(): array
    {
        $options = [];

        // Get Quran teachers
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        if (! empty($quranTeacherIds)) {
            $quranTeachers = User::whereIn('id', $quranTeacherIds)
                ->get()
                ->mapWithKeys(fn ($user) => [
                    'quran_'.$user->id => ($user->name ?? $user->email).' (معلم قرآن)',
                ]);
            $options = array_merge($options, $quranTeachers->toArray());
        }

        // Get Academic teachers
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();
        if (! empty($academicTeacherIds)) {
            $academicTeachers = User::whereIn('id', $academicTeacherIds)
                ->get()
                ->mapWithKeys(fn ($user) => [
                    'academic_'.$user->id => ($user->name ?? $user->email).' (معلم أكاديمي)',
                ]);
            $options = array_merge($options, $academicTeachers->toArray());
        }

        return $options;
    }

    /**
     * Parse teacher key to get ID and type
     */
    protected function parseTeacherKey(?string $key): array
    {
        if (! $key) {
            return [null, null];
        }

        if (str_starts_with($key, 'quran_')) {
            return [(int) str_replace('quran_', '', $key), 'quran_teacher'];
        }

        if (str_starts_with($key, 'academic_')) {
            return [(int) str_replace('academic_', '', $key), 'academic_teacher'];
        }

        return [null, null];
    }

    /**
     * Select a teacher to view their calendar
     */
    public function selectTeacher(?string $teacherKey): void
    {
        [$teacherId, $teacherType] = $this->parseTeacherKey($teacherKey);

        $this->selectedTeacherId = $teacherId;
        $this->selectedTeacherType = $teacherType;
        $this->selectedTeacherKey = $teacherKey;

        // Clear item selection when changing teachers
        $this->selectedItemId = null;

        // Set default active resource tab based on teacher type
        $tabs = $this->getResourceTabConfiguration();
        $this->activeResourceTab = ! empty($tabs) ? array_key_first($tabs) : '';

        // Set initial selected item type based on the active tab
        if ($this->activeResourceTab) {
            $this->selectedItemType = $this->getItemTypeFromTabKey($this->activeResourceTab);
        } else {
            $this->selectedItemType = null;
        }
    }

    /**
     * Handle updated selectedTeacherKey from Livewire binding
     */
    public function updatedSelectedTeacherKey(?string $value): void
    {
        $this->selectTeacher($value);
    }

    /**
     * Get the current teacher key for the dropdown
     */
    public function getTeacherKey(): ?string
    {
        if (! $this->selectedTeacherId || ! $this->selectedTeacherType) {
            return null;
        }

        $prefix = match ($this->selectedTeacherType) {
            'quran_teacher' => 'quran_',
            'academic_teacher' => 'academic_',
            default => '',
        };

        return $prefix.$this->selectedTeacherId;
    }

    /**
     * Get the selected teacher model
     */
    public function getSelectedTeacher(): ?User
    {
        if (! $this->selectedTeacherId) {
            return null;
        }

        return User::find($this->selectedTeacherId);
    }

    /**
     * Get the teacher's name for display
     */
    public function getSelectedTeacherName(): string
    {
        $teacher = $this->getSelectedTeacher();

        return $teacher?->name ?? $teacher?->email ?? 'اختر معلم';
    }

    /**
     * Get session types for the selected teacher
     */
    public function getSessionTypesForTeacher(): array
    {
        if (! $this->selectedTeacherType) {
            return [];
        }

        return match ($this->selectedTeacherType) {
            'quran_teacher' => CalendarSessionType::forQuranTeacher(),
            'academic_teacher' => CalendarSessionType::forAcademicTeacher(),
            default => [],
        };
    }

    /**
     * Get or create the strategy for the selected teacher
     */
    protected function getStrategy(): ?SessionStrategyInterface
    {
        if (! $this->selectedTeacherId || ! $this->selectedTeacherType) {
            return null;
        }

        // Cache the strategy and invalidate when teacher changes
        if (! $this->strategy || $this->strategy->getTargetUser()?->id !== $this->selectedTeacherId) {
            $this->strategy = $this->strategyFactory->makeForUser(
                $this->selectedTeacherType,
                $this->selectedTeacherId
            );
        }

        return $this->strategy;
    }

    /**
     * Check if supervisor has any assigned teachers
     */
    public function hasAssignedTeachers(): bool
    {
        return ! empty($this->getAssignedQuranTeacherIds())
            || ! empty($this->getAssignedAcademicTeacherIds());
    }

    /**
     * Check if a teacher is selected
     */
    public function hasSelectedTeacher(): bool
    {
        return $this->selectedTeacherId !== null && $this->selectedTeacherType !== null;
    }

    /**
     * Check if page should be registered in navigation
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        $profile = $user?->supervisorProfile;

        if (! $profile) {
            return false;
        }

        $quranTeachers = $profile->getAssignedQuranTeacherIds();
        $academicTeachers = $profile->getAssignedAcademicTeacherIds();

        return ! empty($quranTeachers) || ! empty($academicTeachers);
    }

    /**
     * Get color legend data for the selected teacher type
     */
    public function getColorLegendData(): array
    {
        $sessionTypes = [];
        $statusIndicators = [
            ['label' => 'مجدولة', 'color' => '#3B82F6'],
            ['label' => 'مكتملة', 'color' => '#22C55E'],
            ['label' => 'ملغاة', 'color' => '#EF4444'],
            ['label' => 'مباشرة', 'color' => '#F97316'],
        ];

        if ($this->selectedTeacherType === 'quran_teacher') {
            $sessionTypes = [
                ['label' => 'جلسة فردية', 'color' => '#10B981', 'icon' => 'heroicon-m-user'],
                ['label' => 'حلقة جماعية', 'color' => '#3B82F6', 'icon' => 'heroicon-m-user-group'],
                ['label' => 'جلسة تجريبية', 'color' => '#F59E0B', 'icon' => 'heroicon-m-clock'],
            ];
        } elseif ($this->selectedTeacherType === 'academic_teacher') {
            $sessionTypes = [
                ['label' => 'درس خاص', 'color' => '#8B5CF6', 'icon' => 'heroicon-m-academic-cap'],
                ['label' => 'دورة تفاعلية', 'color' => '#EC4899', 'icon' => 'heroicon-m-play-circle'],
            ];
        }

        return [
            'sessionTypes' => $sessionTypes,
            'statusIndicators' => $statusIndicators,
        ];
    }

    /**
     * Get resource tab configuration based on selected teacher type
     * Delegates to strategy for consistent tab configuration
     */
    public function getResourceTabConfiguration(): array
    {
        $strategy = $this->getStrategy();

        if (! $strategy) {
            return [];
        }

        return $strategy->getTabConfiguration();
    }

    /**
     * Set active resource tab
     */
    public function setActiveResourceTab(string $tab): void
    {
        $this->activeResourceTab = $tab;
        // Clear selection when switching tabs
        $this->selectedItemId = null;
        $this->selectedItemType = $this->getItemTypeFromTabKey($tab);
    }

    /**
     * Handle item selection for scheduling
     */
    public function selectItem(int $itemId, string $type): void
    {
        $this->selectedItemId = $itemId;
        $this->selectedItemType = $type;
    }

    /**
     * Get the currently selected item data
     */
    public function getSelectedItem(): ?array
    {
        if (! $this->selectedItemId || ! $this->selectedItemType) {
            return null;
        }

        $items = $this->resourceItems;

        return $items->firstWhere('id', $this->selectedItemId);
    }

    /**
     * Get resources for the active tab
     * Delegates to strategy for consistent resource fetching
     */
    public function getResourceItemsProperty(): Collection
    {
        $strategy = $this->getStrategy();

        if (! $strategy || ! $this->activeResourceTab) {
            return collect();
        }

        $tabConfig = $strategy->getTabConfiguration();
        $methodName = $tabConfig[$this->activeResourceTab]['items_method'] ?? null;

        if (! $methodName || ! method_exists($strategy, $methodName)) {
            return collect();
        }

        return $strategy->$methodName();
    }

    /**
     * Get resource section heading
     * Delegates to strategy for consistent labeling
     */
    public function getResourceSectionHeading(): string
    {
        $strategy = $this->getStrategy();

        if ($strategy) {
            return $strategy->getSectionHeading();
        }

        return 'الموارد';
    }

    /**
     * Get resource section description
     * Delegates to strategy for consistent labeling
     */
    public function getResourceSectionDescription(): string
    {
        $strategy = $this->getStrategy();

        if ($strategy) {
            return $strategy->getSectionDescription();
        }

        return 'الموارد المسندة للمعلم';
    }

    /**
     * Get item type from tab key
     */
    protected function getItemTypeFromTabKey(string $tabKey): string
    {
        return match ($tabKey) {
            'group' => 'group',
            'individual' => 'individual',
            'trials' => 'trial',
            'private_lessons' => 'private_lesson',
            'interactive_courses' => 'interactive_course',
            default => $tabKey,
        };
    }

    /**
     * Schedule action for creating bulk sessions
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

                return 'جدولة جلسات - '.($item['name'] ?? '');
            })
            ->modalDescription('اختر أيام الأسبوع ووقت الجلسات لإنشاء جدول تلقائي')
            ->modalSubmitActionLabel('إنشاء الجدول')
            ->modalCancelActionLabel('إلغاء')
            ->schema([$this->buildScheduleForm()])
            ->action(function (array $data) {
                $this->scheduleDays = $data['schedule_days'] ?? [];
                $this->scheduleTime = $data['schedule_time'] ?? null;
                $this->scheduleStartDate = $data['schedule_start_date'] ?? null;

                // Trial sessions always have exactly 1 session
                if ($this->selectedItemType === 'trial') {
                    $this->sessionCount = 1;
                } else {
                    $this->sessionCount = $data['session_count'] ?? 4;
                }

                $this->createBulkSchedule();
            })
            ->visible(fn () => $this->selectedItemId !== null);
    }

    /**
     * Build the unified schedule form
     */
    protected function buildScheduleForm(): Group
    {
        $item = $this->getSelectedItem();
        $strategy = $this->getStrategy();
        $validator = ($item && $strategy) ? $strategy->getValidator($this->selectedItemType, $item) : null;

        return Group::make([
            CheckboxList::make('schedule_days')
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
                ->helperText(function () use ($validator) {
                    if (! $validator) {
                        return '';
                    }
                    $recommendations = $validator->getRecommendations();

                    return "💡 {$recommendations['reason']}";
                })
                ->live(),

            DatePicker::make('schedule_start_date')
                ->label('تاريخ بداية الجدولة')
                ->helperText(function () use ($item) {
                    if ($item && isset($item['start_date']) && $item['start_date']) {
                        return 'تاريخ بداية الدورة: '.$item['start_date'];
                    }

                    return 'تاريخ البداية لجدولة الجلسات الجديدة';
                })
                ->default(function () use ($item) {
                    // For interactive courses, default to the course's start_date
                    if ($item && isset($item['type']) && $item['type'] === 'interactive_course') {
                        if (isset($item['start_date']) && $item['start_date']) {
                            try {
                                $startDate = Carbon::parse(str_replace('/', '-', $item['start_date']));
                                if ($startDate->gte(now()->startOfDay())) {
                                    return $startDate->format('Y-m-d');
                                }
                            } catch (Exception $e) {
                                // Fall back to null if parsing fails
                            }
                        }
                    }

                    return null;
                })
                ->minDate(AcademyContextService::nowInAcademyTimezone()->format('Y-m-d'))
                ->maxDate(function () use ($validator) {
                    if (! $validator) {
                        return null;
                    }
                    if (method_exists($validator, 'getMaxScheduleDate')) {
                        return $validator->getMaxScheduleDate()?->format('Y-m-d');
                    }

                    return null;
                })
                ->native(false)
                ->displayFormat('Y/m/d')
                ->closeOnDateSelection()
                ->live(),

            Select::make('schedule_time')
                ->label('وقت الجلسة')
                ->required()
                ->placeholder('اختر الساعة')
                ->options(function () {
                    $options = [];
                    for ($hour = 0; $hour <= 23; $hour++) {
                        $time = sprintf('%02d:00', $hour);
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

            TextInput::make('session_count')
                ->label('عدد الجلسات المطلوب إنشاؤها')
                ->helperText(function () use ($item) {
                    if ($this->selectedItemType === 'trial') {
                        return 'الجلسات التجريبية تتكون دائماً من جلسة واحدة فقط';
                    }

                    if (! $item) {
                        return 'حدد عدد الجلسات التي تريد جدولتها';
                    }

                    $remaining = $item['sessions_remaining'] ?? 0;
                    if ($remaining > 0) {
                        return "حدد عدد الجلسات التي تريد جدولتها (المتبقية: {$remaining} جلسة)";
                    }

                    return 'حدد عدد الجلسات التي تريد جدولتها (الحد الأقصى: 100 جلسة)';
                })
                ->numeric()
                ->required()
                ->minValue(1)
                ->maxValue(function () use ($item) {
                    if ($this->selectedItemType === 'trial') {
                        return 1;
                    }

                    if (! $item) {
                        return 100;
                    }

                    if (isset($item['sessions_remaining']) && $item['sessions_remaining'] > 0) {
                        return max(1, $item['sessions_remaining']);
                    }

                    return 100;
                })
                ->default(function () use ($item) {
                    if ($this->selectedItemType === 'trial') {
                        return 1;
                    }

                    if (! $item) {
                        return 4;
                    }

                    if (isset($item['sessions_remaining']) && $item['sessions_remaining'] > 0) {
                        return $item['sessions_remaining'];
                    }

                    return $item['monthly_sessions'] ?? 4;
                })
                ->placeholder('أدخل العدد')
                ->disabled(fn () => $this->selectedItemType === 'trial')
                ->live(),
        ]);
    }

    /**
     * Create bulk schedule using the strategy
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
                ->body('لم يتم العثور على العنصر المحدد')
                ->danger()
                ->send();

            return;
        }

        try {
            $strategy = $this->getStrategy();

            if (! $strategy) {
                throw new Exception('لم يتم تحديد معلم');
            }

            $validator = $strategy->getValidator($this->selectedItemType, $selectedItem);

            // Validate using the validator
            if ($validator) {
                // Validate day selection
                $dayResult = $validator->validateDaySelection($this->scheduleDays);
                if ($dayResult->isError()) {
                    throw new Exception($dayResult->getMessage());
                }

                // Validate session count
                $countResult = $validator->validateSessionCount($this->sessionCount);
                if ($countResult->isError()) {
                    throw new Exception($countResult->getMessage());
                }

                // Validate date range
                $startDate = $this->scheduleStartDate ? Carbon::parse($this->scheduleStartDate) : null;
                $weeksAhead = ceil($this->sessionCount / count($this->scheduleDays));

                $dateResult = $validator->validateDateRange($startDate, $weeksAhead);
                if ($dateResult->isError()) {
                    throw new Exception($dateResult->getMessage());
                }

                // Validate weekly pacing
                $pacingResult = $validator->validateWeeklyPacing($this->scheduleDays, $weeksAhead);
                if ($pacingResult->isError()) {
                    throw new Exception($pacingResult->getMessage());
                }
            }

            // Prepare data for the strategy
            $data = [
                'item_id' => $this->selectedItemId,
                'item_type' => $this->selectedItemType,
                'schedule_days' => $this->scheduleDays,
                'schedule_time' => $this->scheduleTime,
                'schedule_start_date' => $this->scheduleStartDate,
                'session_count' => $this->sessionCount,
            ];

            // Create schedule using the strategy
            $strategy->createSchedule($data, $validator);

            $successMessage = $this->selectedItemType === 'trial'
                ? 'تم جدولة الجلسة التجريبية بنجاح'
                : "تم إنشاء {$this->sessionCount} جلسة بنجاح";

            Notification::make()
                ->title('تم بنجاح')
                ->body($successMessage)
                ->success()
                ->duration(5000)
                ->send();

            // Refresh the page
            $this->js('setTimeout(() => window.location.reload(), 2000)');

        } catch (Exception $e) {
            Notification::make()
                ->title('خطأ')
                ->body('حدث خطأ أثناء إنشاء الجدول: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Get timezone notice for display
     */
    public function getTimezoneNotice(): string
    {
        $timezone = AcademyContextService::getTimezone();
        $label = match ($timezone) {
            'Asia/Riyadh' => 'توقيت السعودية (GMT+3)',
            'Africa/Cairo' => 'توقيت مصر (GMT+2)',
            'Asia/Dubai' => 'توقيت الإمارات (GMT+4)',
            default => $timezone,
        };

        return $label;
    }

    /**
     * Get current time in academy timezone for display
     */
    public function getCurrentTimeDisplay(): string
    {
        $currentTime = nowInAcademyTimezone();
        $formattedTime = formatTimeArabic($currentTime);
        $formattedDate = formatDateArabic($currentTime, 'Y/m/d');

        return "{$formattedDate} - {$formattedTime}";
    }
}
