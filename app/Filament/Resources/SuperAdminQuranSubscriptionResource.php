<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SuperAdminQuranSubscriptionResource\Pages;
use App\Models\QuranSubscription;
use App\Models\Academy;
use App\Models\QuranTeacherProfile;
use App\Models\QuranPackage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\ActionGroup;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;

class SuperAdminQuranSubscriptionResource extends Resource
{
    protected static ?string $model = QuranSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'اشتراكات القرآن (عالمي)';

    protected static ?string $modelLabel = 'اشتراك قرآن';

    protected static ?string $pluralModelLabel = 'اشتراكات القرآن';

    protected static ?string $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 3;

    // NO ACADEMY SCOPING - Show all subscriptions across all academies

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات الاشتراك')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('academy_id')
                                    ->label('الأكاديمية')
                                    ->options(Academy::pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                TextInput::make('subscription_code')
                                    ->label('رمز الاشتراك')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('student_id')
                                    ->label('الطالب')
                                    ->relationship('student', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('quran_teacher_id')
                                    ->label('المعلم')
                                    ->options(function () {
                                        return QuranTeacherProfile::with('academy')
                                            ->where('is_active', true)
                                            ->where('approval_status', 'approved')
                                            ->get()
                                            ->mapWithKeys(function ($teacher) {
                                                return [$teacher->id => "{$teacher->full_name} ({$teacher->academy->name})"];
                                            });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('package_id')
                                    ->label('الباقة')
                                    ->relationship('package', 'name')
                                    ->searchable()
                                    ->preload(),

                                Select::make('subscription_type')
                                    ->label('نوع الاشتراك')
                                    ->options([
                                        'private' => 'جلسات خاصة',
                                        'group' => 'جلسات جماعية',
                                    ])
                                    ->required()
                                    ->native(false),
                            ]),
                    ]),

                Section::make('التفاصيل المالية')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('total_price')
                                    ->label('السعر الإجمالي')
                                    ->numeric()
                                    ->prefix('ر.س')
                                    ->required(),

                                TextInput::make('discount_amount')
                                    ->label('قيمة الخصم')
                                    ->numeric()
                                    ->prefix('ر.س')
                                    ->default(0),

