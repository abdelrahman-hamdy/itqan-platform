<?php

namespace App\Filament\Resources;

use App\Enums\SessionDuration;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\TimeSlot;
use App\Enums\UserType;
use App\Enums\WeekDays;
use App\Filament\Resources\QuranSubscriptionResource\Pages;
use App\Filament\Shared\Traits\HasSubscriptionActions;
use App\Models\QuranSubscription;
use App\Models\SavedPaymentMethod;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuranSubscriptionResource extends BaseResource
{
    use HasSubscriptionActions;

    protected static ?string $model = QuranSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'اشتراكات القرآن';

    protected static ?string $modelLabel = 'اشتراك قرآن';

    protected static ?string $pluralModelLabel = 'اشتراكات القرآن';

    protected static ?string $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 6;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['student', 'quranTeacher', 'package', 'academy', 'individualCircle', 'quranCircle']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات الاشتراك الأساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('subscription_type')
                                    ->label('نوع الاشتراك')
                                    ->options([
                                        'individual' => 'فردي (جلسات خاصة)',
                                        'group' => 'جماعي (حلقة قرآنية)',
                                    ])
                                    ->default('individual')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        // Clear circle selection when type changes
                                        $set('quran_circle_id', null);
                                    })
                                    ->helperText('الاشتراك الفردي لجلسات خاصة، والجماعي للانضمام لحلقة'),

                                Select::make('quran_circle_id')
                                    ->label('الحلقة الجماعية')
                                    ->options(function () {
                                        $academyId = AcademyContextService::getCurrentAcademyId();

                                        return \App\Models\QuranCircle::where('academy_id', $academyId)
                                            ->where('status', true)
                                            ->get()
                                            ->mapWithKeys(fn ($circle) => [
                                                $circle->id => $circle->name.' ('.$circle->enrolled_students.'/'.$circle->max_students.')',
                                            ]);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn ($get) => $get('subscription_type') === 'group')
                                    ->required(fn ($get) => $get('subscription_type') === 'group')
                                    ->helperText('اختر الحلقة التي سينضم إليها الطالب'),

                                Select::make('student_id')
                                    ->label('الطالب')
                                    ->options(function () {
                                        return \App\Models\User::where('user_type', UserType::STUDENT->value)
                                            ->with('studentProfile')
                                            ->get()
                                            ->mapWithKeys(function ($user) {
                                                $studentCode = $user->studentProfile?->student_code ?? 'N/A';
                                                $fullName = $user->studentProfile?->full_name ?? $user->name;

                                                return [$user->id => $fullName.' ('.$studentCode.')'];
                                            });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('quran_teacher_id')
                                    ->label('معلم القرآن')
                                    ->options(function () {
                                        try {
                                            $academyId = AcademyContextService::getCurrentAcademyId();

                                            // Get teachers for current academy
                                            $teachers = \App\Models\QuranTeacherProfile::where('academy_id', $academyId)
                                                ->whereHas('user', fn ($q) => $q->where('active_status', true))
                                                ->get();

                                            if ($teachers->isEmpty()) {
                                                // Fallback: Get all active teachers if none found for current academy
                                                $teachers = \App\Models\QuranTeacherProfile::whereHas('user', fn ($q) => $q->where('active_status', true))->get();
                                            }

                                            if ($teachers->isEmpty()) {
                                                return ['0' => 'لا توجد معلمين نشطين'];
                                            }

                                            // Use user_id as key since quran_teacher_id is a User ID, not profile ID
                                            return $teachers->mapWithKeys(function ($teacher) {
                                                // Use display_name which already includes the code
                                                if ($teacher->display_name) {
                                                    return [$teacher->user_id => $teacher->display_name];
                                                }

                                                $fullName = $teacher->full_name ?? 'معلم غير محدد';
                                                $teacherCode = $teacher->teacher_code ?? 'N/A';

                                                return [$teacher->user_id => $fullName.' ('.$teacherCode.')'];
                                            })->toArray();

                                        } catch (\Exception $e) {
                                            \Log::error('Error loading teachers: '.$e->getMessage());

                                            return ['0' => 'خطأ في تحميل المعلمين'];
                                        }
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->visible(fn ($get) => $get('subscription_type') === 'individual')
                                    ->helperText('للاشتراكات الفردية فقط - الحلقات الجماعية لها معلم محدد'),

                                Select::make('package_id')
                                    ->label('الباقة')
                                    ->options(\App\Models\QuranPackage::where('is_active', true)
                                        ->orderBy('sort_order')
                                        ->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        if ($state) {
                                            $package = \App\Models\QuranPackage::find($state);
                                            if ($package) {
                                                $set('total_sessions', $package->sessions_per_month);
                                                // Use academy's configured currency instead of package's stored currency
                                                $set('currency', getCurrencyCode(null, $package->academy));
                                            }
                                        }
                                    }),
                            ]),
                    ]),

                Section::make('السعر والفوترة')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('total_price')
                                    ->label('سعر الاشتراك')
                                    ->numeric()
                                    ->prefix(getCurrencyCode())
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->helperText('يتم تحديده من سعر الباقة'),

                                Select::make('billing_cycle')
                                    ->label('دورة الفوترة')
                                    ->options([
                                        'monthly' => 'شهرية',
                                        'quarterly' => 'ربع سنوية (ثلاثة أشهر)',
                                        'yearly' => 'سنوية',
                                    ])
                                    ->default('monthly')
                                    ->required(),

                                DateTimePicker::make('starts_at')
                                    ->label('تاريخ البداية')
                                    ->required(),

                                DateTimePicker::make('ends_at')
                                    ->label('تاريخ انتهاء الاشتراك')
                                    ->native(false)
                                    ->helperText('يمكنك تعديل التاريخ يدوياً أو استخدام زر "تمديد الاشتراك" لإضافة أيام محددة')
                                    ->disabled(fn ($record) => ! $record) // Only editable when editing existing subscription
                                    ->dehydrated(true),

                                Toggle::make('auto_renew')
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
                                    }),
                            ]),
                    ]),

                Section::make('حالة الاشتراك')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->label('حالة الاشتراك')
                                    ->options([
                                        SessionSubscriptionStatus::PENDING->value => 'قيد الانتظار',
                                        SessionSubscriptionStatus::ACTIVE->value => 'نشط',
                                        SessionSubscriptionStatus::PAUSED->value => 'متوقف مؤقتاً',
                                        SessionSubscriptionStatus::CANCELLED->value => 'ملغي',
                                    ])
                                    ->default(SessionSubscriptionStatus::PENDING->value)
                                    ->helperText('ملاحظة: تمديد الاشتراك يقوم تلقائياً بتفعيله'),

                                Select::make('payment_status')
                                    ->label('حالة الدفع')
                                    ->options([
                                        SubscriptionPaymentStatus::PENDING->value => 'في الانتظار',
                                        SubscriptionPaymentStatus::PAID->value => 'مدفوع',
                                        SubscriptionPaymentStatus::FAILED->value => 'فشل',
                                    ])
                                    ->default(SubscriptionPaymentStatus::PENDING->value),
                            ]),
                    ])
                    ->visibleOn('edit'),

                Section::make('إعدادات الجلسات')
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
                    ->columns(3),

                Section::make('تفضيلات الطالب')
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
                    ->visible(fn ($get) => $get('subscription_type') === 'individual')
                    ->columns(2),

                Section::make('ملاحظات')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Textarea::make('admin_notes')
                                    ->label('ملاحظات الإدارة')
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->helperText('ملاحظات داخلية للإدارة فقط'),

                                Textarea::make('supervisor_notes')
                                    ->label('ملاحظات المشرف')
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->helperText('ملاحظات للمشرفين'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subscription_code')
                    ->label('رمز الاشتراك')
                    ->searchable()
                    ->fontFamily('mono')
                    ->weight(FontWeight::Bold),

                BadgeColumn::make('subscription_type')
                    ->label('نوع الاشتراك')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'individual' => 'فردي',
                        'group' => 'جماعي',
                        default => $state ?? '-',
                    })
                    ->colors([
                        'primary' => 'individual',
                        'success' => 'group',
                    ])
                    ->icons([
                        'heroicon-o-user' => 'individual',
                        'heroicon-o-user-group' => 'group',
                    ]),

                TextColumn::make('circle_name')
                    ->label('الحلقة')
                    ->getStateUsing(function ($record): string {
                        if ($record->subscription_type === 'individual') {
                            return $record->individualCircle?->name ?? 'لم يتم إنشاء الحلقة';
                        }

                        return $record->quranCircle?->name ?? 'لم يتم تحديد الحلقة';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function ($q) use ($search) {
                            $q->whereHas('individualCircle', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('quranCircle', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                        });
                    })
                    ->limit(25),

                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quranTeacher.full_name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('package.name')
                    ->label('اسم الباقة')
                    ->searchable()
                    ->limit(20),

                TextColumn::make('total_sessions')
                    ->label('إجمالي الجلسات')
                    ->alignCenter(),

                TextColumn::make('total_sessions_scheduled')
                    ->label('المجدولة')
                    ->alignCenter()
                    ->color('primary'),

                TextColumn::make('total_sessions_completed')
                    ->label('المكتملة')
                    ->alignCenter()
                    ->color('success'),

                TextColumn::make('total_sessions_missed')
                    ->label('الفائتة')
                    ->alignCenter()
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sessions_remaining')
                    ->label('المتبقية')
                    ->alignCenter()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 5 => 'success',
                        $state >= 2 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('total_price')
                    ->label('سعر الاشتراك')
                    ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR'))
                    ->sortable()
                    ->weight(FontWeight::Bold),

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

                TextColumn::make('end_date')
                    ->label('تاريخ الانتهاء')
                    ->date()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('subscription_type')
                    ->label('نوع الاشتراك')
                    ->options([
                        'individual' => 'فردي',
                        'group' => 'جماعي',
                    ])
                    ->native(false),

                SelectFilter::make('status')
                    ->label('حالة الاشتراك')
                    ->options([
                        'active' => 'نشط',
                        'pending_new' => 'قيد الانتظار - جديد (أقل من 48 ساعة)',
                        'pending_expired' => 'قيد الانتظار - منتهي (أكثر من 48 ساعة)',
                        'paused' => 'متوقف مؤقتاً',
                        'cancelled' => 'ملغي',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;

                        return match ($value) {
                            'active' => $query->where('status', SessionSubscriptionStatus::ACTIVE),
                            'pending_new' => $query->where('status', SessionSubscriptionStatus::PENDING)
                                ->where('payment_status', SubscriptionPaymentStatus::PENDING)
                                ->where('created_at', '>=', now()->subHours(48)),
                            'pending_expired' => $query->where('status', SessionSubscriptionStatus::PENDING)
                                ->where('payment_status', SubscriptionPaymentStatus::PENDING)
                                ->where('created_at', '<', now()->subHours(48)),
                            'paused' => $query->where('status', SessionSubscriptionStatus::PAUSED),
                            'cancelled' => $query->where('status', SessionSubscriptionStatus::CANCELLED),
                            default => $query,
                        };
                    }),

                SelectFilter::make('payment_status')
                    ->label('حالة الدفع')
                    ->options([
                        SubscriptionPaymentStatus::PENDING->value => 'في الانتظار',
                        SubscriptionPaymentStatus::PAID->value => 'مدفوع',
                        SubscriptionPaymentStatus::FAILED->value => 'فشل',
                    ]),

                SelectFilter::make('package_id')
                    ->label('الباقة')
                    ->relationship('package', 'name'),

                Filter::make('created_at')
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
                    }),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\Action::make('activate')
                        ->label('تفعيل')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (QuranSubscription $record) => $record->update([
                            'status' => SessionSubscriptionStatus::ACTIVE,
                            'payment_status' => SubscriptionPaymentStatus::PAID,
                            'last_payment_at' => now(),
                        ]))
                        ->visible(fn (QuranSubscription $record) => $record->status === SessionSubscriptionStatus::PENDING),
                    Tables\Actions\Action::make('pause')
                        ->label('إيقاف مؤقت')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->form([
                            Textarea::make('pause_reason')
                                ->label('سبب الإيقاف')
                                ->required(),
                        ])
                        ->action(function (QuranSubscription $record, array $data) {
                            $record->update([
                                'status' => SessionSubscriptionStatus::PAUSED,
                                'paused_at' => now(),
                                'pause_reason' => $data['pause_reason'],
                            ]);
                        })
                        ->visible(fn (QuranSubscription $record) => $record->status === SessionSubscriptionStatus::ACTIVE),
                    Tables\Actions\Action::make('resume')
                        ->label('استئناف')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (QuranSubscription $record) {
                            $record->update([
                                'status' => SessionSubscriptionStatus::ACTIVE,
                                'paused_at' => null,
                                'pause_reason' => null,
                            ]);
                        })
                        ->visible(fn (QuranSubscription $record) => $record->status === SessionSubscriptionStatus::PAUSED),
                    Tables\Actions\Action::make('cancel')
                        ->label('إلغاء')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Textarea::make('cancellation_reason')
                                ->label('سبب الإلغاء')
                                ->required(),
                        ])
                        ->action(function (QuranSubscription $record, array $data) {
                            $record->update([
                                'status' => SessionSubscriptionStatus::CANCELLED,
                                'cancelled_at' => now(),
                                'cancellation_reason' => $data['cancellation_reason'],
                                'auto_renew' => false,
                            ]);
                        })
                        ->visible(fn (QuranSubscription $record) => $record->status !== SessionSubscriptionStatus::CANCELLED),
                    Tables\Actions\Action::make('extend_subscription')
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
                        ->action(function (array $data, QuranSubscription $record) {
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
                        ->visible(fn (QuranSubscription $record) => auth()->user()->hasRole(['super_admin', 'admin'])),
                    // Cancel pending action (from HasSubscriptionActions trait)
                    static::getCancelPendingAction(),
                    Tables\Actions\DeleteAction::make()
                        ->label('حذف'),
                    Tables\Actions\RestoreAction::make()->label(__('filament.actions.restore')),
                    Tables\Actions\ForceDeleteAction::make()->label(__('filament.actions.force_delete')),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk cancel pending action (from HasSubscriptionActions trait)
                    static::getBulkCancelPendingAction(),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                    Tables\Actions\RestoreBulkAction::make()->label(__('filament.actions.restore_selected')),
                    Tables\Actions\ForceDeleteBulkAction::make()->label(__('filament.actions.force_delete_selected')),
                ]),
            ])
            ->headerActions([
                // Header action to cancel all expired pending (from HasSubscriptionActions trait)
                static::getCancelExpiredPendingAction(),
            ])
            ->defaultSort('created_at', 'desc');
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
                                Infolists\Components\TextEntry::make('quranTeacher.full_name')
                                    ->label('المعلم'),
                                Infolists\Components\TextEntry::make('subscription_type')
                                    ->label('نوع الاشتراك')
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'individual' => 'فردي',
                                        'group' => 'جماعي',
                                        default => $state ?? '-',
                                    })
                                    ->badge(),
                                Infolists\Components\TextEntry::make('billing_cycle')
                                    ->label('دورة الفوترة'),
                            ]),
                    ]),

                Infolists\Components\Section::make('الجلسات والأسعار')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_sessions')
                                    ->label('إجمالي الجلسات'),
                                Infolists\Components\TextEntry::make('total_sessions_scheduled')
                                    ->label('المجدولة'),
                                Infolists\Components\TextEntry::make('total_sessions_completed')
                                    ->label('المكتملة'),
                                Infolists\Components\TextEntry::make('total_sessions_missed')
                                    ->label('الفائتة'),
                                Infolists\Components\TextEntry::make('sessions_remaining')
                                    ->label('المتبقية')
                                    ->badge()
                                    ->color(fn ($state): string => match (true) {
                                        (int) $state >= 5 => 'success',
                                        (int) $state >= 2 => 'warning',
                                        default => 'danger',
                                    }),
                                Infolists\Components\TextEntry::make('total_price')
                                    ->label('السعر الإجمالي')
                                    ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR')),
                            ]),
                    ]),

                Infolists\Components\Section::make('تفضيلات الطالب')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('weekly_schedule.preferred_days')
                                    ->label('الأيام المفضلة')
                                    ->formatStateUsing(fn ($state) => is_array($state)
                                        ? collect($state)->map(fn ($day) => WeekDays::tryFrom($day)?->label() ?? $day)->implode('، ')
                                        : '-')
                                    ->placeholder('لم يتم تحديد الأيام'),
                                Infolists\Components\TextEntry::make('weekly_schedule.preferred_time')
                                    ->label('الفترة المفضلة')
                                    ->formatStateUsing(fn ($state) => TimeSlot::tryFrom($state)?->label() ?? '-')
                                    ->placeholder('لم يتم تحديد الفترة'),
                                Infolists\Components\TextEntry::make('student_notes')
                                    ->label('ملاحظات الطالب')
                                    ->columnSpanFull()
                                    ->placeholder('لا توجد ملاحظات'),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->subscription_type === 'individual'),

                Infolists\Components\Section::make('ملاحظات')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('admin_notes')
                                    ->label('ملاحظات الإدارة')
                                    ->placeholder('لا توجد ملاحظات'),
                                Infolists\Components\TextEntry::make('supervisor_notes')
                                    ->label('ملاحظات المشرف')
                                    ->placeholder('لا توجد ملاحظات'),
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
                    ->visible(fn (QuranSubscription $record) => ! empty($record->metadata['extensions'])),
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
            'index' => Pages\ListQuranSubscriptions::route('/'),
            'create' => Pages\CreateQuranSubscription::route('/create'),
            'view' => Pages\ViewQuranSubscription::route('/{record}'),
            'edit' => Pages\EditQuranSubscription::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // Use the scoped query from trait for consistent academy filtering
        $query = static::getEloquentQuery()->where('status', SessionSubscriptionStatus::PENDING->value);

        return $query->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}
