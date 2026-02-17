<?php

namespace App\Filament\Resources;

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\UserType;
use App\Filament\Resources\QuranSubscriptionResource\Pages;
use App\Filament\Shared\Resources\BaseSubscriptionResource;
use App\Models\QuranSubscription;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuranSubscriptionResource extends BaseSubscriptionResource
{
    protected static ?string $model = QuranSubscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'اشتراكات القرآن';
    protected static ?string $modelLabel = 'اشتراك قرآن';
    protected static ?string $pluralModelLabel = 'اشتراكات القرآن';
    protected static ?string $navigationGroup = 'إدارة القرآن';
    protected static ?int $navigationSort = 6;

    protected static function getBasicInfoFormSection(): Section
    {
        return Section::make('معلومات الاشتراك الأساسية')
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
                                    $teachers = \App\Models\QuranTeacherProfile::where('academy_id', $academyId)
                                        ->whereHas('user', fn ($q) => $q->where('active_status', true))
                                        ->get();

                                    if ($teachers->isEmpty()) {
                                        $teachers = \App\Models\QuranTeacherProfile::whereHas('user', fn ($q) => $q->where('active_status', true))->get();
                                    }

                                    if ($teachers->isEmpty()) {
                                        return ['0' => 'لا توجد معلمين نشطين'];
                                    }

                                    return $teachers->mapWithKeys(function ($teacher) {
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
                                        $set('currency', getCurrencyCode(null, $package->academy));
                                    }
                                }
                            }),
                    ]),
            ]);
    }

    protected static function getPricingFormSection(): Section
    {
        return Section::make('السعر والفوترة')
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
                            ->disabled(fn ($record) => ! $record)
                            ->dehydrated(true),

                        static::getAutoRenewToggle(),
                    ]),
            ]);
    }

    protected static function getStatusSection(): Section
    {
        return Section::make('حالة الاشتراك')
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
            ->visibleOn('edit');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                static::getBasicInfoFormSection(),
                static::getPricingFormSection(),
                static::getStatusSection(),
                static::getSessionSettingsSection(),
                static::getStudentPreferencesSection()->visible(fn ($get) => $get('subscription_type') === 'individual'),
                static::getNotesSection(),
            ]);
    }

    protected static function getTypeSpecificTableColumns(): array
    {
        return [
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

            TextColumn::make('end_date')
                ->label('تاريخ الانتهاء')
                ->date()
                ->sortable(),
        ];
    }

    protected static function getTypeSpecificFilters(): array
    {
        return [
            SelectFilter::make('subscription_type')
                ->label('نوع الاشتراك')
                ->options([
                    'individual' => 'فردي',
                    'group' => 'جماعي',
                ])
                ->native(false),

            SelectFilter::make('package_id')
                ->label('الباقة')
                ->relationship('package', 'name'),
        ];
    }

    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['student', 'quranTeacher', 'package', 'academy', 'individualCircle', 'quranCircle']);
    }

    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                Tables\Actions\ViewAction::make()->label('عرض'),
                Tables\Actions\EditAction::make()->label('تعديل'),
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
                static::getPauseAction(),
                static::getResumeAction(),
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
                static::getExtendSubscriptionAction(),
                static::getCancelPendingAction(),
                Tables\Actions\DeleteAction::make()->label('حذف'),
                Tables\Actions\RestoreAction::make()->label(__('filament.actions.restore')),
                Tables\Actions\ForceDeleteAction::make()->label(__('filament.actions.force_delete')),
            ]),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                static::getBulkCancelPendingAction(),
                Tables\Actions\DeleteBulkAction::make()->label('حذف المحدد'),
                Tables\Actions\RestoreBulkAction::make()->label(__('filament.actions.restore_selected')),
                Tables\Actions\ForceDeleteBulkAction::make()->label(__('filament.actions.force_delete_selected')),
            ]),
        ];
    }

    protected static function getTypeSpecificInfolistSections(): array
    {
        return [
            Infolists\Components\Section::make('معلومات الاشتراك')
                ->schema([
                    Infolists\Components\Grid::make(2)
                        ->schema([
                            Infolists\Components\TextEntry::make('subscription_code')->label('رمز الاشتراك'),
                            Infolists\Components\TextEntry::make('package.name')->label('اسم الباقة'),
                            Infolists\Components\TextEntry::make('student.name')->label('الطالب'),
                            Infolists\Components\TextEntry::make('quranTeacher.full_name')->label('المعلم'),
                            Infolists\Components\TextEntry::make('subscription_type')
                                ->label('نوع الاشتراك')
                                ->formatStateUsing(fn (?string $state): string => match ($state) {
                                    'individual' => 'فردي',
                                    'group' => 'جماعي',
                                    default => $state ?? '-',
                                })
                                ->badge(),
                            Infolists\Components\TextEntry::make('billing_cycle')->label('دورة الفوترة'),
                        ]),
                ]),

            Infolists\Components\Section::make('الجلسات والأسعار')
                ->schema([
                    Infolists\Components\Grid::make(3)
                        ->schema([
                            Infolists\Components\TextEntry::make('total_sessions')->label('إجمالي الجلسات'),
                            Infolists\Components\TextEntry::make('total_sessions_scheduled')->label('المجدولة'),
                            Infolists\Components\TextEntry::make('total_sessions_completed')->label('المكتملة'),
                            Infolists\Components\TextEntry::make('total_sessions_missed')->label('الفائتة'),
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
                                    ? collect($state)->map(fn ($day) => \App\Enums\WeekDays::tryFrom($day)?->label() ?? $day)->implode('، ')
                                    : '-')
                                ->placeholder('لم يتم تحديد الأيام'),
                            Infolists\Components\TextEntry::make('weekly_schedule.preferred_time')
                                ->label('الفترة المفضلة')
                                ->formatStateUsing(fn ($state) => \App\Enums\TimeSlot::tryFrom($state)?->label() ?? '-')
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
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema(array_merge(
                static::getTypeSpecificInfolistSections(),
                [static::getExtensionHistoryInfolistSection()]
            ));
    }

    public static function getRelations(): array
    {
        return [];
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
        $query = static::getEloquentQuery()->where('status', SessionSubscriptionStatus::PENDING->value);

        return $query->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}