                                TextInput::make('final_price')
                                    ->label('السعر النهائي')
                                    ->numeric()
                                    ->prefix('ر.س')
                                    ->required(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('payment_status')
                                    ->label('حالة الدفع')
                                    ->options([
                                        'pending' => 'في الانتظار',
                                        'current' => 'مدفوع',
                                        'overdue' => 'متأخر',
                                        'failed' => 'فشل',
                                    ])
                                    ->required()
                                    ->native(false),

                                Select::make('subscription_status')
                                    ->label('حالة الاشتراك')
                                    ->options([
                                        'pending' => 'في الانتظار',
                                        'active' => 'نشط',
                                        'paused' => 'مؤقت',
                                        'expired' => 'منتهي',
                                        'cancelled' => 'ملغي',
                                    ])
                                    ->required()
                                    ->native(false),
                            ]),
                    ]),

                Section::make('الجلسات والتواريخ')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('total_sessions')
                                    ->label('إجمالي الجلسات')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1),

                                TextInput::make('sessions_used')
                                    ->label('الجلسات المستخدمة')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),

                                TextInput::make('sessions_remaining')
                                    ->label('الجلسات المتبقية')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('starts_at')
                                    ->label('تاريخ البداية')
                                    ->required()
                                    ->native(false),

                                DateTimePicker::make('expires_at')
                                    ->label('تاريخ الانتهاء')
                                    ->required()
                                    ->native(false),
                            ]),
                    ]),

                Section::make('تقدم الطالب')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('current_surah')
                                    ->label('رقم السورة الحالية')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(114),

                                TextInput::make('current_verse')
                                    ->label('رقم الآية الحالية')
                                    ->numeric()
                                    ->minValue(1),

                                TextInput::make('verses_memorized')
                                    ->label('عدد الآيات المحفوظة')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('memorization_level')
                                    ->label('مستوى الحفظ')
                                    ->options([
                                        'beginner' => 'مبتدئ',
                                        'intermediate' => 'متوسط',
                                        'advanced' => 'متقدم',
                                        'expert' => 'خبير',
                                    ])
                                    ->native(false),

                                TextInput::make('progress_percentage')
                                    ->label('نسبة التقدم (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%'),
                            ]),
                    ]),

                Section::make('معلومات إضافية')
                    ->schema([
                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3),

                        Toggle::make('auto_renew')
                            ->label('تجديد تلقائي')
                            ->default(false),
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
                    ->sortable()
                    ->copyable(),

                TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('quranTeacher.full_name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('package.name')
                    ->label('الباقة')
                    ->searchable(),

                BadgeColumn::make('subscription_status')
                    ->label('حالة الاشتراك')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'في الانتظار',
                        'active' => 'نشط',
                        'paused' => 'مؤقت',
                        'expired' => 'منتهي',
                        'cancelled' => 'ملغي',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'active',
                        'info' => 'paused',
                        'gray' => 'expired',
                        'danger' => 'cancelled',
                    ]),

                BadgeColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'في الانتظار',
                        'current' => 'مدفوع',
                        'overdue' => 'متأخر',
                        'failed' => 'فشل',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'current',
                        'danger' => ['overdue', 'failed'],
                    ]),

                TextColumn::make('sessions_progress')
                    ->label('تقدم الجلسات')
                    ->formatStateUsing(function ($record) {
                        return "{$record->sessions_used}/{$record->total_sessions}";
                    })
                    ->badge()
                    ->color('info'),

                TextColumn::make('final_price')
                    ->label('السعر النهائي')
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('progress_percentage')
                    ->label('نسبة التقدم')
                    ->formatStateUsing(fn ($state) => $state ? "{$state}%" : '-')
                    ->badge()
                    ->color('success'),

                TextColumn::make('starts_at')
                    ->label('تاريخ البداية')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                SelectFilter::make('academy_id')
                    ->label('الأكاديمية')
                    ->options(Academy::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('subscription_status')
                    ->label('حالة الاشتراك')
                    ->options([
                        'pending' => 'في الانتظار',
                        'active' => 'نشط',
                        'paused' => 'مؤقت',
                        'expired' => 'منتهي',
                        'cancelled' => 'ملغي',
                    ]),

                SelectFilter::make('payment_status')
                    ->label('حالة الدفع')
                    ->options([
                        'pending' => 'في الانتظار',
                        'current' => 'مدفوع',
                        'overdue' => 'متأخر',
                        'failed' => 'فشل',
                    ]),

                SelectFilter::make('quran_teacher_id')
                    ->label('المعلم')
                    ->options(function () {
                        return QuranTeacherProfile::with('academy')
                            ->where('is_active', true)
                            ->get()
                            ->mapWithKeys(function ($teacher) {
                                return [$teacher->id => "{$teacher->full_name} ({$teacher->academy->name})"];
                            });
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('subscription_type')
                    ->label('نوع الاشتراك')
                    ->options([
                        'private' => 'جلسات خاصة',
                        'group' => 'جلسات جماعية',
                    ]),

                Filter::make('expires_soon')
                    ->label('ينتهي قريباً (خلال 30 يوم)')
                    ->query(fn (Builder $query): Builder => $query->where('expires_at', '<=', now()->addDays(30))),

                Filter::make('active_subscriptions')
                    ->label('الاشتراكات النشطة فقط')
                    ->query(fn (Builder $query): Builder => $query->where('subscription_status', 'active')),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
                                    ->label('رمز الاشتراك')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('academy.name')
                                    ->label('الأكاديمية')
                                    ->badge()
                                    ->color('primary'),

                                Infolists\Components\TextEntry::make('student.name')
                                    ->label('الطالب'),

                                Infolists\Components\TextEntry::make('quranTeacher.full_name')
                                    ->label('المعلم'),

                                Infolists\Components\TextEntry::make('package.name')
                                    ->label('الباقة'),

                                Infolists\Components\TextEntry::make('subscription_type')
                                    ->label('نوع الاشتراك')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'private' => 'جلسات خاصة',
                                        'group' => 'جلسات جماعية',
                                        default => $state,
                                    }),
                            ])
                    ]),

                Infolists\Components\Section::make('الحالة والدفع')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('subscription_status')
                                    ->label('حالة الاشتراك')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'pending' => 'في الانتظار',
                                        'active' => 'نشط',
                                        'paused' => 'مؤقت',
                                        'expired' => 'منتهي',
                                        'cancelled' => 'ملغي',
                                        default => $state,
                                    })
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'active' => 'success',
                                        'paused' => 'info',
                                        'expired' => 'gray',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('payment_status')
                                    ->label('حالة الدفع')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'pending' => 'في الانتظار',
                                        'current' => 'مدفوع',
                                        'overdue' => 'متأخر',
                                        'failed' => 'فشل',
                                        default => $state,
                                    })
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'current' => 'success',
                                        'overdue', 'failed' => 'danger',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('final_price')
                                    ->label('السعر النهائي')
                                    ->money('SAR'),

                                Infolists\Components\TextEntry::make('discount_amount')
                                    ->label('قيمة الخصم')
                                    ->money('SAR'),
                            ])
                    ]),

                Infolists\Components\Section::make('الجلسات والتقدم')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_sessions')
                                    ->label('إجمالي الجلسات'),

                                Infolists\Components\TextEntry::make('sessions_used')
                                    ->label('الجلسات المستخدمة'),

                                Infolists\Components\TextEntry::make('sessions_remaining')
                                    ->label('الجلسات المتبقية'),

                                Infolists\Components\TextEntry::make('progress_percentage')
                                    ->label('نسبة التقدم')
                                    ->formatStateUsing(fn ($state) => $state ? "{$state}%" : '-'),

                                Infolists\Components\TextEntry::make('verses_memorized')
                                    ->label('عدد الآيات المحفوظة'),

                                Infolists\Components\TextEntry::make('memorization_level')
                                    ->label('مستوى الحفظ')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'beginner' => 'مبتدئ',
                                        'intermediate' => 'متوسط',
                                        'advanced' => 'متقدم',
                                        'expert' => 'خبير',
                                        default => $state,
                                    }),
                            ])
                    ]),

                Infolists\Components\Section::make('التواريخ المهمة')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('starts_at')
                                    ->label('تاريخ البداية')
                                    ->dateTime(),

                                Infolists\Components\TextEntry::make('expires_at')
                                    ->label('تاريخ الانتهاء')
                                    ->dateTime(),

                                Infolists\Components\TextEntry::make('last_session_at')
                                    ->label('آخر جلسة')
                                    ->dateTime(),

                                Infolists\Components\TextEntry::make('next_payment_at')
                                    ->label('الدفعة التالية')
                                    ->dateTime(),
                            ])
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuperAdminQuranSubscriptions::route('/'),
            'view' => Pages\ViewSuperAdminQuranSubscription::route('/{record}'),
            'edit' => Pages\EditSuperAdminQuranSubscription::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Only super admin can access global Quran subscription management
        return \App\Services\AcademyContextService::isSuperAdmin();
    }
}