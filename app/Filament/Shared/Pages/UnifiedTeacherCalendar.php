<?php

namespace App\Filament\Shared\Pages;

use Filament\Schemas\Components\Group;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Exception;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use App\Enums\UserType;
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
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

/**
 * Unified Teacher Calendar Page
 *
 * Single calendar implementation for all teacher types (Quran, Academic)
 * Uses strategy pattern to handle type-specific logic
 *
 * @property Collection $schedulableItems
 */
class UnifiedTeacherCalendar extends Page
{
    use FormatsCalendarData;
    use HandlesScheduling;
    use ManagesSessionStatistics;
    use ValidatesConflicts;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected string $view = 'filament.shared.pages.unified-teacher-calendar';

    protected static ?string $navigationLabel = 'التقويم';

    protected static ?string $title = 'تقويم المعلم';

    protected static ?int $navigationSort = 2;

    protected static string | \UnitEnum | null $navigationGroup = 'جلساتي';

    // Teacher type and strategy
    public string $teacherType = '';

    protected ?SessionStrategyInterface $strategy = null;

    protected ?SessionStrategyFactory $strategyFactory = null;

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

        if (! $user) {
            abort(403, 'يجب تسجيل الدخول للوصول إلى هذه الصفحة');
        }

        // Check if user is a Quran teacher
        if ($user->quranTeacherProfile()->exists() || $user->user_type === UserType::QURAN_TEACHER->value) {
            return 'quran_teacher';
        }

        // Check if user is an Academic teacher
        if ($user->academicTeacherProfile()->exists() || $user->user_type === UserType::ACADEMIC_TEACHER->value) {
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
     * Get the strategy factory instance
     */
    protected function getStrategyFactory(): SessionStrategyFactory
    {
        if (! $this->strategyFactory) {
            $this->strategyFactory = app(SessionStrategyFactory::class);
        }

        return $this->strategyFactory;
    }

    /**
     * Get the strategy instance
     */
    protected function getStrategy(): SessionStrategyInterface
    {
        if (! $this->strategy) {
            if (empty($this->teacherType)) {
                $this->teacherType = $this->detectTeacherType();
            }
            $this->strategy = $this->getStrategyFactory()->make($this->teacherType);
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
        if (! $user) {
            return false;
        }

        // Allow access for both Quran and Academic teachers
        return $user->quranTeacherProfile()->exists()
            || $user->academicTeacherProfile()->exists()
            || $user->user_type === UserType::QURAN_TEACHER->value
            || $user->user_type === UserType::ACADEMIC_TEACHER->value;
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

        if (! $currentTab || ! isset($currentTab['items_method'])) {
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
        if (! $this->selectedItemId || ! $this->selectedItemType) {
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
        $validator = $item ? $this->getStrategy()->getValidator($this->selectedItemType, $item) : null;

        return Group::make([
            Placeholder::make('subscription_info')
                ->label(__('scheduling.info.subscription_info'))
                ->content(function () use ($item) {
                    if (! $item) {
                        return '';
                    }

                    $lines = [];

                    // Subscription/course dates
                    if (isset($item['subscription_start']) && $item['subscription_start']) {
                        $start = $item['subscription_start'] instanceof Carbon
                            ? $item['subscription_start']->format('Y/m/d')
                            : Carbon::parse($item['subscription_start'])->format('Y/m/d');
                        $lines[] = __('scheduling.info.subscription_start').': '.$start;
                    }
                    if (isset($item['subscription_end']) && $item['subscription_end']) {
                        $end = $item['subscription_end'] instanceof Carbon
                            ? $item['subscription_end']->format('Y/m/d')
                            : Carbon::parse($item['subscription_end'])->format('Y/m/d');
                        $lines[] = __('scheduling.info.subscription_end').': '.$end;
                    }
                    if (isset($item['start_date']) && $item['start_date']) {
                        $lines[] = __('scheduling.info.subscription_start').': '.$item['start_date'];
                    }
                    if (isset($item['end_date']) && $item['end_date']) {
                        $lines[] = __('scheduling.info.subscription_end').': '.$item['end_date'];
                    }

                    // Session counts
                    $total = $item['sessions_count'] ?? $item['total_sessions'] ?? null;
                    $scheduled = $item['sessions_scheduled'] ?? null;
                    $remaining = $item['sessions_remaining'] ?? null;

                    if ($total !== null) {
                        $lines[] = __('scheduling.info.total_sessions').': '.$total;
                    }
                    if ($scheduled !== null) {
                        $lines[] = __('scheduling.info.scheduled_sessions').': '.$scheduled;
                    }
                    if ($remaining !== null) {
                        $lines[] = __('scheduling.info.remaining_sessions').': '.$remaining;
                    }

                    if (empty($lines)) {
                        return '';
                    }

                    return new HtmlString(
                        '<div class="text-sm space-y-1 p-3 bg-primary-50 dark:bg-primary-950 rounded-lg border border-primary-200 dark:border-primary-800">'
                        .implode('<br>', array_map(fn ($l) => e($l), $lines))
                        .'</div>'
                    );
                })
                ->visible(fn () => $item !== null),

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
                            // Parse the date from 'Y/m/d' format to Carbon
                            try {
                                $startDate = Carbon::parse(str_replace('/', '-', $item['start_date']));
                                // Only use if the date is in the future or today
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
                ->minDate(now()->format('Y-m-d'))
                ->maxDate(function () use ($validator) {
                    if (! $validator) {
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
                ->live(),

            TimePicker::make('schedule_time')
                ->label('وقت الجلسة')
                ->required()
                ->seconds(false)
                ->minutesStep(15)
                ->native(false)
                ->helperText(function () {
                    $timezone = AcademyContextService::getTimezone();
                    $currentTime = Carbon::now($timezone)->format('H:i');

                    return "الوقت الذي ستبدأ فيه الجلسات (التوقيت المحلي - الوقت الحالي: {$currentTime})";
                }),

            TextInput::make('session_count')
                ->label('عدد الجلسات المطلوب إنشاؤها')
                ->helperText(function () use ($item) {
                    // Trial sessions always have exactly 1 session
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
                    // Trial sessions always have exactly 1 session
                    if ($this->selectedItemType === 'trial') {
                        return 1;
                    }

                    if (! $item) {
                        return 100;
                    }

                    // Check if item has sessions_remaining
                    if (isset($item['sessions_remaining']) && $item['sessions_remaining'] > 0) {
                        return max(1, $item['sessions_remaining']);
                    }

                    return 100;
                })
                ->default(function () use ($item) {
                    // Trial sessions always have exactly 1 session
                    if ($this->selectedItemType === 'trial') {
                        return 1;
                    }

                    if (! $item) {
                        return 4;
                    }

                    // FIXED: Always default to maximum available sessions (not capped at 8)
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
            // Get validator for the selected item
            $strategy = $this->getStrategy();
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
