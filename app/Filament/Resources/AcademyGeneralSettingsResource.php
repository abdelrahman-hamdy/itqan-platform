<?php

namespace App\Filament\Resources;

use App\Enums\Country;
use App\Enums\Currency;
use App\Enums\NotificationCategory;
use App\Enums\TeachingLanguage;
use App\Enums\Timezone;
use App\Enums\UserType;
use App\Filament\Resources\AcademyGeneralSettingsResource\Pages;
use App\Models\AcademicPackage;
use App\Models\Academy;
use App\Models\QuranPackage;
use App\Services\AcademyContextService;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AcademyGeneralSettingsResource extends BaseResource
{
    protected static ?string $model = Academy::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'الإعدادات العامة';

    protected static ?string $modelLabel = 'إعدادات عامة';

    protected static ?string $pluralModelLabel = 'الإعدادات العامة';

    protected static ?string $navigationGroup = 'إدارة الأكاديميات';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->user_type === UserType::SUPER_ADMIN->value;
    }

    public static function canAccess(): bool
    {
        return static::hasSpecificAcademySelected();
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->user_type === UserType::SUPER_ADMIN->value;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    protected static function hasSpecificAcademySelected(): bool
    {
        $academyContextService = app(AcademyContextService::class);

        return $academyContextService->getCurrentAcademyId() !== null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Panel 0: Basic Academy Information
                Section::make('معلومات الأكاديمية')
                    ->description('الاسم والمعلومات الأساسية للأكاديمية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('اسم الأكاديمية (عربي)')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('الاسم الذي يظهر في الواجهة العربية'),

                                TextInput::make('name_en')
                                    ->label('Academy Name (English)')
                                    ->maxLength(255)
                                    ->helperText('The name displayed in the English interface'),
                            ]),
                    ])
                    ->collapsible(),

                // Panel 1: Regional Settings
                Section::make('الإعدادات الإقليمية')
                    ->description('إعدادات البلد والعملة والمنطقة الزمنية')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('country')
                                    ->label('الدولة')
                                    ->options(Country::toArray())
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->default(Country::SAUDI_ARABIA->value)
                                    ->enum(Country::class),

                                Select::make('currency')
                                    ->label('العملة')
                                    ->options(Currency::toArray())
                                    ->default(Currency::SAR->value)
                                    ->required()
                                    ->searchable()
                                    ->helperText('العملة المستخدمة في جميع المعاملات المالية')
                                    ->enum(Currency::class),

                                Select::make('timezone')
                                    ->label('المنطقة الزمنية')
                                    ->options(Timezone::toArray())
                                    ->default(Timezone::RIYADH->value)
                                    ->required()
                                    ->searchable()
                                    ->helperText('المنطقة الزمنية لجميع العمليات والجداول')
                                    ->enum(Timezone::class),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('الإعدادات الأكاديمية')
                    ->description('تخصيص الإعدادات الأكاديمية الخاصة بالمعلمين الجدد')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        CheckboxList::make('academic_settings.available_languages')
                            ->label('اللغات المتاحة للمعلمين')
                            ->options(TeachingLanguage::toArray())
                            ->columns(4)
                            ->default(TeachingLanguage::defaults())
                            ->helperText('اختر اللغات التي يمكن للمعلمين التدريس بها')
                            ->required(),

                        CheckboxList::make('academic_settings.default_package_ids')
                            ->label('الباقات الافتراضية للمعلمين الجدد')
                            ->options(function () {
                                $academyId = AcademyContextService::getCurrentAcademyId();

                                if (! $academyId) {
                                    return [];
                                }

                                return AcademicPackage::where('academy_id', $academyId)
                                    ->where('is_active', true)
                                    ->whereNotNull('name')
                                    ->where('name', '!=', '')
                                    ->orderBy('sort_order')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->default([])
                            ->helperText(function () {
                                $academyId = AcademyContextService::getCurrentAcademyId();

                                if (! $academyId) {
                                    return 'لا يمكن تحديد الأكاديمية.';
                                }

                                $count = AcademicPackage::where('academy_id', $academyId)
                                    ->where('is_active', true)
                                    ->whereNotNull('name')
                                    ->where('name', '!=', '')
                                    ->count();

                                if ($count === 0) {
                                    return 'لا توجد باقات أكاديمية متاحة. يرجى إضافة الباقات أولاً من قسم إدارة الباقات الأكاديمية.';
                                }

                                return "الباقات المختارة ستظهر تلقائياً في ملف المعلم الجديد. يوجد {$count} باقة متاحة للاختيار.";
                            })
                            ->columns(2),
                    ]),

                Section::make('إعدادات القرآن')
                    ->description('تخصيص الإعدادات الخاصة بمعلمي القرآن الجدد')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        CheckboxList::make('quran_settings.available_languages')
                            ->label('اللغات المتاحة لمعلمي القرآن')
                            ->options(TeachingLanguage::toArray())
                            ->columns(4)
                            ->default(TeachingLanguage::defaults())
                            ->helperText('اختر اللغات التي يمكن لمعلمي القرآن التدريس بها')
                            ->required(),

                        CheckboxList::make('quran_settings.default_package_ids')
                            ->label('الباقات الافتراضية لمعلمي القرآن الجدد')
                            ->options(function () {
                                $academyId = AcademyContextService::getCurrentAcademyId();

                                if (! $academyId) {
                                    return [];
                                }

                                return QuranPackage::where('academy_id', $academyId)
                                    ->where('is_active', true)
                                    ->whereNotNull('name')
                                    ->where('name', '!=', '')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->default([])
                            ->helperText(function () {
                                $academyId = AcademyContextService::getCurrentAcademyId();

                                if (! $academyId) {
                                    return 'لا يمكن تحديد الأكاديمية.';
                                }

                                $count = QuranPackage::where('academy_id', $academyId)
                                    ->where('is_active', true)
                                    ->whereNotNull('name')
                                    ->where('name', '!=', '')
                                    ->count();

                                if ($count === 0) {
                                    return 'لا توجد باقات قرآن متاحة. يرجى إضافة الباقات أولاً من قسم إدارة باقات القرآن.';
                                }

                                return "الباقات المختارة ستظهر تلقائياً في ملف معلم القرآن الجديد. يوجد {$count} باقة متاحة للاختيار.";
                            })
                            ->columns(2),
                    ]),

                Section::make('إعدادات الاجتماعات')
                    ->description('تحديد القيم الافتراضية لتوقيت فتح وإغلاق الاجتماعات وإدارة الانضمام المتأخر')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('meeting_settings.default_preparation_minutes')
                                    ->label('وقت تحضير الاجتماع (دقيقة)')
                                    ->helperText('الوقت قبل بداية الجلسة لإنشاء أو فتح الاجتماع بشكل افتراضي')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(60)
                                    ->default(10)
                                    ->required()
                                    ->suffix('دقيقة'),

                                TextInput::make('meeting_settings.default_late_tolerance_minutes')
                                    ->label('فترة السماح للانضمام المتأخر (دقيقة)')
                                    ->helperText('الوقت المسموح للطلاب للانضمام بعد بداية الجلسة دون اعتبارهم متأخرين')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(60)
                                    ->default(15)
                                    ->required()
                                    ->suffix('دقيقة'),

                                TextInput::make('meeting_settings.default_buffer_minutes')
                                    ->label('وقت إضافي بعد انتهاء الجلسة (دقيقة)')
                                    ->helperText('الوقت الإضافي لبقاء الاجتماع مفتوحاً بعد انتهاء الجلسة بشكل افتراضي')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(60)
                                    ->default(5)
                                    ->required()
                                    ->suffix('دقيقة'),
                            ]),
                    ]),

                // Review Settings
                Section::make('إعدادات التقييمات')
                    ->description('تخصيص إعدادات تقييمات المعلمين والدورات')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Toggle::make('academic_settings.auto_approve_reviews')
                            ->label('الموافقة التلقائية على التقييمات')
                            ->helperText('عند التفعيل، سيتم نشر التقييمات الجديدة تلقائياً دون الحاجة لمراجعة المشرف')
                            ->default(true),
                    ]),

                // Notifications & Email Settings
                Section::make('إعدادات الإشعارات والبريد الإلكتروني')
                    ->description('التحكم في إرسال الإشعارات عبر البريد الإلكتروني للمستخدمين')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Toggle::make('notification_settings.email_enabled')
                            ->label('تفعيل إرسال الإشعارات عبر البريد الإلكتروني')
                            ->helperText('عند التفعيل، سيتم إرسال الإشعارات المختارة أدناه عبر البريد الإلكتروني بالإضافة إلى الإشعارات داخل التطبيق')
                            ->default(false)
                            ->live(),

                        Placeholder::make('email_display')
                            ->label('البريد الإلكتروني المرسل منه')
                            ->content(fn ($record) => $record?->email ?? 'لم يتم تحديد البريد الإلكتروني')
                            ->helperText('يتم استخدام البريد الإلكتروني المسجل في معلومات الأكاديمية كعنوان المرسل')
                            ->visible(fn ($get) => $get('notification_settings.email_enabled')),

                        TextInput::make('notification_settings.email_from_name')
                            ->label('اسم المرسل')
                            ->helperText('الاسم الذي يظهر كمرسل البريد الإلكتروني (مثال: أكاديمية إتقان)')
                            ->maxLength(255)
                            ->placeholder('اسم الأكاديمية')
                            ->visible(fn ($get) => $get('notification_settings.email_enabled')),

                        CheckboxList::make('notification_settings.email_categories')
                            ->label('أنواع الإشعارات المرسلة عبر البريد الإلكتروني')
                            ->helperText('اختر أنواع الإشعارات التي ترغب بإرسالها عبر البريد الإلكتروني')
                            ->options([
                                NotificationCategory::SESSION->value => 'الجلسات (تذكير، بدء، انتهاء، إلغاء)',
                                NotificationCategory::ATTENDANCE->value => 'الحضور (تسجيل حضور، غياب، تأخر)',
                                NotificationCategory::HOMEWORK->value => 'الواجبات (تعيين، تسليم، تقييم)',
                                NotificationCategory::PAYMENT->value => 'المدفوعات (نجاح، فشل، اشتراكات)',
                                NotificationCategory::MEETING->value => 'الاجتماعات (جاهزية الغرفة)',
                                NotificationCategory::PROGRESS->value => 'التقدم (تقارير، شهادات، إنجازات)',
                                NotificationCategory::SYSTEM->value => 'النظام (تحديثات، صيانة)',
                                NotificationCategory::REVIEW->value => 'التقييمات (تقييمات جديدة)',
                                NotificationCategory::TRIAL->value => 'الجلسات التجريبية (طلبات، موافقة، تذكير)',
                                NotificationCategory::ALERT->value => 'التنبيهات العاجلة',
                            ])
                            ->columns(2)
                            ->default([
                                NotificationCategory::SESSION->value,
                                NotificationCategory::ATTENDANCE->value,
                                NotificationCategory::HOMEWORK->value,
                                NotificationCategory::PAYMENT->value,
                                NotificationCategory::PROGRESS->value,
                                NotificationCategory::SYSTEM->value,
                                NotificationCategory::TRIAL->value,
                                NotificationCategory::ALERT->value,
                            ])
                            ->visible(fn ($get) => $get('notification_settings.email_enabled')),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('الإعدادات الإقليمية')
                    ->description('إعدادات البلد والعملة والمنطقة الزمنية للأكاديمية')
                    ->schema([
                        TextEntry::make('name')
                            ->label('اسم الأكاديمية')
                            ->weight('bold'),

                        TextEntry::make('country')
                            ->label('الدولة')
                            ->formatStateUsing(function (?string $state): string {
                                if (! $state) {
                                    return '';
                                }

                                try {
                                    return Country::from($state)->label();
                                } catch (\ValueError $e) {
                                    return $state;
                                }
                            }),

                        TextEntry::make('currency')
                            ->label('العملة')
                            ->formatStateUsing(function (?string $state): string {
                                if (! $state) {
                                    return '';
                                }

                                try {
                                    return Currency::from($state)->label();
                                } catch (\ValueError $e) {
                                    return $state;
                                }
                            }),

                        TextEntry::make('timezone')
                            ->label('المنطقة الزمنية')
                            ->formatStateUsing(function (?string $state): string {
                                if (! $state) {
                                    return '';
                                }

                                try {
                                    return Timezone::from($state)->label();
                                } catch (\ValueError $e) {
                                    return $state;
                                }
                            }),
                    ])
                    ->columns(2),

                InfolistSection::make('معلومات إضافية')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime('Y-m-d H:i'),

                        TextEntry::make('updated_at')
                            ->label('آخر تحديث')
                            ->dateTime('Y-m-d H:i'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Academy::query()
                    ->when(
                        app(AcademyContextService::class)->getCurrentAcademyId(),
                        fn ($query, $academyId) => $query->where('id', $academyId)
                    )
            )
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الأكاديمية')
                    ->weight('bold'),

                TextColumn::make('country')
                    ->label('الدولة')
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return '';
                        }

                        try {
                            return Country::from($state)->label();
                        } catch (\ValueError $e) {
                            return $state;
                        }
                    }),

                TextColumn::make('currency')
                    ->label('العملة')
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return '';
                        }

                        try {
                            return Currency::from($state)->label();
                        } catch (\ValueError $e) {
                            return $state;
                        }
                    }),

                TextColumn::make('timezone')
                    ->label('المنطقة الزمنية')
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return '';
                        }

                        try {
                            return Timezone::from($state)->label();
                        } catch (\ValueError $e) {
                            return $state;
                        }
                    }),

                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime('Y-m-d H:i'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->paginated(false)
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAcademyGeneralSettings::route('/'),
            'edit' => Pages\EditGeneralSettings::route('/{record}/edit'),
        ];
    }

    /**
     * Override to prevent trying to load 'academy' relationship on Academy model
     */
    protected static function getAcademyRelationshipPath(): string
    {
        return ''; // Academy model doesn't have a relationship to itself
    }
}
