<?php

namespace App\Filament\Shared\Pages;

use App\Filament\Shared\Traits\FormatsCalendarData;
use App\Filament\Shared\Traits\HandlesScheduling;
use App\Filament\Shared\Traits\ManagesSessionStatistics;
use App\Filament\Shared\Traits\ValidatesConflicts;
use App\Services\AcademyContextService;
use App\Services\Calendar\SessionStrategyFactory;
use App\Services\Calendar\SessionStrategyInterface;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Unified Teacher Calendar Page
 *
 * Single calendar implementation for all teacher types (Quran, Academic)
 * Uses strategy pattern to handle type-specific logic
 *
 * @property \Illuminate\Support\Collection $schedulableItems
 */
class UnifiedTeacherCalendar extends Page
{
    use HandlesScheduling;
    use ManagesSessionStatistics;
    use FormatsCalendarData;
    use ValidatesConflicts;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.shared.pages.unified-teacher-calendar';

    protected static ?string $navigationLabel = 'التقويم';

    protected static ?string $title = 'تقويم المعلم';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'جلساتي';

    // Teacher type and strategy
    public string $teacherType;

    protected ?SessionStrategyInterface $strategy = null;

    protected SessionStrategyFactory $strategyFactory;

    // Selected item properties
    public ?int $selectedItemId = null;

    public ?string $selectedItemType = null;

    public string $activeTab = '';

    // Scheduling form properties
    public array $scheduleDays = [];

    public ?string $scheduleTime = null;

    public ?string $scheduleStartDate = null;

    public int $sessionCount = 4;

    /**
     * Mount the page and detect teacher type
     */
    public function mount(SessionStrategyFactory $strategyFactory): void
    {
        $this->strategyFactory = $strategyFactory;
        $this->teacherType = $this->detectTeacherType();
        $this->strategy = $this->strategyFactory->make($this->teacherType);

        // Set initial active tab based on strategy
        $tabs = $this->strategy->getTabConfiguration();
        $this->activeTab = array_key_first($tabs);

        // Set initial selected item type
        $firstTab = reset($tabs);
        if (isset($firstTab['items_method'])) {
            $this->selectedItemType = $this->getItemTypeFromTabKey($this->activeTab);
        }
    }

