<?php

namespace App\Filament\Shared\Resources;

use App\Enums\SessionDuration;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\TimeSlot;
use App\Enums\WeekDays;
use App\Filament\Shared\Traits\HasSubscriptionActions;
use App\Models\SavedPaymentMethod;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
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

                Forms\Components\Select::make('session_duration_minutes')
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
                Forms\Components\CheckboxList::make('weekly_schedule.preferred_days')
                    ->label('الأيام المفضلة')
                    ->options(WeekDays::options())
                    ->columns(4)
                    ->columnSpanFull(),

                Forms\Components\Select::make('weekly_schedule.preferred_time')
                    ->label('الفترة المفضلة')
                    ->options(TimeSlot::options())
                    ->placeholder('اختر الفترة المفضلة'),

                Forms\Components\Textarea::make('student_notes')
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

            BadgeColumn::make('status')
                ->label('حالة الاشتراك')
                ->formatStateUsing(fn ($state): string => match ($state instanceof \App\Enums\SessionSubscriptionStatus ? $state->value : $state) {
                    SessionSubscriptionStatus::PENDING->value => 'قيد الانتظار',
                    SessionSubscriptionStatus::ACTIVE->value => 'نشط',
                    SessionSubscriptionStatus::PAUSED->value => 'متوقف مؤقتاً',
                    SessionSubscriptionStatus::CANCELLED->value => 'ملغي',
                    default => $state instanceof \App\Enums\SessionSubscriptionStatus ? $state->value : (string) $state,
                })
                ->colors([
                    'success' => SessionSubscriptionStatus::ACTIVE->value,
                    'warning' => SessionSubscriptionStatus::PENDING->value,
                    'info' => SessionSubscriptionStatus::PAUSED->value,
                    'danger' => SessionSubscriptionStatus::CANCELLED->value,
                ]),

            BadgeColumn::make('payment_status')
                ->label('حالة الدفع')
                ->formatStateUsing(fn ($state): string => match ($state instanceof \App\Enums\SubscriptionPaymentStatus ? $state->value : $state) {
                    SubscriptionPaymentStatus::PENDING->value => 'في الانتظار',
                    SubscriptionPaymentStatus::PAID->value => 'مدفوع',
                    SubscriptionPaymentStatus::FAILED->value => 'فشل',
                    default => $state instanceof \App\Enums\SubscriptionPaymentStatus ? $state->value : (string) $state,
                })
                ->colors([
                    'warning' => SubscriptionPaymentStatus::PENDING->value,
                    'success' => SubscriptionPaymentStatus::PAID->value,
                    'danger' => SubscriptionPaymentStatus::FAILED->value,
                ]),

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
                ->form([
                    Forms\Components\DatePicker::make('date')
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
                ->form([
                    Forms\Components\DatePicker::make('date')
                        ->label('التاريخ'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when(
                        $data['date'],
                        fn (Builder $query, $date): Builder => $query->whereDate('end_date', '<=', $date)
                    );
                }),

            Tables\Filters\TrashedFilter::make()->label(__('filament.filters.trashed')),
        ];
    }

    // Shared pause action
    protected static function getPauseAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('pause')
            ->label('إيقاف مؤقت')
            ->icon('heroicon-o-pause-circle')
            ->color('warning')
            ->form([
                Textarea::make('pause_reason')
                    ->label('سبب الإيقاف')
                    ->required(),
            ])
            ->action(function ($record, array $data) {
                $record->update([
                    'status' => SessionSubscriptionStatus::PAUSED,
                    'paused_at' => now(),
                    'pause_reason' => $data['pause_reason'],
                ]);
            })
            ->visible(fn ($record) => $record->status === SessionSubscriptionStatus::ACTIVE);
    }

    // Shared resume action
    protected static function getResumeAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('resume')
            ->label('استئناف')
            ->icon('heroicon-o-play-circle')
            ->color('success')
            ->requiresConfirmation()
            ->action(function ($record) {
                $record->update([
                    'status' => SessionSubscriptionStatus::ACTIVE,
                    'paused_at' => null,
                    'pause_reason' => null,
                ]);
            })
            ->visible(fn ($record) => $record->status === SessionSubscriptionStatus::PAUSED);
    }

    // Shared extend subscription action
    protected static function getExtendSubscriptionAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('extend_subscription')
            ->label('تمديد الاشتراك')
            ->icon('heroicon-o-calendar-days')
            ->color('success')
            ->form([
                TextInput::make('extension_days')
                    ->label('عدد أيام التمديد')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(365)
                    ->default(7)
                    ->helperText('سيتم إضافة هذه الأيام إلى تاريخ انتهاء الاشتراك الحالي'),
                Textarea::make('extension_reason')
                    ->label('سبب التمديد')
                    ->required()
                    ->placeholder('مثال: تعويض عن خلل تقني، اتفاق تجاري، إلخ')
                    ->rows(3),
            ])
            ->action(function (array $data, $record) {
                $currentEndsAt = $record->ends_at ?? now();
                $newEndsAt = $currentEndsAt->copy()->addDays($data['extension_days']);

                $metadata = $record->metadata ?? [];
                $metadata['extensions'] = $metadata['extensions'] ?? [];
                $metadata['extensions'][] = [
                    'extended_by' => auth()->id(),
                    'extended_by_name' => auth()->user()->name,
                    'extension_days' => $data['extension_days'],
                    'extension_reason' => $data['extension_reason'],
                    'old_ends_at' => $currentEndsAt->toDateTimeString(),
                    'new_ends_at' => $newEndsAt->toDateTimeString(),
                    'extended_at' => now()->toDateTimeString(),
                ];

                $record->update([
                    'ends_at' => $newEndsAt,
                    'next_billing_date' => $newEndsAt,
                    'metadata' => $metadata,
                ]);

                \Filament\Notifications\Notification::make()
                    ->success()
                    ->title('تم تمديد الاشتراك بنجاح')
                    ->body("تم إضافة {$data['extension_days']} يوم. تاريخ الانتهاء الجديد: {$newEndsAt->format('Y-m-d')}")
                    ->send();
            })
            ->requiresConfirmation()
            ->modalHeading('تمديد الاشتراك')
            ->modalDescription('سيتم إضافة الأيام المحددة إلى تاريخ انتهاء الاشتراك الحالي')
            ->visible(fn ($record) => auth()->user()->hasRole(['super_admin', 'admin']));
    }

    // Shared extension history infolist section
    protected static function getExtensionHistoryInfolistSection(): Infolists\Components\Section
    {
        return Infolists\Components\Section::make('سجل التمديدات')
            ->schema([
                Infolists\Components\RepeatableEntry::make('metadata.extensions')
                    ->label('')
                    ->schema([
                        Infolists\Components\TextEntry::make('extension_days')
                            ->label('عدد الأيام')
                            ->suffix(' يوم')
                            ->weight(FontWeight::Bold),
                        Infolists\Components\TextEntry::make('extension_reason')
                            ->label('سبب التمديد')
                            ->columnSpan(2),
                        Infolists\Components\TextEntry::make('extended_by_name')
                            ->label('تم بواسطة'),
                        Infolists\Components\TextEntry::make('extended_at')
                            ->label('تاريخ التمديد')
                            ->dateTime('Y-m-d H:i'),
                        Infolists\Components\TextEntry::make('old_ends_at')
                            ->label('تاريخ الانتهاء السابق')
                            ->dateTime('Y-m-d H:i'),
                        Infolists\Components\TextEntry::make('new_ends_at')
                            ->label('تاريخ الانتهاء الجديد')
                            ->dateTime('Y-m-d H:i')
                            ->color('success')
                            ->weight(FontWeight::Bold),
                    ])
                    ->columns(4)
                    ->contained(false),
            ])
            ->collapsed()
            ->visible(fn ($record) => ! empty($record->metadata['extensions']));
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
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions(static::getTableActions())
            ->bulkActions(static::getTableBulkActions())
            ->headerActions([
                // Header action to cancel all expired pending (from HasSubscriptionActions trait)
                static::getCancelExpiredPendingAction(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
