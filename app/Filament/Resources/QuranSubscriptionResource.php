<?php

namespace App\Filament\Resources;

use App\Enums\SubscriptionStatus;
use App\Filament\Resources\QuranSubscriptionResource\Pages;
use App\Models\QuranSubscription;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\ActionGroup;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use App\Services\AcademyContextService;
use App\Enums\SessionStatus;

class QuranSubscriptionResource extends BaseResource
{
    protected static ?string $model = QuranSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'اشتراكات القرآن';

    protected static ?string $modelLabel = 'اشتراك قرآن';

    protected static ?string $pluralModelLabel = 'اشتراكات القرآن';

    protected static ?string $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['student', 'quranTeacher', 'package', 'academy']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات الاشتراك الأساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('student_id')
                                    ->label('الطالب')
                                    ->options(function () {
                                        return \App\Models\User::where('user_type', 'student')
                                            ->with('studentProfile')
                                            ->get()
                                            ->mapWithKeys(function ($user) {
                                                $studentCode = $user->studentProfile?->student_code ?? 'N/A';
                                                $fullName = $user->studentProfile?->full_name ?? $user->name;
                                                return [$user->id => $fullName . ' (' . $studentCode . ')'];
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
                                                ->where('is_active', true)
                                                ->get();
                                            
                                            if ($teachers->isEmpty()) {
                                                // Fallback: Get all active teachers if none found for current academy
                                                $teachers = \App\Models\QuranTeacherProfile::where('is_active', true)->get();
                                            }
                                            
                                            if ($teachers->isEmpty()) {
                                                return ['0' => 'لا توجد معلمين نشطين'];
                                            }
                                            
                                            return $teachers->mapWithKeys(function ($teacher) {
                                                // Use display_name which already includes the code
                                                if ($teacher->display_name) {
                                                    return [$teacher->id => $teacher->display_name];
                                                }
                                                
                                                $fullName = $teacher->full_name ?? 'معلم غير محدد';
                                                $teacherCode = $teacher->teacher_code ?? 'N/A';
                                                return [$teacher->id => $fullName . ' (' . $teacherCode . ')'];
                                            })->toArray();
                                            
                                        } catch (\Exception $e) {
                                            \Log::error('Error loading teachers: ' . $e->getMessage());
                                            return ['0' => 'خطأ في تحميل المعلمين'];
                                        }
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('package_id')
                                    ->label('الباقة')
                                    ->options(\App\Models\QuranPackage::where('is_active', true)
                                        ->orderBy('sort_order')
                                        ->pluck('name_ar', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        if ($state) {
                                            $package = \App\Models\QuranPackage::find($state);
                                            if ($package) {
                                                $set('total_sessions', $package->sessions_per_month);
                                                $set('currency', $package->currency);
                                            }
                                        }
                                    }),
                            ]),
                    ]),

                Section::make('تفاصيل الجلسات والأسعار')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('total_sessions')
                                    ->label('إجمالي الجلسات')
                                    ->numeric()
                                    ->minValue(4)
                                    ->maxValue(32)
                                    ->required(),

                                TextInput::make('total_price')
                                    ->label('السعر الأصلي')
                                    ->numeric()
                                    ->prefix('SAR')
                                    ->minValue(0)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $discount = $get('discount_amount') ?: 0;
                                        $finalPrice = max(0, $state - $discount);
                                        $set('final_price', $finalPrice);
                                    }),

                                TextInput::make('discount_amount')
                                    ->label('مقدار الخصم')
                                    ->numeric()
                                    ->prefix('SAR')
                                    ->minValue(0)
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $total = $get('total_price') ?: 0;
                                        $finalPrice = max(0, $total - $state);
                                        $set('final_price', $finalPrice);
                                    }),

                                TextInput::make('final_price')
                                    ->label('السعر النهائي')
                                    ->numeric()
                                    ->prefix('SAR')
                                    ->disabled()
                                    ->dehydrated(),

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

                                Toggle::make('auto_renew')
                                    ->label('التجديد التلقائي')
                                    ->default(true),
                            ]),
                    ]),

                Section::make('حالة الاشتراك')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->label('حالة الاشتراك')
                                    ->options([
                                        SubscriptionStatus::ACTIVE->value => 'نشط',
                                        SubscriptionStatus::EXPIRED->value => 'منتهي',
                                        SubscriptionStatus::PAUSED->value => 'متوقف',
                                        SessionStatus::CANCELLED->value => 'ملغي',
                                        SubscriptionStatus::PENDING->value => 'في الانتظار',
                                        'suspended' => 'موقف',
                                    ])
                                    ->default(SubscriptionStatus::PENDING->value),

                                Select::make('payment_status')
                                    ->label('حالة الدفع')
                                    ->options([
                                        'paid' => 'مدفوع',
                                        SubscriptionStatus::PENDING->value => 'في الانتظار',
                                        'failed' => 'فشل',
                                        SubscriptionStatus::REFUNDED->value => 'مسترد',
                                        SessionStatus::CANCELLED->value => 'ملغي',
                                    ])
                                    ->default(SubscriptionStatus::PENDING->value),
                            ]),
                    ])
                    ->visibleOn('edit'),

                Section::make('التقدم والإحصائيات')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('sessions_used')
                                    ->label('الجلسات المستخدمة')
                                    ->numeric()
                                    ->disabled(),

                                TextInput::make('sessions_remaining')
                                    ->label('الجلسات المتبقية')
                                    ->numeric()
                                    ->disabled(),

                                TextInput::make('pages_memorized')
                                    ->label('الصفحات المحفوظة')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(604)
                                    ->default(0),

                                TextInput::make('progress_percentage')
                                    ->label('نسبة التقدم')
                                    ->numeric()
                                    ->suffix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(0),

                                Select::make('memorization_level')
                                    ->label('مستوى الحفظ')
                                    ->options([
                                        'beginner' => 'مبتدئ',
                                        'elementary' => 'أولي',
                                        'intermediate' => 'متوسط',
                                        'advanced' => 'متقدم',
                                        'expert' => 'خبير',
                                        'hafiz' => 'حافظ',
                                    ])
                                    ->default('beginner'),

                                TextInput::make('current_surah')
                                    ->label('السورة الحالية')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(114),

                                TextInput::make('current_page')
                                    ->label('الصفحة الحالية')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(604),
                            ]),
                    ])
                    ->visibleOn('edit'),

                Section::make('ملاحظات وتقييم')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Textarea::make('notes')
                                    ->label('ملاحظات')
                                    ->rows(3)
                                    ->maxLength(500),

                                TextInput::make('rating')
                                    ->label('التقييم')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(5)
                                    ->visible(fn (string $operation): bool => $operation === 'edit'),

                                Textarea::make('review_text')
                                    ->label('نص التقييم')
                                    ->rows(3)
                                    ->visible(fn (string $operation): bool => $operation === 'edit'),
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

                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quranTeacher.full_name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('package.name_ar')
                    ->label('اسم الباقة')
                    ->searchable()
                    ->limit(20),

                TextColumn::make('total_sessions')
                    ->label('إجمالي الجلسات')
                    ->alignCenter(),

                TextColumn::make('sessions_used')
                    ->label('المستخدمة')
                    ->alignCenter()
                    ->color('info'),

                TextColumn::make('sessions_remaining')
                    ->label('المتبقية')
                    ->alignCenter()
                    ->color('success'),

                TextColumn::make('total_price')
                    ->label('السعر الأصلي')
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('discount_amount')
                    ->label('الخصم')
                    ->money('SAR')
                    ->sortable()
                    ->default(0),

                TextColumn::make('final_price')
                    ->label('السعر النهائي')
                    ->money('SAR')
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->color('success'),

                BadgeColumn::make('status')
                    ->label('حالة الاشتراك')
                    ->formatStateUsing(fn ($state): string => match ($state instanceof \App\Enums\SubscriptionStatus ? $state->value : $state) {
                        SubscriptionStatus::ACTIVE->value => 'نشط',
                        SubscriptionStatus::EXPIRED->value => 'منتهي',
                        SubscriptionStatus::PAUSED->value => 'متوقف',
                        SessionStatus::CANCELLED->value => 'ملغي',
                        SubscriptionStatus::PENDING->value => 'في الانتظار',
                        'suspended' => 'موقف',
                        default => $state instanceof \App\Enums\SubscriptionStatus ? $state->value : (string) $state,
                    })
                    ->colors([
                        'success' => SubscriptionStatus::ACTIVE->value,
                        'danger' => fn ($state): bool => in_array($state instanceof \App\Enums\SubscriptionStatus ? $state->value : $state, [SubscriptionStatus::EXPIRED->value, 'suspended']),
                        'warning' => SubscriptionStatus::PAUSED->value,
                        'secondary' => SessionStatus::CANCELLED->value,
                        'info' => SubscriptionStatus::PENDING->value,
                    ]),

                BadgeColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->formatStateUsing(fn ($state): string => match ($state instanceof \App\Enums\SubscriptionPaymentStatus ? $state->value : $state) {
                        'paid' => 'مدفوع',
                        SubscriptionStatus::PENDING->value => 'في الانتظار',
                        'failed' => 'فشل',
                        SubscriptionStatus::REFUNDED->value => 'مسترد',
                        SessionStatus::CANCELLED->value => 'ملغي',
                        default => $state instanceof \App\Enums\SubscriptionPaymentStatus ? $state->value : (string) $state,
                    })
                    ->colors([
                        'success' => 'paid',
                        'warning' => SubscriptionStatus::PENDING->value,
                        'danger' => 'failed',
                        'info' => SubscriptionStatus::REFUNDED->value,
                        'secondary' => SessionStatus::CANCELLED->value,
                    ]),

                TextColumn::make('progress_percentage')
                    ->label('التقدم')
                    ->suffix('%')
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state >= 80 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('expires_at')
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
                SelectFilter::make('status')
                    ->label('حالة الاشتراك')
                    ->options([
                        SubscriptionStatus::ACTIVE->value => 'نشط',
                        SubscriptionStatus::EXPIRED->value => 'منتهي',
                        SubscriptionStatus::PAUSED->value => 'متوقف',
                        SessionStatus::CANCELLED->value => 'ملغي',
                        SubscriptionStatus::PENDING->value => 'في الانتظار',
                        'suspended' => 'موقف',
                    ]),

                SelectFilter::make('payment_status')
                    ->label('حالة الدفع')
                    ->options([
                        'paid' => 'مدفوع',
                        SubscriptionStatus::PENDING->value => 'في الانتظار',
                        'failed' => 'فشل',
                        SubscriptionStatus::REFUNDED->value => 'مسترد',
                        SessionStatus::CANCELLED->value => 'ملغي',
                    ]),

                SelectFilter::make('package_id')
                    ->label('الباقة')
                    ->relationship('package', 'name_ar'),

                Filter::make('expiring_soon')
                    ->label('تنتهي قريباً')
                    ->query(fn (Builder $query): Builder => $query->where('expires_at', '<=', now()->addDays(7))),

                Filter::make('trial_active')
                    ->label('التجريبية النشطة')
                    ->query(fn (Builder $query): Builder => $query->where('is_trial_active', true)),
            ])
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
                            'status' => SubscriptionStatus::ACTIVE,
                            'payment_status' => 'paid',
                            'last_payment_at' => now(),
                        ]))
                        ->visible(fn (QuranSubscription $record) => $record->status === SubscriptionStatus::PENDING),
                    Tables\Actions\Action::make('pause')
                        ->label('إيقاف مؤقت')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->form([
                            Textarea::make('pause_reason')
                                ->label('سبب الإيقاف')
                                ->required()
                        ])
                        ->action(function (QuranSubscription $record, array $data) {
                            $record->update([
                                'status' => SubscriptionStatus::PAUSED,
                                'paused_at' => now(),
                                'pause_reason' => $data['pause_reason'],
                            ]);
                        })
                        ->visible(fn (QuranSubscription $record) => $record->status === SubscriptionStatus::ACTIVE),
                    Tables\Actions\Action::make('cancel')
                        ->label('إلغاء')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Textarea::make('cancellation_reason')
                                ->label('سبب الإلغاء')
                                ->required()
                        ])
                        ->action(function (QuranSubscription $record, array $data) {
                            $record->update([
                                'status' => SessionStatus::CANCELLED->value,
                                'cancelled_at' => now(),
                                'cancellation_reason' => $data['cancellation_reason'],
                                'auto_renew' => false,
                            ]);
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->label('حذف'),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
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
                                Infolists\Components\TextEntry::make('package_name')
                                    ->label('اسم الباقة'),
                                Infolists\Components\TextEntry::make('student.name')
                                    ->label('الطالب'),
                                Infolists\Components\TextEntry::make('quranTeacher.full_name')
                                    ->label('المعلم'),
                                Infolists\Components\TextEntry::make('package_type')
                                    ->label('نوع الباقة')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('billing_cycle')
                                    ->label('دورة الفوترة'),
                            ]),
                    ]),

                Infolists\Components\Section::make('الجلسات والأسعار')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_sessions')
                                    ->label('إجمالي الجلسات'),
                                Infolists\Components\TextEntry::make('sessions_used')
                                    ->label('المستخدمة'),
                                Infolists\Components\TextEntry::make('sessions_remaining')
                                    ->label('المتبقية'),
                                Infolists\Components\TextEntry::make('total_price')
                                    ->label('السعر الإجمالي')
                                    ->money('SAR'),
                            ]),
                    ]),

                Infolists\Components\Section::make('التقدم والإحصائيات')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('pages_memorized')
                                    ->label('الصفحات المحفوظة'),
                                Infolists\Components\TextEntry::make('progress_percentage')
                                    ->label('نسبة التقدم')
                                    ->suffix('%'),
                                Infolists\Components\TextEntry::make('memorization_level')
                                    ->label('مستوى الحفظ')
                                    ->badge(),
                            ]),
                    ]),
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
        $query = static::getEloquentQuery()->where('status', SubscriptionStatus::PENDING->value);
        return $query->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
} 