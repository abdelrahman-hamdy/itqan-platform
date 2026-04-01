<?php

namespace App\Filament\Shared\Resources;

use App\Enums\SessionSubscriptionStatus;
use App\Models\QuranIndividualCircle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * Base Quran Individual Circle Resource
 *
 * Shared functionality for SuperAdmin and Teacher panels.
 * Child classes must implement query scoping and authorization methods.
 */
abstract class BaseQuranIndividualCircleResource extends Resource
{
    protected static ?string $model = QuranIndividualCircle::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user';

    protected static ?string $modelLabel = 'حلقة فردية';

    protected static ?string $pluralModelLabel = 'الحلقات الفردية';

    protected static ?string $recordTitleAttribute = 'name';

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
     * Get the basic info form section (teacher/student selection differs by panel).
     */
    abstract protected static function getBasicInfoFormSection(): Section;

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

    public static function form(Schema $form): Schema
    {
        $schema = [];

        // Add subscription warning section (visible only when subscription is suspended)
        $schema[] = static::getSubscriptionWarningSection();

        // Add basic info section (panel-specific)
        $schema[] = static::getBasicInfoFormSection()
            ->hidden(fn ($record) => $record && static::isSubscriptionSuspended($record));

        // Add academic progress section (shared)
        $schema[] = static::getAcademicProgressFormSection()
            ->hidden(fn ($record) => $record && static::isSubscriptionSuspended($record));

        // Add subscription info section (shared, view/edit only)
        $schema[] = static::getSubscriptionInfoFormSection()
            ->hidden(fn ($record) => $record && static::isSubscriptionSuspended($record));

        // Add progress tracking section (shared)
        $schema[] = static::getProgressTrackingFormSection()
            ->hidden(fn ($record) => $record && static::isSubscriptionSuspended($record));

        // Recording settings (admin only)
        $schema[] = Section::make(__('recordings.recording_settings'))
            ->schema([
                Toggle::make('recording_enabled')
                    ->label(__('recordings.enable_audio_recording'))
                    ->helperText(__('recordings.enable_audio_recording_help'))
                    ->default(false)
                    ->live(),
                Toggle::make('show_recording_to_teacher')
                    ->label(__('recordings.show_to_teacher'))
                    ->default(true)
                    ->visible(fn ($get) => $get('recording_enabled')),
                Toggle::make('show_recording_to_student')
                    ->label(__('recordings.show_to_student'))
                    ->default(false)
                    ->visible(fn ($get) => $get('recording_enabled')),
            ])
            ->collapsed()
            ->hidden(fn ($record) => $record && static::isSubscriptionSuspended($record));

        // Add additional sections from child classes
        foreach (static::getAdditionalFormSections() as $section) {
            $schema[] = $section instanceof Section
                ? $section->hidden(fn ($record) => $record && static::isSubscriptionSuspended($record))
                : $section;
        }

        return $form->components($schema);
    }