    /**
     * Detect the teacher type for the current user
     */
    protected function detectTeacherType(): string
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'يجب تسجيل الدخول للوصول إلى هذه الصفحة');
        }

        // Check if user is a Quran teacher
        if ($user->quranTeacherProfile()->exists() || $user->user_type === 'quran_teacher') {
            return 'quran_teacher';
        }

        // Check if user is an Academic teacher
        if ($user->academicTeacherProfile()->exists() || $user->user_type === 'academic_teacher') {
            return 'academic_teacher';
        }

        abort(403, 'هذه الصفحة متاحة للمعلمين فقط');
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
     * Get the strategy instance
     */
    protected function getStrategy(): SessionStrategyInterface
    {
        if (!$this->strategy) {
            $this->strategy = $this->strategyFactory->make($this->teacherType);
        }
        return $this->strategy;
    }

    /**
     * Get section heading from strategy (for Blade template)
     */
    public function getSectionHeading(): string
    {
        return $this->getStrategy()->getSectionHeading();
    }

    /**
     * Get section description from strategy (for Blade template)
     */
    public function getSectionDescription(): string
    {
        return $this->getStrategy()->getSectionDescription();
    }

    /**
     * Get tabs label from strategy (for Blade template)
     */
    public function getTabsLabel(): string
    {
        return $this->getStrategy()->getTabsLabel();
    }

    /**
     * Get tab configuration from strategy (for Blade template)
     */
    public function getTabConfiguration(): array
    {
        return $this->getStrategy()->getTabConfiguration();
    }

    /**
     * Check if user can access this page
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Allow access for both Quran and Academic teachers
        return $user->quranTeacherProfile()->exists()
            || $user->academicTeacherProfile()->exists()
            || $user->user_type === 'quran_teacher'
            || $user->user_type === 'academic_teacher';
    }

    /**
     * Get footer widgets for the calendar
     */
    protected function getFooterWidgets(): array
    {
        return $this->getStrategy()->getFooterWidgets();
    }

    /**
     * Get schedulable items for the active tab
     */
    public function getSchedulableItemsProperty()
    {
        $strategy = $this->getStrategy();
        $tabConfig = $strategy->getTabConfiguration();
        $currentTab = $tabConfig[$this->activeTab] ?? null;

        if (!$currentTab || !isset($currentTab['items_method'])) {
            return collect();
        }

        $methodName = $currentTab['items_method'];

        // Call the strategy method to get items
        if (method_exists($strategy, $methodName)) {
            return $strategy->$methodName();
        }

        return collect();
    }

    /**
     * Set active tab
     */
    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->selectedItemId = null;
        $this->selectedItemType = $this->getItemTypeFromTabKey($tab);
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
        if (!$this->selectedItemId || !$this->selectedItemType) {
            return null;
        }

        $items = $this->schedulableItems;
        return $items->firstWhere('id', $this->selectedItemId);
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
                return 'جدولة جلسات - ' . ($item['name'] ?? '');
            })
            ->modalDescription('اختر أيام الأسبوع ووقت الجلسات لإنشاء جدول تلقائي')
            ->modalSubmitActionLabel('إنشاء الجدول')
            ->modalCancelActionLabel('إلغاء')
            ->form([$this->buildScheduleForm()])
            ->action(function (array $data) {
                $this->scheduleDays = $data['schedule_days'] ?? [];
                $this->scheduleTime = $data['schedule_time'] ?? null;
                $this->scheduleStartDate = $data['schedule_start_date'] ?? null;
                $this->sessionCount = $data['session_count'] ?? 4;

                $this->createBulkSchedule();
            })
            ->visible(fn () => $this->selectedItemId !== null);
    }

    /**
     * Build the unified schedule form
     */
    protected function buildScheduleForm(): Forms\Components\Group
    {
        $item = $this->getSelectedItem();
        $validator = $item ? $this->getStrategy()->getValidator($this->selectedItemType, $item) : null;

        return Forms\Components\Group::make([
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
                ->helperText(function () use ($validator) {
                    if (!$validator) {
                        return '';
                    }
                    $recommendations = $validator->getRecommendations();
                    return "💡 {$recommendations['reason']}";
                })
                ->reactive(),

            Forms\Components\DatePicker::make('schedule_start_date')
                ->label('تاريخ بداية الجدولة')
                ->helperText(function () use ($item) {
                    if ($item && isset($item['start_date']) && $item['start_date']) {
                        return 'تاريخ بداية الدورة: ' . $item['start_date'];
                    }
                    return 'تاريخ البداية لجدولة الجلسات الجديدة';
                })
                ->default(function () use ($item) {
                    // For interactive courses, default to the course's start_date
                    if ($item && isset($item['type']) && $item['type'] === 'interactive_course') {
                        if (isset($item['start_date']) && $item['start_date']) {
                            // Parse the date from 'Y/m/d' format to Carbon
                            try {
                                $startDate = \Carbon\Carbon::parse(str_replace('/', '-', $item['start_date']));
                                // Only use if the date is in the future or today
                                if ($startDate->gte(now()->startOfDay())) {
                                    return $startDate->format('Y-m-d');
                                }
                            } catch (\Exception $e) {
                                // Fall back to null if parsing fails
                            }
                        }
                    }
                    return null;
                })
                ->minDate(now()->format('Y-m-d'))
                ->maxDate(function () use ($validator) {
                    if (!$validator) {
                        return null;
                    }
                    // Check if validator has getMaxScheduleDate method
                    if (method_exists($validator, 'getMaxScheduleDate')) {
                        return $validator->getMaxScheduleDate()?->format('Y-m-d');
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
                        $hour12 = $hour > 12 ? $hour - 12 : ($hour == 0 ? 12 : $hour);
                        $period = $hour >= 12 ? 'م' : 'ص';
                        $display = sprintf('%02d:00', $hour) . ' (' . $hour12 . ' ' . $period . ')';
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
                ->helperText(function () use ($item) {
                    if (!$item) {
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
                    if (!$item) {
                        return 100;
                    }

                    // Check if item has sessions_remaining
                    if (isset($item['sessions_remaining']) && $item['sessions_remaining'] > 0) {
                        return max(1, $item['sessions_remaining']);
                    }

                    return 100;
                })
                ->default(function () use ($item) {
                    if (!$item) {
                        return 4;
                    }

                    // FIXED: Always default to maximum available sessions (not capped at 8)
                    if (isset($item['sessions_remaining']) && $item['sessions_remaining'] > 0) {
                        return $item['sessions_remaining'];
                    }

                    return $item['monthly_sessions'] ?? 4;
                })
                ->placeholder('أدخل العدد')
                ->reactive(),
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

        if (!$selectedItem) {
            Notification::make()
                ->title('خطأ')
                ->body('لم يتم العثور على العنصر المحدد')
                ->danger()
                ->send();
            return;
        }

        try {
            // Get validator for the selected item
            $strategy = $this->getStrategy();
            $validator = $strategy->getValidator($this->selectedItemType, $selectedItem);

            // Validate using the validator
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

            Notification::make()
                ->title('تم بنجاح')
                ->body("تم إنشاء {$this->sessionCount} جلسة بنجاح")
                ->success()
                ->duration(5000)
                ->send();

            // Refresh the page
            $this->js('setTimeout(() => window.location.reload(), 2000)');

        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ')
                ->body('حدث خطأ أثناء إنشاء الجدول: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
