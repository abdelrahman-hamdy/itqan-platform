<?php

namespace App\Filament\Shared\Resources;

use App\Enums\SessionDuration;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\TimeSlot;
use App\Enums\WeekDays;
use App\Filament\Shared\Traits\HasSubscriptionActions;
use App\Models\SavedPaymentMethod;
use App\Services\AcademyContextService;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseSubscriptionResource extends Resource
{
    use HasSubscriptionActions;

    // Abstract methods for child classes to implement
    abstract protected static function getBasicInfoFormSection(): Section;

    abstract protected static function getPricingFormSection(): Section;

    abstract protected static function getTypeSpecificTableColumns(): array;

    abstract protected static function getTypeSpecificFilters(): array;

    abstract protected static function getTypeSpecificInfolistSections(): array;

    abstract protected static function scopeEloquentQuery(Builder $query): Builder;

    abstract protected static function getTableActions(): array;

    abstract protected static function getTableBulkActions(): array;

    // Shared session settings section
    protected static function getSessionSettingsSection(): Section
    {
        return Section::make('إعدادات الجلسات')
            ->schema([
                TextInput::make('total_sessions')
                    ->label('عدد الجلسات الكلي')
                    ->numeric()
                    ->default(8)
                    ->minValue(1)
                    ->required()
                    ->helperText('إجمالي عدد الجلسات المتاحة في هذا الاشتراك'),

                TextInput::make('total_sessions_scheduled')
                    ->label('الجلسات المجدولة')
                    ->numeric()
                    ->disabled()
                    ->helperText('عدد الجلسات التي تم جدولتها'),

                TextInput::make('total_sessions_completed')
                    ->label('الجلسات المكتملة')
                    ->numeric()
                    ->disabled()
                    ->helperText('عدد الجلسات المكتملة بنجاح'),

                TextInput::make('total_sessions_missed')
                    ->label('الجلسات الفائتة')
                    ->numeric()
                    ->disabled()
                    ->helperText('عدد الجلسات الفائتة أو الملغاة'),

                TextInput::make('sessions_remaining')
                    ->label('الجلسات المتبقية')
                    ->numeric()
                    ->disabled()
                    ->helperText('عدد الجلسات المتبقية للاستخدام'),

                Select::make('session_duration_minutes')
                    ->label('مدة الجلسة')
                    ->options(SessionDuration::options())
                    ->default(SessionDuration::FORTY_FIVE_MINUTES->value)
                    ->required()
                    ->helperText('المدة الافتراضية لكل جلسة'),
            ])
            ->columns(3);
    }

    // Shared student preferences section
    protected static function getStudentPreferencesSection(): Section
    {
        return Section::make('تفضيلات الطالب')
            ->description('المعلومات التي قدمها الطالب عند الاشتراك')
            ->schema([
                CheckboxList::make('weekly_schedule.preferred_days')
                    ->label('الأيام المفضلة')
                    ->options(WeekDays::options())
                    ->columns(4)
                    ->columnSpanFull(),

                Select::make('weekly_schedule.preferred_time')
                    ->label('الفترة المفضلة')
                    ->options(TimeSlot::options())
                    ->placeholder('اختر الفترة المفضلة'),

                Textarea::make('student_notes')
                    ->label('ملاحظات الطالب')
                    ->rows(2)
                    ->helperText('الملاحظات التي قدمها الطالب عند الاشتراك')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    // Shared notes section
    protected static function getNotesSection(): Section
    {
        return Section::make('ملاحظات')
            ->schema([
                Grid::make(2)
                    ->schema([
                        Textarea::make('admin_notes')
                            ->label('ملاحظات الإدارة')
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('ملاحظات داخلية للإدارة فقط'),

                        Textarea::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->rows(3)
                            ->maxLength(2000)
                            ->helperText('ملاحظات للمشرفين'),
                    ]),
            ]);
    }

    // Shared auto-renew toggle with saved payment method validation
    protected static function getAutoRenewToggle(): Toggle
    {
        return Toggle::make('auto_renew')
            ->label('التجديد التلقائي')
            ->default(true)
            ->helperText(function ($record) {
                if (! $record || ! $record->student_id) {
                    return 'يتطلب بطاقة دفع محفوظة';
                }

                $hasSavedCard = SavedPaymentMethod::where('user_id', $record->student_id)
                    ->where('gateway', 'paymob')
                    ->where('is_active', true)
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->exists();

                return $hasSavedCard
                    ? '✓ بطاقة محفوظة موجودة - التجديد التلقائي متاح'
                    : '⚠️ لا توجد بطاقة محفوظة. يجب على الطالب إضافة بطاقة أولاً.';
            })
            ->disabled(function ($record) {
                if (! $record || ! $record->student_id) {
                    return false;
                }

                return ! SavedPaymentMethod::where('user_id', $record->student_id)
                    ->where('gateway', 'paymob')
                    ->where('is_active', true)
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->exists();
            });
    }

    // Shared table columns
    protected static function getSharedTableColumns(): array
    {
        return [
            TextColumn::make('subscription_code')
                ->label('رمز الاشتراك')
                ->searchable()
                ->sortable()
                ->fontFamily('mono')
                ->weight(FontWeight::Bold),

            TextColumn::make('student.name')
                ->label('الطالب')
                ->searchable()
                ->sortable(),

            TextColumn::make('status')
                ->badge()
                ->label('حالة الاشتراك')
                ->formatStateUsing(fn ($state): string => match ($state instanceof SessionSubscriptionStatus ? $state->value : $state) {
                    SessionSubscriptionStatus::PENDING->value => 'قيد الانتظار',
                    SessionSubscriptionStatus::ACTIVE->value => 'نشط',
                    SessionSubscriptionStatus::PAUSED->value => 'متوقف مؤقتاً',
                    SessionSubscriptionStatus::CANCELLED->value => 'ملغي',
                    default => $state instanceof SessionSubscriptionStatus ? $state->value : (string) $state,
                })
                ->colors([
                    'success' => SessionSubscriptionStatus::ACTIVE->value,
                    'warning' => SessionSubscriptionStatus::PENDING->value,
                    'info' => SessionSubscriptionStatus::PAUSED->value,
                    'danger' => SessionSubscriptionStatus::CANCELLED->value,
                ]),

            TextColumn::make('payment_status')
                ->badge()
                ->label('حالة الدفع')
                ->formatStateUsing(fn ($state): string => match ($state instanceof SubscriptionPaymentStatus ? $state->value : $state) {
                    SubscriptionPaymentStatus::PENDING->value => 'في الانتظار',
                    SubscriptionPaymentStatus::PAID->value => 'مدفوع',
                    SubscriptionPaymentStatus::FAILED->value => 'فشل',
                    default => $state instanceof SubscriptionPaymentStatus ? $state->value : (string) $state,
                })
                ->colors([
                    'warning' => SubscriptionPaymentStatus::PENDING->value,
                    'success' => SubscriptionPaymentStatus::PAID->value,
                    'danger' => SubscriptionPaymentStatus::FAILED->value,
                ])
                ->toggleable(),

            TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    // Shared filters
    protected static function getSharedFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label('حالة الاشتراك')
                ->options([
                    SessionSubscriptionStatus::ACTIVE->value => 'نشط',
                    SessionSubscriptionStatus::PENDING->value => 'قيد الانتظار',
                    SessionSubscriptionStatus::PAUSED->value => 'متوقف مؤقتاً',
                    SessionSubscriptionStatus::CANCELLED->value => 'ملغي',
                ]),

            SelectFilter::make('payment_status')
                ->label('حالة الدفع')
                ->options(SubscriptionPaymentStatus::options()),

            Filter::make('date_from')
                ->label('من تاريخ')
                ->schema([
                    DatePicker::make('date')
                        ->label('التاريخ'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when(
                        $data['date'],
                        fn (Builder $query, $date): Builder => $query->whereDate('end_date', '>=', $date)
                    );
                }),

            Filter::make('date_to')
                ->label('إلى تاريخ')
                ->schema([
                    DatePicker::make('date')
                        ->label('التاريخ'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when(
                        $data['date'],
                        fn (Builder $query, $date): Builder => $query->whereDate('end_date', '<=', $date)
                    );
                }),

            static::getExpiredPendingFilter(),

            TrashedFilter::make()->label(__('filament.filters.trashed')),
        ];
    }

    // Shared extension history infolist section
    protected static function getExtensionHistoryInfolistSection(): Section
    {
        return Section::make('سجل التمديدات')
            ->schema([
                RepeatableEntry::make('metadata.extensions')
                    ->label('')
                    ->schema([
                        TextEntry::make('grace_days')
                            ->label('عدد الأيام')
                            ->suffix(' يوم')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('extended_by_name')
                            ->label('تم بواسطة'),
                        TextEntry::make('extended_at')
                            ->label('تاريخ التمديد')
                            ->dateTime('Y-m-d H:i'),
                        TextEntry::make('grace_period_ends_at')
                            ->label('نهاية فترة السماح')
                            ->dateTime('Y-m-d H:i')
                            ->color('warning')
                            ->weight(FontWeight::Bold),
                    ])
                    ->columns(4)
                    ->contained(false),
            ])
            ->collapsed()
            ->visible(fn ($record) => ! empty($record->metadata['extensions']));
    }

    // Shared subscription status and dates infolist section
    protected static function getSubscriptionStatusAndDatesSection(): Section
    {
        return Section::make('حالة الاشتراك والمواعيد')
            ->schema([
                Grid::make(4)
                    ->schema([
                        TextEntry::make('status')
                            ->label('حالة الاشتراك')
                            ->badge()
                            ->weight(FontWeight::Bold)
                            ->formatStateUsing(fn (mixed $state): string => match ($state instanceof SessionSubscriptionStatus ? $state->value : $state) {
                                SessionSubscriptionStatus::PENDING->value => 'قيد الانتظار',
                                SessionSubscriptionStatus::ACTIVE->value => 'نشط',
                                SessionSubscriptionStatus::PAUSED->value => 'متوقف مؤقتاً',
                                SessionSubscriptionStatus::CANCELLED->value => 'ملغي',
                                default => (string) $state,
                            })
                            ->color(fn (mixed $state): string => match ($state instanceof SessionSubscriptionStatus ? $state->value : $state) {
                                SessionSubscriptionStatus::ACTIVE->value => 'success',
                                SessionSubscriptionStatus::PENDING->value => 'warning',
                                SessionSubscriptionStatus::PAUSED->value => 'info',
                                SessionSubscriptionStatus::CANCELLED->value => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('payment_status')
                            ->label('حالة الدفع')
                            ->badge()
                            ->formatStateUsing(fn (mixed $state): string => match ($state instanceof SubscriptionPaymentStatus ? $state->value : $state) {
                                SubscriptionPaymentStatus::PENDING->value => 'في الانتظار',
                                SubscriptionPaymentStatus::PAID->value => 'مدفوع',
                                SubscriptionPaymentStatus::FAILED->value => 'فشل',
                                default => (string) $state,
                            })
                            ->color(fn (mixed $state): string => match ($state instanceof SubscriptionPaymentStatus ? $state->value : $state) {
                                SubscriptionPaymentStatus::PAID->value => 'success',
                                SubscriptionPaymentStatus::PENDING->value => 'warning',
                                SubscriptionPaymentStatus::FAILED->value => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('auto_renew')
                            ->label('التجديد التلقائي')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state ? 'مفعّل' : 'معطّل')
                            ->color(fn ($state): string => $state ? 'success' : 'gray'),
                        TextEntry::make('grace_period_status')
                            ->label('فترة السماح')
                            ->badge()
                            ->getStateUsing(function ($record) {
                                if (! $record) {
                                    return null;
                                }
                                $metadata = $record->metadata ?? [];
                                if (! isset($metadata['grace_period_ends_at'])) {
                                    return null;
                                }
                                $gracePeriodEndsAt = \Carbon\Carbon::parse($metadata['grace_period_ends_at']);
                                if ($gracePeriodEndsAt->isPast()) {
                                    return 'منتهية';
                                }

                                return 'نشطة حتى '.$gracePeriodEndsAt->format('Y-m-d');
                            })
                            ->color(fn ($state) => match (true) {
                                $state === null => 'gray',
                                str_contains($state ?? '', 'نشطة') => 'warning',
                                default => 'danger',
                            })
                            ->visible(fn ($record) => isset($record?->metadata['grace_period_ends_at'])),
                    ]),
                Grid::make(3)
                    ->schema([
                        TextEntry::make('starts_at')
                            ->label('تاريخ البدء')
                            ->dateTime('Y-m-d')
                            ->placeholder('لم يتم التحديد'),
                        TextEntry::make('ends_at')
                            ->label('تاريخ انتهاء الاشتراك (المدفوع)')
                            ->dateTime('Y-m-d')
                            ->placeholder('لم يتم التحديد')
                            ->color(fn ($record) => $record?->ends_at && $record->ends_at->isPast() ? 'danger' : null)
                            ->weight(fn ($record) => $record?->ends_at && $record->ends_at->isPast() ? FontWeight::Bold : null)
                            ->helperText(fn ($record) => match (true) {
                                $record?->isInGracePeriod() => 'منتهي - في فترة السماح حتى '.$record->getGracePeriodEndsAt()->format('Y-m-d'),
                                $record?->ends_at?->isPast() ?? false => 'منتهي الصلاحية',
                                default => null,
                            }),
                        TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime('Y-m-d H:i'),
                    ]),
            ]);
    }

    // ========================================
    // Academy Context Methods
    // ========================================

    protected static function isViewingAllAcademies(): bool
    {
        if (Filament::getTenant() !== null) {
            return false;
        }

        $academyContextService = app(AcademyContextService::class);

        return $academyContextService->getCurrentAcademyId() === null;
    }

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }

    protected static function getAcademyColumn(): TextColumn
    {
        $academyPath = static::getAcademyRelationshipPath();

        return TextColumn::make($academyPath.'.name')
            ->label('الأكاديمية')
            ->sortable()
            ->searchable()
            ->visible(static::isViewingAllAcademies())
            ->placeholder('غير محدد');
    }

    // Apply panel-specific scoping
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['student', 'academy']);

        return static::scopeEloquentQuery($query);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(array_merge(
                static::getSharedTableColumns(),
                static::getTypeSpecificTableColumns()
            ))
            ->filters(array_merge(
                static::getSharedFilters(),
                static::getTypeSpecificFilters()
            ))
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordUrl(fn ($record) => static::getUrl('view', ['record' => $record]))
            ->recordActions(static::getTableActions())
            ->toolbarActions(static::getTableBulkActions())
            ->defaultSort('created_at', 'desc');
    }
}
