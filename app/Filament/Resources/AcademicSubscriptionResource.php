<?php

namespace App\Filament\Resources;

use App\Enums\SessionDuration;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\TimeSlot;
use App\Enums\UserType;
use App\Enums\WeekDays;
use App\Filament\Concerns\HasCrossAcademyAccess;
use App\Filament\Resources\AcademicSubscriptionResource\Pages;
use App\Filament\Shared\Traits\HasSubscriptionActions;
use App\Models\AcademicSubscription;
use App\Models\SavedPaymentMethod;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicSubscriptionResource extends BaseResource
{
    use HasCrossAcademyAccess;
    use HasSubscriptionActions;

    protected static ?string $model = AcademicSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'الاشتراكات الأكاديمية';

    protected static ?string $modelLabel = 'اشتراك أكاديمي';

    protected static ?string $pluralModelLabel = 'الاشتراكات الأكاديمية';

    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 2;

    /**
     * Get the navigation badge showing pending subscriptions count
     */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', SessionSubscriptionStatus::PENDING->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * Get the navigation badge color
     */
    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::where('status', SessionSubscriptionStatus::PENDING->value)->count() > 0 ? 'warning' : null;
    }

    /**
     * Get the navigation badge tooltip
     */
    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('filament.tabs.pending');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['student', 'teacher.user', 'subject', 'gradeLevel', 'academy']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الاشتراك الأساسية')
                    ->schema([
                        Forms\Components\Select::make('academy_id')
                            ->relationship('academy', 'name')
                            ->label('الأكاديمية')
                            ->required()
                            ->disabled()
                            ->default(fn () => auth()->user()->academy_id),

                        Forms\Components\Select::make('student_id')
                            ->label('الطالب')
                            ->searchable()
                            ->preload(false)
                            ->getSearchResultsUsing(function (string $search) {
                                $academyId = \App\Services\AcademyContextService::getCurrentAcademyId();

                                return \App\Models\User::where('user_type', UserType::STUDENT->value)
                                    ->with('studentProfile')
                                    ->when($academyId, function ($query) use ($academyId) {
                                        $query->whereHas('studentProfile.gradeLevel', function ($q) use ($academyId) {
                                            $q->where('academy_id', $academyId);
                                        });
                                    })
                                    ->where(function ($q) use ($search) {
                                        $q->where('name', 'like', "%{$search}%")
                                            ->orWhere('email', 'like', "%{$search}%")
                                            ->orWhereHas('studentProfile', function ($sq) use ($search) {
                                                $sq->where('student_code', 'like', "%{$search}%");
                                            });
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($user) {
                                        return [$user->id => $user->studentProfile?->display_name ?? $user->name];
                                    });
                            })
                            ->getOptionLabelUsing(function ($value) {
                                $user = \App\Models\User::with('studentProfile')->find($value);

                                return $user?->studentProfile?->display_name ?? $user?->name ?? 'طالب #'.$value;
                            })
                            ->required(),

                        Forms\Components\Select::make('teacher_id')
                            ->label('المعلم')
                            ->required()
                            ->searchable()
                            ->preload(false)
                            ->getSearchResultsUsing(function (string $search) {
                                $academyId = \App\Services\AcademyContextService::getCurrentAcademyId();

                                return \App\Models\AcademicTeacherProfile::with('user')
                                    ->when($academyId, function ($query) use ($academyId) {
                                        $query->where('academy_id', $academyId);
                                    })
                                    ->where(function ($q) use ($search) {
                                        $q->whereHas('user', function ($uq) use ($search) {
                                            $uq->where('name', 'like', "%{$search}%")
                                                ->orWhere('email', 'like', "%{$search}%");
                                        });
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn ($teacher) => [
                                        $teacher->id => $teacher->user?->name ?? $teacher->full_name ?? 'معلم #'.$teacher->id,
                                    ]);
                            })
                            ->getOptionLabelUsing(function ($value) {
                                $teacher = \App\Models\AcademicTeacherProfile::with('user')->find($value);

                                return $teacher?->user?->name ?? $teacher?->full_name ?? 'معلم #'.$value;
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('subject_id', null);
                                $set('grade_level_id', null);
                            }),

                        Forms\Components\Select::make('subject_id')
                            ->label('المادة الدراسية')
                            ->required()
                            ->searchable()
                            ->options(function (Forms\Get $get) {
                                $teacherId = $get('teacher_id');
                                if ($teacherId) {
                                    $teacher = \App\Models\AcademicTeacherProfile::find($teacherId);
                                    if ($teacher) {
                                        return $teacher->subjects->pluck('name', 'id')->toArray();
                                    }
                                }

                                // Fallback: show all subjects
                                return \App\Models\AcademicSubject::pluck('name', 'id')->toArray();
                            })
                            ->getOptionLabelUsing(function ($value, $record) {
                                // Show snapshot name if available, otherwise try to find the subject
                                if ($record?->subject_name) {
                                    return $record->subject_name;
                                }
                                $subject = \App\Models\AcademicSubject::find($value);

                                return $subject?->name ?? 'مادة #'.$value;
                            })
                            ->live(),

                        Forms\Components\Select::make('grade_level_id')
                            ->label('المرحلة الدراسية')
                            ->required()
                            ->searchable()
                            ->options(function (Forms\Get $get) {
                                $teacherId = $get('teacher_id');
                                if ($teacherId) {
                                    $teacher = \App\Models\AcademicTeacherProfile::find($teacherId);
                                    if ($teacher) {
                                        return $teacher->gradeLevels->pluck('name', 'id')->toArray();
                                    }
                                }

                                // Fallback: show all grade levels
                                return \App\Models\AcademicGradeLevel::pluck('name', 'id')->toArray();
                            })
                            ->getOptionLabelUsing(function ($value, $record) {
                                // Show snapshot name if available, otherwise try to find the grade level
                                if ($record?->grade_level_name) {
                                    return $record->grade_level_name;
                                }
                                $gradeLevel = \App\Models\AcademicGradeLevel::find($value);

                                return $gradeLevel?->name ?? 'مرحلة #'.$value;
                            })
                            ->live(),

                        Forms\Components\Select::make('academic_package_id')
                            ->label('الباقة الأكاديمية')
                            ->searchable()
                            ->preload(false)
                            ->getSearchResultsUsing(function (string $search) {
                                $academyId = \App\Services\AcademyContextService::getCurrentAcademyId();

                                return \App\Models\AcademicPackage::query()
                                    ->when($academyId, function ($query) use ($academyId) {
                                        $query->where('academy_id', $academyId);
                                    })
                                    ->where('name', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->pluck('name', 'id');
                            })
                            ->getOptionLabelUsing(function ($value, $record) {
                                // Show snapshot name if available
                                if ($record?->package_name_ar) {
                                    return $record->package_name_ar;
                                }
                                $package = \App\Models\AcademicPackage::find($value);

                                return $package?->name ?? 'باقة #'.$value;
                            }),
                    ])->columns(2),

                Forms\Components\Section::make('تفاصيل الاشتراك')
                    ->schema([
                        Forms\Components\TextInput::make('subscription_code')
                            ->label('رمز الاشتراك')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('billing_cycle')
                            ->label('دورة الفوترة')
                            ->options([
                                'monthly' => 'شهرياً',
                                'quarterly' => 'كل 3 شهور',
                                'yearly' => 'سنوياً',
                            ])
                            ->default('monthly')
                            ->required(),

                        Forms\Components\TextInput::make('final_monthly_amount')
                            ->label('سعر الاشتراك الشهري')
                            ->numeric()
                            ->suffix(getCurrencySymbol())
                            ->helperText('السعر النهائي بعد الخصم'),
                    ])->columns(3),

                Forms\Components\Section::make('إعدادات الجلسات')
                    ->schema([
                        Forms\Components\TextInput::make('total_sessions')
                            ->label('عدد الجلسات الكلي')
                            ->numeric()
                            ->default(8)
                            ->minValue(1)
                            ->required()
                            ->helperText('إجمالي عدد الجلسات المتاحة في هذا الاشتراك'),

                        Forms\Components\TextInput::make('total_sessions_scheduled')
                            ->label('الجلسات المجدولة')
                            ->numeric()
                            ->disabled()
                            ->helperText('عدد الجلسات التي تم جدولتها'),

                        Forms\Components\TextInput::make('total_sessions_completed')
                            ->label('الجلسات المكتملة')
                            ->numeric()
                            ->disabled()
                            ->helperText('عدد الجلسات المكتملة بنجاح'),

                        Forms\Components\TextInput::make('total_sessions_missed')
                            ->label('الجلسات الفائتة')
                            ->numeric()
                            ->disabled()
                            ->helperText('عدد الجلسات الفائتة أو الملغاة'),

                        Forms\Components\TextInput::make('sessions_remaining')
                            ->label('الجلسات المتبقية')
                            ->numeric()
                            ->disabled()
                            ->helperText('عدد الجلسات المتبقية للاستخدام'),

                        Forms\Components\Select::make('session_duration_minutes')
                            ->label('مدة الجلسة')
                            ->options(SessionDuration::options())
                            ->default(SessionDuration::SIXTY_MINUTES->value)
                            ->required()
                            ->helperText('المدة الافتراضية لكل جلسة'),
                    ])->columns(3),

                Forms\Components\Section::make('جدولة الجلسات')
                    ->schema([
                        Forms\Components\TextInput::make('sessions_per_week')
                            ->label('عدد الجلسات أسبوعياً')
                            ->numeric()
                            ->default(2)
                            ->minValue(1)
                            ->maxValue(7)
                            ->helperText('عدد الجلسات المجدولة كل أسبوع'),

                        Forms\Components\TextInput::make('sessions_per_month')
                            ->label('عدد الجلسات شهرياً')
                            ->numeric()
                            ->disabled()
                            ->helperText('إجمالي الجلسات المتوقعة في الشهر (محسوب تلقائياً)'),
                    ])->columns(2),

                Forms\Components\Section::make('التواريخ والدفع')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ البدء')
                            ->default(now())
                            ->required()
                            ->live(),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('تاريخ الانتهاء')
                            ->after('start_date'),

                        Forms\Components\DatePicker::make('next_billing_date')
                            ->label('تاريخ الفوترة التالي'),

                        Forms\Components\Select::make('status')
                            ->label('حالة الاشتراك')
                            ->options(SessionSubscriptionStatus::options())
                            ->default(SessionSubscriptionStatus::ACTIVE->value)
                            ->required(),

                        Forms\Components\Select::make('payment_status')
                            ->label('حالة الدفع')
                            ->options(SubscriptionPaymentStatus::options())
                            ->default(SubscriptionPaymentStatus::PENDING->value)
                            ->required(),

                        Forms\Components\Toggle::make('auto_renew')
                            ->label('التجديد التلقائي')
                            ->default(true)
                            ->helperText(function ($record) {
                                if (!$record || !$record->student_id) {
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
                                if (!$record || !$record->student_id) {
                                    return false;
                                }

                                return !SavedPaymentMethod::where('user_id', $record->student_id)
                                    ->where('gateway', 'paymob')
                                    ->where('is_active', true)
                                    ->where(function ($query) {
                                        $query->whereNull('expires_at')
                                            ->orWhere('expires_at', '>', now());
                                    })
                                    ->exists();
                            }),
                    ])->columns(3),

                Forms\Components\Section::make('تفضيلات الطالب')
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
                    ]),

                Forms\Components\Section::make('ملاحظات')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('admin_notes')
                                    ->label('ملاحظات الإدارة')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->helperText('ملاحظات داخلية للإدارة'),

                                Forms\Components\Textarea::make('supervisor_notes')
                                    ->label('ملاحظات المشرف')
                                    ->rows(3)
                                    ->maxLength(2000)
                                    ->helperText('ملاحظات مرئية للمشرف والإدارة فقط'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(
                fn (AcademicSubscription $record): string => Pages\ViewAcademicSubscription::getUrl([$record->id])
            )
            ->columns([
                static::getAcademyColumn(),

                Tables\Columns\TextColumn::make('subscription_code')
                    ->label('رمز الاشتراك')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('teacher.user.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('المادة')
                    ->searchable(),

                Tables\Columns\TextColumn::make('gradeLevel.name')
                    ->label('المرحلة')
                    ->searchable(),

                Tables\Columns\TextColumn::make('final_monthly_amount')
                    ->label('المبلغ الشهري')
                    ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof \App\Enums\SessionSubscriptionStatus ? $state->label() : $state)
                    ->color(fn ($state) => $state instanceof \App\Enums\SessionSubscriptionStatus ? $state->color() : 'secondary'),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof \App\Enums\SubscriptionPaymentStatus ? $state->label() : $state)
                    ->color(fn ($state) => $state instanceof \App\Enums\SubscriptionPaymentStatus ? $state->color() : 'secondary'),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البدء')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('next_billing_date')
                    ->label('الفوترة التالية')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament.status'))
                    ->options(SessionSubscriptionStatus::options()),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label(__('filament.payment_status'))
                    ->options(SubscriptionPaymentStatus::options()),

                Tables\Filters\SelectFilter::make('subject_id')
                    ->label(__('filament.course.subject'))
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label(__('filament.teacher'))
                    ->relationship('teacher.user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('filament.filters.from_date')),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('filament.filters.to_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = __('filament.filters.from_date').': '.$data['from'];
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = __('filament.filters.to_date').': '.$data['until'];
                        }

                        return $indicators;
                    }),

                Tables\Filters\TrashedFilter::make()->label(__('filament.filters.trashed')),

                // Subscription pending filters (from HasSubscriptionActions trait)
                ...static::getSubscriptionFilters(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                // Cancel pending action (from HasSubscriptionActions trait)
                static::getCancelPendingAction(),
                Tables\Actions\Action::make('pause')
                    ->label('إيقاف مؤقت')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('إيقاف الاشتراك مؤقتاً')
                    ->modalDescription('هل أنت متأكد من إيقاف هذا الاشتراك مؤقتاً؟')
                    ->action(function (AcademicSubscription $record) {
                        $record->update([
                            'status' => SessionSubscriptionStatus::PAUSED,
                            'paused_at' => now(),
                        ]);
                    })
                    ->visible(fn (AcademicSubscription $record) => $record->status === SessionSubscriptionStatus::ACTIVE),
                Tables\Actions\Action::make('resume')
                    ->label('استئناف')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('استئناف الاشتراك')
                    ->modalDescription('هل أنت متأكد من استئناف هذا الاشتراك؟')
                    ->action(function (AcademicSubscription $record) {
                        $record->update([
                            'status' => SessionSubscriptionStatus::ACTIVE,
                            'paused_at' => null,
                            'pause_reason' => null,
                        ]);
                    })
                    ->visible(fn (AcademicSubscription $record) => $record->status === SessionSubscriptionStatus::PAUSED),
                Tables\Actions\Action::make('extend_subscription')
                    ->label('تمديد الاشتراك')
                    ->icon('heroicon-o-calendar-plus')
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
                    ->action(function (array $data, AcademicSubscription $record) {
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
                    ->visible(fn (AcademicSubscription $record) => auth()->user()->hasRole(['super_admin', 'admin'])),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make()->label(__('filament.actions.restore')),
                Tables\Actions\ForceDeleteAction::make()->label(__('filament.actions.force_delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk cancel pending action (from HasSubscriptionActions trait)
                    static::getBulkCancelPendingAction(),
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make()->label(__('filament.actions.restore_selected')),
                    Tables\Actions\ForceDeleteBulkAction::make()->label(__('filament.actions.force_delete_selected')),
                ]),
            ])
            ->headerActions([
                // Header action to cancel all expired pending (from HasSubscriptionActions trait)
                static::getCancelExpiredPendingAction(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الاشتراك')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('subscription_code')
                                    ->label('رمز الاشتراك'),
                                Infolists\Components\TextEntry::make('package.name')
                                    ->label('اسم الباقة'),
                                Infolists\Components\TextEntry::make('student.name')
                                    ->label('الطالب'),
                                Infolists\Components\TextEntry::make('teacher.user.name')
                                    ->label('المعلم'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('حالة الاشتراك')
                                    ->badge()
                                    ->formatStateUsing(fn ($state): string => match ($state?->value ?? $state) {
                                        SessionSubscriptionStatus::PENDING->value => 'قيد الانتظار',
                                        SessionSubscriptionStatus::ACTIVE->value => 'نشط',
                                        SessionSubscriptionStatus::PAUSED->value => 'متوقف مؤقتاً',
                                        SessionSubscriptionStatus::CANCELLED->value => 'ملغي',
                                        default => (string) $state,
                                    })
                                    ->color(fn ($state): string => match ($state?->value ?? $state) {
                                        SessionSubscriptionStatus::ACTIVE->value => 'success',
                                        SessionSubscriptionStatus::PENDING->value => 'warning',
                                        SessionSubscriptionStatus::PAUSED->value => 'info',
                                        SessionSubscriptionStatus::CANCELLED->value => 'danger',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('payment_status')
                                    ->label('حالة الدفع')
                                    ->badge()
                                    ->formatStateUsing(fn ($state): string => match ($state?->value ?? $state) {
                                        SubscriptionPaymentStatus::PENDING->value => 'في الانتظار',
                                        SubscriptionPaymentStatus::PAID->value => 'مدفوع',
                                        SubscriptionPaymentStatus::FAILED->value => 'فشل',
                                        default => (string) $state,
                                    })
                                    ->color(fn ($state): string => match ($state?->value ?? $state) {
                                        SubscriptionPaymentStatus::PAID->value => 'success',
                                        SubscriptionPaymentStatus::PENDING->value => 'warning',
                                        SubscriptionPaymentStatus::FAILED->value => 'danger',
                                        default => 'gray',
                                    }),
                            ]),
                    ]),

                Infolists\Components\Section::make('سجل التمديدات')
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
                    ->visible(fn (AcademicSubscription $record) => !empty($record->metadata['extensions'])),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicSubscriptions::route('/'),
            'create' => Pages\CreateAcademicSubscription::route('/create'),
            'view' => Pages\ViewAcademicSubscription::route('/{record}'),
            'edit' => Pages\EditAcademicSubscription::route('/{record}/edit'),
        ];
    }
}