    /**
     * Academic progress section - shared across panels.
     */
    protected static function getAcademicProgressFormSection(): Section
    {
        return Section::make('التقدم الأكاديمي')
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('specialization')
                            ->label('التخصص')
                            ->options(QuranIndividualCircle::SPECIALIZATIONS)
                            ->default('memorization')
                            ->required(),

                        Select::make('memorization_level')
                            ->label('مستوى الحفظ')
                            ->options(QuranIndividualCircle::MEMORIZATION_LEVELS)
                            ->default('beginner')
                            ->required(),
                    ]),
            ]);
    }

    /**
     * Progress tracking section - shared across panels (read-only).
     */
    protected static function getProgressTrackingFormSection(): Section
    {
        return Section::make('تتبع التقدم')
            ->description('يتم حسابها تلقائياً من واجبات الجلسات')
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('total_memorized_pages')
                            ->label('إجمالي الصفحات المحفوظة')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('يتم تحديثه من واجبات الحفظ الجديد'),

                        TextInput::make('total_reviewed_pages')
                            ->label('إجمالي الصفحات المراجعة')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('يتم تحديثه من واجبات المراجعة'),

                        TextInput::make('total_reviewed_surahs')
                            ->label('إجمالي السور المراجعة')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('يتم تحديثه من واجبات المراجعة الشاملة'),
                    ]),
            ])
            ->collapsible()
            ->collapsed();
    }

    /**
     * Subscription info section - read-only, shows linked subscription data.
     */
    protected static function getSubscriptionInfoFormSection(): Section
    {
        return Section::make('معلومات الاشتراك')
            ->icon('heroicon-o-credit-card')
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('subscription_package')
                            ->label('الباقة')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (TextInput $component, $record) {
                                $sub = $record?->activeSubscription ?? $record?->subscription;
                                $name = $sub?->package_name_ar ?: $sub?->package_name_en ?: $sub?->package?->name;
                                $component->state($name ?: '-');
                            }),

                        TextInput::make('subscription_status_display')
                            ->label('حالة الاشتراك')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (TextInput $component, $record) {
                                $sub = $record?->activeSubscription ?? $record?->subscription;
                                if (! $sub) {
                                    $component->state('-');

                                    return;
                                }
                                $component->state(
                                    $sub->status instanceof SessionSubscriptionStatus
                                        ? $sub->status->label()
                                        : ($sub->status ?? '-')
                                );
                            }),

                        TextInput::make('subscription_billing_cycle')
                            ->label('دورة الفوترة')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (TextInput $component, $record) {
                                $sub = $record?->activeSubscription ?? $record?->subscription;
                                if (! $sub) {
                                    $component->state('-');

                                    return;
                                }
                                $component->state(
                                    $sub->billing_cycle instanceof \App\Enums\BillingCycle
                                        ? $sub->billing_cycle->label()
                                        : ($sub->billing_cycle ?? '-')
                                );
                            }),
                    ]),

                Grid::make(3)
                    ->schema([
                        TextInput::make('subscription_total_sessions')
                            ->label('إجمالي الجلسات')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (TextInput $component, $record) {
                                $sub = $record?->activeSubscription ?? $record?->subscription;
                                $component->state($sub?->total_sessions ?? $record?->total_sessions ?? '-');
                            }),

                        TextInput::make('subscription_completed_sessions')
                            ->label('الجلسات المكتملة')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (TextInput $component, $record) {
                                $component->state($record?->sessions_completed ?? 0);
                            }),

                        TextInput::make('subscription_remaining_sessions')
                            ->label('الجلسات المتبقية')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (TextInput $component, $record) {
                                $sub = $record?->activeSubscription ?? $record?->subscription;
                                $total = $sub?->total_sessions ?? $record?->total_sessions ?? 0;
                                $completed = $record?->sessions_completed ?? 0;
                                $component->state(max(0, $total - $completed));
                            }),
                    ]),

                Grid::make(3)
                    ->schema([
                        TextInput::make('subscription_starts_at')
                            ->label('تاريخ البداية')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (TextInput $component, $record) {
                                $sub = $record?->activeSubscription ?? $record?->subscription;
                                $component->state(
                                    $sub?->starts_at
                                        ? toAcademyTimezone($sub->starts_at)->format('Y-m-d')
                                        : '-'
                                );
                            }),

                        TextInput::make('subscription_ends_at')
                            ->label('تاريخ الانتهاء')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (TextInput $component, $record) {
                                $sub = $record?->activeSubscription ?? $record?->subscription;
                                $component->state(
                                    $sub?->ends_at
                                        ? toAcademyTimezone($sub->ends_at)->format('Y-m-d')
                                        : '-'
                                );
                            }),

                        TextInput::make('subscription_session_duration')
                            ->label('مدة الجلسة')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (TextInput $component, $record) {
                                $duration = $record?->default_duration_minutes;
                                $component->state($duration ? "{$duration} دقيقة" : '-');
                            }),
                    ]),

                Grid::make(2)
                    ->schema([
                        TextInput::make('subscription_learning_goals')
                            ->label('أهداف التعلم')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (TextInput $component, $record) {
                                $sub = $record?->activeSubscription ?? $record?->subscription;
                                $goals = $sub?->learning_goals;
                                $component->state(
                                    ! empty($goals)
                                        ? collect($goals)->implode('، ')
                                        : '-'
                                );
                            }),

                        TextInput::make('subscription_student_notes')
                            ->label('ملاحظات الطالب')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (TextInput $component, $record) {
                                $sub = $record?->activeSubscription ?? $record?->subscription;
                                $component->state($sub?->student_notes ?? '-');
                            }),
                    ]),

                TextInput::make('subscription_weekly_schedule')
                    ->label('الجدول الأسبوعي')
                    ->disabled()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (TextInput $component, $record) {
                        $sub = $record?->activeSubscription ?? $record?->subscription;
                        $schedule = $sub?->weekly_schedule;
                        $component->state(
                            ! empty($schedule)
                                ? collect($schedule)->map(fn ($s) => is_array($s) ? json_encode($s, JSON_UNESCAPED_UNICODE) : $s)->implode('، ')
                                : '-'
                        );
                    })
                    ->columnSpanFull(),

                Placeholder::make('no_subscription_notice')
                    ->label('')
                    ->content(new HtmlString('<span class="text-warning-600 dark:text-warning-400">لا يوجد اشتراك مرتبط بهذه الحلقة</span>'))
                    ->visible(fn ($record) => ! ($record?->activeSubscription ?? $record?->subscription))
                    ->columnSpanFull(),
            ])
            ->collapsible()
            ->visible(fn ($record) => $record !== null);
    }

    /**
     * Get additional form sections - override in child classes.
     * SuperAdmin: Description, Learning Objectives, Notes section
     * Teacher: Session Settings, Teacher Notes
     */
    protected static function getAdditionalFormSections(): array
    {
        return [];
    }

    // ========================================
    // Subscription Suspension Warning
    // ========================================

    /**
     * Check if the linked subscription is suspended.
     */
    protected static function isSubscriptionSuspended($record): bool
    {
        if (! $record) {
            return false;
        }

        $subscription = $record->subscription;
        if (! $subscription) {
            return false;
        }

        return $subscription->status === SessionSubscriptionStatus::EXPIRED;
    }

    /**
     * Warning section shown when the linked subscription is suspended.
     */
    protected static function getSubscriptionWarningSection(): Section
    {
        return Section::make('تنبيه - الاشتراك معلق')
            ->schema([
                Placeholder::make('subscription_warning')
                    ->label('')
                    ->content('تم تعليق الاشتراك المرتبط بهذه الحلقة بسبب عدم الدفع. يرجى تجديد الاشتراك للاستمرار في الخدمة.')
                    ->extraAttributes(['class' => 'text-danger-600 dark:text-danger-400 font-bold text-lg']),
            ])
            ->icon('heroicon-o-exclamation-triangle')
            ->iconColor('danger')
            ->visible(fn ($record) => static::isSubscriptionSuspended($record));
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
     * Override in child classes for panel-specific columns.
     */
    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('student.name')
                ->label('الطالب')
                ->searchable()
                ->sortable()
                ->weight(FontWeight::SemiBold),

            TextColumn::make('specialization')
                ->badge()
                ->label('التخصص')
                ->formatStateUsing(fn (string $state): string => QuranIndividualCircle::SPECIALIZATIONS[$state] ?? $state)
                ->colors([
                    'success' => 'memorization',
                    'info' => 'recitation',
                    'warning' => 'interpretation',
                    'danger' => 'tajweed',
                    'primary' => 'complete',
                ])
                ->toggleable(),

            TextColumn::make('memorization_level')
                ->label('المستوى')
                ->formatStateUsing(fn (string $state): string => QuranIndividualCircle::MEMORIZATION_LEVELS[$state] ?? $state)
                ->badge()
                ->color('gray')
                ->toggleable(),

            TextColumn::make('sessions_completed')
                ->label('الجلسات المكتملة')
                ->numeric()
                ->sortable()
                ->alignCenter()
                ->toggleable(),

            TextColumn::make('total_memorized_pages')
                ->label('صفحات الحفظ')
                ->numeric()
                ->sortable()
                ->alignCenter()
                ->toggleable(),

            TextColumn::make('total_reviewed_pages')
                ->label('صفحات المراجعة')
                ->numeric()
                ->sortable()
                ->alignCenter()
                ->toggleable(isToggledHiddenByDefault: true),

            IconColumn::make('is_active')
                ->label('نشط')
                ->boolean()
                ->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-x-circle')
                ->trueColor('success')
                ->falseColor('danger'),

            TextColumn::make('scheduling_status')
                ->label('حالة الجدولة')
                ->badge()
                ->state(function ($record) {
                    return static::getSchedulingStatusLabel($record);
                })
                ->color(function ($record) {
                    return static::getSchedulingStatusColor($record);
                })
                ->toggleable(),

            TextColumn::make('last_session_at')
                ->label('آخر جلسة')
                ->dateTime('Y-m-d')
                ->placeholder('لم تبدأ')
                ->sortable()
                ->toggleable(),

            TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime('Y-m-d')
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
            TernaryFilter::make('is_active')
                ->label('الحالة')
                ->trueLabel('نشطة')
                ->falseLabel('غير نشطة')
                ->placeholder('الكل'),

            SelectFilter::make('specialization')
                ->label('التخصص')
                ->options(QuranIndividualCircle::SPECIALIZATIONS),

            SelectFilter::make('memorization_level')
                ->label('مستوى الحفظ')
                ->options(QuranIndividualCircle::MEMORIZATION_LEVELS),
        ];
    }

    // ========================================
    // Scheduling Status Helpers (for table columns)
    // ========================================

    /**
     * Get the actual scheduled count from the eager-loaded withCount.
     * Falls back to cached field if withCount not available.
     */
    protected static function getScheduledCount($record): int
    {
        // Use withCount result (sessions_not_cancelled_count) if available
        return $record->sessions_not_cancelled_count ?? $record->sessions_scheduled ?? 0;
    }

    /**
     * Get scheduling status label using live session counts.
     */
    protected static function getSchedulingStatusLabel($record): string
    {
        $sub = $record->activeSubscription ?? $record->subscription;

        if (! $sub || $sub->status !== SessionSubscriptionStatus::ACTIVE) {
            return 'الاشتراك غير نشط';
        }

        if ($sub->ends_at?->isPast()) {
            return 'الاشتراك منتهي';
        }

        $total = $sub->total_sessions ?? $record->total_sessions ?? 0;
        $scheduled = static::getScheduledCount($record);
        $remaining = $total - $scheduled;

        if ($remaining <= 0) {
            return 'مجدولة بالكامل';
        }

        if ($scheduled > 0) {
            return "مجدولة جزئياً ({$scheduled}/{$total})";
        }

        return "غير مجدولة ({$remaining} متبقية)";
    }

    /**
     * Get scheduling status color.
     */
    protected static function getSchedulingStatusColor($record): string
    {
        $sub = $record->activeSubscription ?? $record->subscription;

        if (! $sub || $sub->status !== SessionSubscriptionStatus::ACTIVE) {
            return 'gray';
        }

        if ($sub->ends_at?->isPast()) {
            return 'danger';
        }

        $total = $sub->total_sessions ?? $record->total_sessions ?? 0;
        $scheduled = static::getScheduledCount($record);
        $remaining = $total - $scheduled;

        if ($remaining <= 0) {
            return 'success';
        }

        if ($scheduled > 0) {
            return 'info';
        }

        return 'warning';
    }

    // ========================================
    // Eloquent Query
    // ========================================

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'quranTeacher',
                'student',
                'academy',
                'subscription.package',
                'linkedSubscriptions.package',
            ])
            ->withCount(['sessions as sessions_not_cancelled_count' => function ($q) {
                $q->whereNotIn('status', ['cancelled']);
            }]);

        return static::scopeEloquentQuery($query);
    }

    public static function getRelations(): array
    {
        return [];
    }
}
