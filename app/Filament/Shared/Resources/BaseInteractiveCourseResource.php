<?php

namespace App\Filament\Shared\Resources;

use App\Enums\DifficultyLevel;
use App\Enums\InteractiveCourseStatus;
use App\Enums\SessionDuration;
use App\Enums\WeekDays;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Services\AcademyContextService;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Interactive Course Resource
 *
 * Shared functionality for SuperAdmin and AcademicTeacher panels.
 * Child classes must implement query scoping and authorization methods.
 */
abstract class BaseInteractiveCourseResource extends Resource
{
    protected static ?string $model = InteractiveCourse::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $modelLabel = 'دورة تفاعلية';

    protected static ?string $pluralModelLabel = 'الدورات التفاعلية';

    // ========================================
    // Abstract Methods - Panel-specific implementation
    // ========================================

    /**
     * Apply panel-specific query scoping.
     */
    abstract protected static function scopeEloquentQuery(Builder $query): Builder;

    /**
     * Get panel-specific table actions.
     */
    abstract protected static function getTableActions(): array;

    /**
     * Get panel-specific bulk actions.
     */
    abstract protected static function getTableBulkActions(): array;

    /**
     * Get the form schema for this panel.
     */
    abstract protected static function getFormSchema(): array;

    // ========================================
    // Authorization - Override in child classes
    // ========================================

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    // ========================================
    // Shared Form Definition
    // ========================================

    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::getFormSchema());
    }

    /**
     * Basic course info section - shared across panels.
     */
    protected static function getBasicInfoFormSection(): Section
    {
        return Section::make('معلومات الدورة الأساسية')
            ->schema([
                TextInput::make('title')
                    ->label('عنوان الدورة')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('مثل: رياضيات متقدمة - الفصل الأول')
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('وصف الدورة')
                    ->required()
                    ->maxLength(1000)
                    ->rows(4)
                    ->columnSpanFull(),

                FileUpload::make('featured_image')
                    ->label('صورة الكورس')
                    ->helperText('صورة مميزة تظهر في بطاقة الكورس وصفحة التفاصيل')
                    ->image()
                    ->directory('interactive-courses/featured')
                    ->visibility('public')
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('16:9')
                    ->imageResizeTargetWidth('800')
                    ->imageResizeTargetHeight('450')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Specialization section - shared across panels.
     */
    protected static function getSpecializationFormSection(): Section
    {
        return Section::make('التخصص والمستوى')
            ->schema([
                Grid::make(3)
                    ->schema([
                        Select::make('subject_id')
                            ->label('المادة الدراسية')
                            ->options(function () {
                                $academyId = AcademyContextService::getCurrentAcademyId();

                                return $academyId ? AcademicSubject::where('academy_id', $academyId)->where('is_active', true)->pluck('name', 'id') : [];
                            })
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('grade_level_id')
                            ->label('المرحلة الدراسية')
                            ->options(function () {
                                $academyId = AcademyContextService::getCurrentAcademyId();

                                return $academyId ? AcademicGradeLevel::where('academy_id', $academyId)
                                    ->whereNotNull('name')
                                    ->where('name', '!=', '')
                                    ->pluck('name', 'id') : [];
                            })
                            ->required()
                            ->searchable(),

                        Select::make('assigned_teacher_id')
                            ->label('المعلم المعين')
                            ->options(function () {
                                $academyId = AcademyContextService::getCurrentAcademyId();

                                return $academyId ? AcademicTeacherProfile::forAcademy($academyId)
                                    ->active()
                                    ->with('user')
                                    ->get()
                                    ->mapWithKeys(function ($teacher) {
                                        $displayName = $teacher->user ? $teacher->user->name : $teacher->full_name;
                                        $qualification = $teacher->education_level?->label() ?? 'غير محدد';

                                        return [$teacher->id => $displayName.' ('.$qualification.')'];
                                    }) : [];
                            })
                            ->required()
                            ->searchable(),
                    ]),
            ]);
    }

    /**
     * Course settings section - shared across panels.
     */
    protected static function getCourseSettingsFormSection(): Section
    {
        return Section::make('إعدادات الدورة')
            ->schema([
                Grid::make(4)
                    ->schema([
                        TextInput::make('total_sessions')
                            ->label('إجمالي الجلسات')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(200)
                            ->default(16)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $set('duration_weeks', null);
                            })
                            ->suffix('جلسة'),

                        TextInput::make('sessions_per_week')
                            ->label('الجلسات أسبوعياً')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(7)
                            ->default(2)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $set('duration_weeks', null);
                            })
                            ->suffix('جلسة'),

                        TextInput::make('duration_weeks')
                            ->label('مدة الدورة (أسابيع)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->live()
                            ->placeholder(function (Get $get) {
                                $totalSessions = $get('total_sessions') ?? 0;
                                $sessionsPerWeek = $get('sessions_per_week') ?? 0;

                                if ($totalSessions > 0 && $sessionsPerWeek > 0) {
                                    $durationWeeks = ceil($totalSessions / $sessionsPerWeek);

                                    return (string) $durationWeeks;
                                }

                                return '0';
                            }),

                        Select::make('session_duration_minutes')
                            ->label('مدة الجلسة (دقيقة)')
                            ->options(SessionDuration::options())
                            ->default(SessionDuration::SIXTY_MINUTES->value)
                            ->required(),
                    ]),

                Grid::make(2)
                    ->schema([
                        TextInput::make('max_students')
                            ->label('أقصى عدد طلاب')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50)
                            ->default(20)
                            ->required()
                            ->suffix('طالب'),

                        Select::make('difficulty_level')
                            ->label('مستوى الصعوبة')
                            ->options(DifficultyLevel::options())
                            ->default(DifficultyLevel::BEGINNER->value)
                            ->required(),
                    ]),
            ]);
    }

    /**
     * Financial settings section - shared across panels.
     */
    protected static function getFinancialSettingsFormSection(): Section
    {
        return Section::make('الإعدادات المالية')
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('student_price')
                            ->label('سعر الدورة للطالب')
                            ->numeric()
                            ->minValue(0)
                            ->default(500)
                            ->required()
                            ->prefix(getCurrencyCode()),

                        TextInput::make('sale_price')
                            ->label('سعر التخفيض')
                            ->numeric()
                            ->minValue(0)
                            ->prefix(getCurrencyCode())
                            ->helperText('اتركه فارغاً إذا لا يوجد تخفيض'),

                        TextInput::make('teacher_payment')
                            ->label('دفع المعلم')
                            ->numeric()
                            ->minValue(0)
                            ->default(2000)
                            ->required()
                            ->prefix(getCurrencyCode()),

                        Select::make('payment_type')
                            ->label('نوع دفع المعلم')
                            ->options([
                                'fixed_amount' => 'مبلغ ثابت',
                                'per_student' => 'لكل طالب',
                                'per_session' => 'لكل جلسة',
                            ])
                            ->default('fixed_amount')
                            ->required(),
                    ]),
            ]);
    }

    /**
     * Dates and scheduling section - shared across panels.
     */
    protected static function getDatesSchedulingFormSection(): Section
    {
        return Section::make('التواريخ والجدولة')
            ->schema([
                Grid::make(3)
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('تاريخ البداية')
                            ->required()
                            ->minDate(now()->addDays(7))
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $set('end_date', null);
                            }),

                        TextInput::make('end_date')
                            ->label('تاريخ النهاية')
                            ->disabled()
                            ->dehydrated(false)
                            ->live()
                            ->formatStateUsing(function ($state, $record) {
                                if ($state && $record && $record->end_date) {
                                    return $record->end_date->format('d/m/Y');
                                }

                                if ($state && is_string($state)) {
                                    try {
                                        return Carbon::parse($state)->format('d/m/Y');
                                    } catch (Exception $e) {
                                        return $state;
                                    }
                                }

                                return null;
                            })
                            ->placeholder(function (Get $get) {
                                $startDate = $get('start_date');
                                $totalSessions = $get('total_sessions') ?? 0;
                                $sessionsPerWeek = $get('sessions_per_week') ?? 0;

                                if (! $startDate || $sessionsPerWeek === 0) {
                                    return 'تاريخ غير محدد';
                                }

                                $durationWeeks = ceil($totalSessions / $sessionsPerWeek);
                                $endDate = date('d/m/Y', strtotime($startDate.' +'.($durationWeeks * 7).' days'));

                                return $endDate;
                            }),

                        DatePicker::make('enrollment_deadline')
                            ->label('آخر موعد للتسجيل')
                            ->helperText('اتركه فارغاً للسماح بالتسجيل طوال فترة الدورة')
                            ->before('start_date')
                            ->maxDate(function (Get $get) {
                                $startDate = $get('start_date');

                                return $startDate ? date('Y-m-d', strtotime($startDate.' -3 days')) : null;
                            }),
                    ]),

                Repeater::make('schedule')
                    ->label('الجدول الأسبوعي')
                    ->schema([
                        Grid::make(4)->schema([
                            Select::make('day')
                                ->label('اليوم')
                                ->options(WeekDays::options())
                                ->required(),
                            Select::make('hour')
                                ->label('الساعة')
                                ->options(array_combine(range(1, 12), range(1, 12)))
                                ->required(),
                            Select::make('minute')
                                ->label('الدقيقة')
                                ->options(['00' => '00', '15' => '15', '30' => '30', '45' => '45'])
                                ->required(),
                            Select::make('period')
                                ->label('الفترة')
                                ->options(['am' => __('common.am'), 'pm' => __('common.pm')])
                                ->required(),
                        ]),
                    ])
                    ->default([['day' => 'sunday', 'hour' => 4, 'minute' => '00', 'period' => 'pm']])
                    ->addActionLabel('إضافة يوم')
                    ->afterStateHydrated(function ($component, $state) {
                        $component->state(static::hydrateScheduleForRepeater($state));
                    })
                    ->dehydrateStateUsing(function ($state) {
                        return static::dehydrateScheduleFromRepeater($state);
                    })
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Content and objectives section - shared across panels.
     */
    protected static function getContentObjectivesFormSection(): Section
    {
        return Section::make('محتوى الدورة والأهداف')
            ->schema([
                Repeater::make('learning_outcomes')
                    ->label('مخرجات التعلم')
                    ->schema([
                        TextInput::make('outcome')
                            ->label('المخرج')
                            ->required(),
                    ])
                    ->addActionLabel('إضافة مخرج')
                    ->collapsible()
                    ->columnSpanFull(),

                Repeater::make('prerequisites')
                    ->label('المتطلبات المسبقة')
                    ->schema([
                        TextInput::make('prerequisite')
                            ->label('المتطلب')
                            ->required(),
                    ])
                    ->addActionLabel('إضافة متطلب')
                    ->collapsible()
                    ->columnSpanFull(),

                Textarea::make('course_outline')
                    ->label('مخطط الدورة')
                    ->rows(5)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Status settings section - shared across panels.
     */
    protected static function getStatusSettingsFormSection(): Section
    {
        return Section::make('حالة الدورة والإعدادات')
            ->schema([
                Toggle::make('is_published')
                    ->label('مفعل للنشر')
                    ->default(false)
                    ->helperText('هل يمكن للطلاب رؤية هذه الدورة والتسجيل فيها؟'),

                Select::make('status')
                    ->label('حالة الدورة')
                    ->options(InteractiveCourseStatus::options())
                    ->default(InteractiveCourseStatus::PUBLISHED->value)
                    ->required()
                    ->helperText('حالة الدورة الحالية'),

                Toggle::make('recording_enabled')
                    ->label('تسجيل جلسات الدورة')
                    ->default(true)
                    ->helperText('تفعيل تسجيل جميع جلسات هذه الدورة'),
            ])->columns(3);
    }

    // ========================================
    // Shared Table Definition
    // ========================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->defaultSort('created_at', 'desc')
            ->filters(static::getTableFilters())
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordActions(static::getTableActions())
            ->toolbarActions(static::getTableBulkActions());
    }

    /**
     * Get the table columns - shared across panels.
     */
    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('course_code')
                ->label('رمز الدورة')
                ->searchable()
                ->sortable()
                ->copyable(),

            TextColumn::make('title')
                ->label('العنوان')
                ->searchable()
                ->limit(30)
                ->tooltip(function (TextColumn $column): ?string {
                    $state = $column->getState();

                    return strlen($state) > 30 ? $state : null;
                }),

            TextColumn::make('subject.name')
                ->label('المادة')
                ->badge()
                ->color('info')
                ->toggleable(),

            TextColumn::make('gradeLevel.name')
                ->label('المرحلة')
                ->badge()
                ->color('success')
                ->toggleable(),

            TextColumn::make('assignedTeacher.user.name')
                ->label('المعلم')
                ->searchable()
                ->getStateUsing(function ($record) {
                    if (! $record->assignedTeacher) {
                        return 'غير معين';
                    }

                    return $record->assignedTeacher->user
                        ? $record->assignedTeacher->user->name
                        : $record->assignedTeacher->full_name;
                })
                ->toggleable(),

            TextColumn::make('enrollments_count')
                ->label('المسجلين')
                ->counts('enrollments')
                ->suffix(function ($record) {
                    return ' / '.$record->max_students;
                })
                ->color(function ($record) {
                    $percentage = ($record->enrollments_count / $record->max_students) * 100;

                    return $percentage >= 80 ? 'danger' : ($percentage >= 60 ? 'warning' : 'success');
                })
                ->toggleable(),

            TextColumn::make('student_price')
                ->label('السعر')
                ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR'))
                ->sortable()
                ->toggleable(),

            TextColumn::make('status')
                ->badge()
                ->label('الحالة')
                ->colors(InteractiveCourseStatus::colorOptions())
                ->formatStateUsing(function ($state): string {
                    if ($state instanceof InteractiveCourseStatus) {
                        return $state->label();
                    }
                    $statusEnum = InteractiveCourseStatus::tryFrom($state);

                    return $statusEnum?->label() ?? $state;
                }),

            TextColumn::make('start_date')
                ->label('تاريخ البداية')
                ->date('Y-m-d')
                ->sortable()
                ->toggleable(),

            IconColumn::make('is_published')
                ->label('منشور')
                ->boolean()
                ->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-x-circle')
                ->trueColor('success')
                ->falseColor('danger')
                ->toggleable(),

            TextColumn::make('created_at')
                ->label(__('filament.created_at'))
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * Get the table filters - shared across panels.
     */
    protected static function getTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label('الحالة')
                ->options(InteractiveCourseStatus::options())
                ->searchable(),

            SelectFilter::make('subject_id')
                ->label('المادة')
                ->relationship('subject', 'name')
                ->searchable()
                ->preload(),

            SelectFilter::make('grade_level_id')
                ->label('المرحلة')
                ->relationship('gradeLevel', 'name')
                ->searchable()
                ->preload(),

            TernaryFilter::make('is_published')
                ->label('منشور')
                ->placeholder('الكل')
                ->trueLabel('منشور')
                ->falseLabel('غير منشور'),
        ];
    }

    // ========================================
    // Schedule Hydration / Dehydration
    // ========================================

    /**
     * Convert any stored schedule format into repeater-compatible entries.
     * Returns: [['day' => 'sunday', 'hour' => 4, 'minute' => '00', 'period' => 'pm'], ...]
     */
    public static function hydrateScheduleForRepeater(mixed $state): array
    {
        if (! is_array($state) || empty($state)) {
            return [['day' => 'sunday', 'hour' => 4, 'minute' => '00', 'period' => 'pm']];
        }

        $arabicToDayValue = collect(WeekDays::cases())
            ->mapWithKeys(fn (WeekDays $d) => [mb_strtolower($d->label()) => $d->value])
            ->toArray();

        $entries = [];

        // Array-of-objects: [{"day":"sunday","time":"16:00"}]
        if (array_is_list($state) && isset($state[0]['day'])) {
            foreach ($state as $item) {
                $dayValue = strtolower($item['day'] ?? '');
                // Validate it's a known WeekDays value
                if (! WeekDays::tryFrom($dayValue)) {
                    $dayValue = $arabicToDayValue[$dayValue] ?? $dayValue;
                }
                $time = $item['time'] ?? '16:00';
                $entries[] = array_merge(['day' => $dayValue], self::parseTimeTo12h($time));
            }

            return $entries ?: [['day' => 'sunday', 'hour' => 4, 'minute' => '00', 'period' => 'pm']];
        }

        // Key-value: {"الأحد": "16:00 - 17:00"} or {"monday": "10:00"}
        foreach ($state as $key => $value) {
            $lowerKey = strtolower($key);
            $dayValue = WeekDays::tryFrom($lowerKey)?->value ?? ($arabicToDayValue[mb_strtolower($key)] ?? $lowerKey);
            $time = is_string($value) ? $value : '16:00';
            // For range format, use start time only
            if (str_contains($time, ' - ')) {
                $time = trim(explode(' - ', $time)[0]);
            }
            $entries[] = array_merge(['day' => $dayValue], self::parseTimeTo12h($time));
        }

        return $entries ?: [['day' => 'sunday', 'hour' => 4, 'minute' => '00', 'period' => 'pm']];
    }

    /**
     * Convert repeater entries back to storage format: [{"day":"sunday","time":"16:00"}]
     */
    public static function dehydrateScheduleFromRepeater(mixed $state): array
    {
        if (! is_array($state) || empty($state)) {
            return [];
        }

        $result = [];
        foreach ($state as $entry) {
            if (empty($entry['day'])) {
                continue;
            }
            $hour = (int) ($entry['hour'] ?? 12);
            $minute = $entry['minute'] ?? '00';
            $period = $entry['period'] ?? 'pm';

            // Convert 12h to 24h
            if ($period === 'am' && $hour === 12) {
                $hour24 = 0;
            } elseif ($period === 'pm' && $hour !== 12) {
                $hour24 = $hour + 12;
            } else {
                $hour24 = $hour;
            }

            $result[] = [
                'day' => $entry['day'],
                'time' => sprintf('%02d:%s', $hour24, $minute),
            ];
        }

        return $result;
    }

    /**
     * Parse a 24h time string into [hour, minute, period] for the repeater.
     */
    private static function parseTimeTo12h(string $time): array
    {
        $time = trim($time);
        if (! preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
            return ['hour' => 4, 'minute' => '00', 'period' => 'pm'];
        }
        $h = (int) $matches[1];
        $m = $matches[2];
        // Snap minute to nearest valid option
        $validMinutes = ['00', '15', '30', '45'];
        if (! in_array($m, $validMinutes)) {
            $mInt = (int) $m;
            $m = $validMinutes[0];
            foreach ($validMinutes as $vm) {
                if (abs($mInt - (int) $vm) < abs($mInt - (int) $m)) {
                    $m = $vm;
                }
            }
        }
        $period = $h >= 12 ? 'pm' : 'am';
        $h12 = $h % 12 ?: 12;

        return ['hour' => $h12, 'minute' => $m, 'period' => $period];
    }

    // ========================================
    // Schedule Normalization (Legacy)
    // ========================================

    /**
     * Normalize schedule state from various stored formats into a localized key-value map.
     *
     * Handles: array-of-objects [{"day":"monday","time":"10:00"}],
     * key-value with English keys {"monday":"10:00"}, and already-Arabic keys.
     */
    public static function normalizeScheduleState(mixed $state): array
    {
        if (! is_array($state) || empty($state)) {
            return $state ?? [];
        }

        $dayTranslations = collect(WeekDays::cases())
            ->mapWithKeys(fn (WeekDays $day) => [strtolower($day->value) => $day->label()])
            ->toArray();

        // Array-of-objects format: [{"day":"monday","time":"10:00"}, ...]
        if (array_is_list($state) && isset($state[0]['day'])) {
            $normalized = [];
            foreach ($state as $item) {
                $dayKey = strtolower($item['day'] ?? '');
                $label = $dayTranslations[$dayKey] ?? $item['day'];
                $time = $item['time'] ?? $item['start_time'] ?? '';
                if (isset($item['end_time'])) {
                    $time .= ' - '.$item['end_time'];
                }
                $normalized[$label] = $time;
            }

            return $normalized;
        }

        // Key-value format with potentially English keys: {"monday":"10:00"}
        $normalized = [];
        foreach ($state as $key => $value) {
            $lowerKey = strtolower($key);
            $normalized[$dayTranslations[$lowerKey] ?? $key] = $value;
        }

        return $normalized;
    }

    // ========================================
    // Eloquent Query
    // ========================================

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'academy',
                'subject',
                'gradeLevel',
                'assignedTeacher.user',
            ])
            ->withCount('enrollments');

        return static::scopeEloquentQuery($query);
    }

    public static function getRelations(): array
    {
        return [];
    }
}
