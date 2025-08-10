<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicSettingsResource\Pages;
use App\Models\AcademicSettings;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseSettingsResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\ScopedToAcademy;
use App\Services\AcademyContextService;

class AcademicSettingsResource extends BaseSettingsResource
{
    use ScopedToAcademy;

    protected static ?string $model = AcademicSettings::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationLabel = 'إعدادات القسم الأكاديمي';
    
    protected static ?string $navigationGroup = 'الإعدادات';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $modelLabel = 'إعدادات أكاديمية';
    
    protected static ?string $pluralModelLabel = 'إعدادات القسم الأكاديمي';

    // Note: getEloquentQuery() is now handled by ScopedToAcademy trait

    public static function shouldRegisterNavigation(): bool
    {
        return parent::shouldRegisterNavigation();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('الإعدادات العامة')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TagsInput::make('sessions_per_week_options')
                                    ->label('خيارات عدد الحصص الأسبوعية')
                                    ->placeholder('أضف خيار')
                                    ->helperText('الخيارات المتاحة للطلاب عند اختيار عدد الحصص (مثل: 1، 2، 3، 4)')
                                    ->required()
                                    ->default([1, 2, 3, 4]),

                                Forms\Components\TextInput::make('default_session_duration_minutes')
                                    ->label('مدة الحصة الافتراضية (دقيقة)')
                                    ->numeric()
                                    ->minValue(30)
                                    ->maxValue(180)
                                    ->default(60)
                                    ->required()
                                    ->suffix('دقيقة'),
                                    
                                Forms\Components\TextInput::make('default_booking_fee')
                                    ->label('رسوم الحجز الافتراضية')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->prefix('SAR'),

                                Forms\Components\Select::make('currency')
                                    ->label('العملة الافتراضية للأكاديمية')
                                    ->helperText('العملة المستخدمة في جميع الأسعار والمدفوعات')
                                    ->options([
                                        'SAR' => 'ريال سعودي (SAR)',
                                        'AED' => 'درهم إماراتي (AED)',
                                        'KWD' => 'دينار كويتي (KWD)',
                                        'QAR' => 'ريال قطري (QAR)',
                                        'BHD' => 'دينار بحريني (BHD)',
                                        'OMR' => 'ريال عماني (OMR)',
                                        'USD' => 'دولار أمريكي (USD)',
                                        'EUR' => 'يورو (EUR)',
                                    ])
                                    ->default('SAR')
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('إعدادات الحصة التجريبية')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('enable_trial_sessions')
                                    ->label('تفعيل الحصص التجريبية')
                                    ->helperText('السماح للمعلمين بتقديم حصص تجريبية')
                                    ->default(true)
                                    ->live(),

                                Forms\Components\TextInput::make('trial_session_duration_minutes')
                                    ->label('مدة الحصة التجريبية (دقيقة)')
                                    ->numeric()
                                    ->minValue(15)
                                    ->maxValue(60)
                                    ->default(30)
                                    ->suffix('دقيقة')
                                    ->visible(fn (Forms\Get $get) => $get('enable_trial_sessions')),

                                Forms\Components\TextInput::make('trial_session_fee')
                                    ->label('رسوم الحصة التجريبية')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->prefix('SAR')
                                    ->visible(fn (Forms\Get $get) => $get('enable_trial_sessions')),
                            ]),
                    ]),

                Forms\Components\Section::make('إعدادات الاشتراكات')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('subscription_pause_max_days')
                                    ->label('أقصى أيام إيقاف الاشتراك')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(90)
                                    ->default(30)
                                    ->suffix('يوم'),

                                Forms\Components\TextInput::make('auto_renewal_reminder_days')
                                    ->label('تذكير التجديد التلقائي (أيام)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(30)
                                    ->default(7)
                                    ->suffix('يوم'),

                                Forms\Components\Toggle::make('allow_mid_month_cancellation')
                                    ->label('السماح بالإلغاء في منتصف الشهر')
                                    ->helperText('إمكانية إلغاء الاشتراك في أي وقت خلال الشهر')
                                    ->default(false),
                            ]),
                    ]),

                Forms\Components\Section::make('إعدادات الدفع')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\CheckboxList::make('enabled_payment_methods')
                                    ->label('طرق الدفع المفعلة')
                                    ->options([
                                        'tab_pay' => 'Tab Pay',
                                        'paymob' => 'Paymob',
                                    ])
                                    ->default(['tab_pay', 'paymob'])
                                    ->required()
                                    ->columns(2),
                            ]),
                    ]),

                Forms\Components\Section::make('إعدادات Google Meet')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('auto_create_google_meet_links')
                                    ->label('إنشاء روابط Google Meet تلقائياً')
                                    ->helperText('إنشاء رابط Google Meet لكل جلسة تلقائياً')
                                    ->default(true)
                                    ->live(),

                                Forms\Components\TextInput::make('google_meet_account_email')
                                    ->label('البريد الإلكتروني لـ Google Meet')
                                    ->email()
                                    ->visible(fn (Forms\Get $get) => $get('auto_create_google_meet_links'))
                                    ->placeholder('academy@example.com'),
                            ]),
                    ]),

                Forms\Components\Section::make('إعدادات الدورات التفاعلية')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('courses_start_on_schedule')
                                    ->label('بدء الدورات حسب الجدول المحدد')
                                    ->helperText('الدورات تبدأ في التاريخ المحدد بغض النظر عن عدد المسجلين')
                                    ->default(true),

                                Forms\Components\TextInput::make('course_enrollment_deadline_days')
                                    ->label('موعد إغلاق التسجيل (أيام قبل البدء)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(30)
                                    ->default(3)
                                    ->suffix('يوم'),

                                Forms\Components\Toggle::make('allow_late_enrollment')
                                    ->label('السماح بالتسجيل المتأخر')
                                    ->helperText('إمكانية التسجيل حتى بعد بدء الدورة')
                                    ->default(false),
                            ]),
                    ]),

                Forms\Components\Section::make('إعدادات اللغات')
                    ->schema([
                        Forms\Components\CheckboxList::make('available_languages')
                            ->label('اللغات المتاحة للمعلمين')
                            ->options([
                                'arabic' => 'العربية',
                                'english' => 'الإنجليزية',
                                'french' => 'الفرنسية',
                                'german' => 'الألمانية',
                                'turkish' => 'التركية',
                                'spanish' => 'الإسبانية',
                                'chinese' => 'الصينية',
                                'japanese' => 'اليابانية',
                                'korean' => 'الكورية',
                                'italian' => 'الإيطالية',
                                'portuguese' => 'البرتغالية',
                                'russian' => 'الروسية',
                                'hindi' => 'الهندية',
                                'urdu' => 'الأردية',
                                'persian' => 'الفارسية',
                            ])
                            ->default(['arabic', 'english'])
                            ->columns(3)
                            ->helperText('اختر اللغات التي يمكن للمعلمين التدريس بها')
                            ->required(),
                    ]),

                Forms\Components\Section::make('ملاحظات إدارية')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        // This table won't be used since we're using ManageSettings page
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('academy.name')
                    ->label('الأكاديمية'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('معلومات الأكاديمية')
                    ->schema([
                        Components\TextEntry::make('academy.name')
                            ->label('الأكاديمية'),
                        Components\TextEntry::make('currency')
                            ->label('العملة'),
                    ])
                    ->columns(2),

                Components\Section::make('إعدادات الجلسات')
                    ->schema([
                        Components\TextEntry::make('sessions_per_week_options_text')
                            ->label('خيارات الحصص الأسبوعية'),
                        Components\TextEntry::make('default_session_duration_minutes')
                            ->label('مدة الحصة الافتراضية')
                            ->suffix(' دقيقة'),
                        Components\IconEntry::make('enable_trial_sessions')
                            ->label('الحصص التجريبية')
                            ->boolean(),
                        Components\TextEntry::make('trial_session_duration_minutes')
                            ->label('مدة الحصة التجريبية')
                            ->suffix(' دقيقة')
                            ->visible(fn ($record) => $record->enable_trial_sessions),
                    ])
                    ->columns(2),

                Components\Section::make('إعدادات الدفع')
                    ->schema([
                        Components\TextEntry::make('payment_methods_text')
                            ->label('طرق الدفع المفعلة'),
                    ])
                    ->columns(2),

                Components\Section::make('إعدادات متقدمة')
                    ->schema([
                        Components\IconEntry::make('auto_create_google_meet_links')
                            ->label('Google Meet التلقائي')
                            ->boolean(),
                        Components\TextEntry::make('google_meet_account_email')
                            ->label('البريد الإلكتروني للـ Google Meet')
                            ->placeholder('غير محدد'),
                        Components\IconEntry::make('courses_start_on_schedule')
                            ->label('بدء الدورات حسب الجدول')
                            ->boolean(),
                        Components\TextEntry::make('course_enrollment_deadline_days')
                            ->label('موعد إغلاق التسجيل')
                            ->suffix(' يوم قبل البدء'),
                    ])
                    ->columns(2),

                Components\Section::make('ملاحظات')
                    ->schema([
                        Components\TextEntry::make('notes')
                            ->label('ملاحظات إدارية')
                            ->placeholder('لا توجد ملاحظات'),
                        Components\TextEntry::make('updated_at')
                            ->label('آخر تحديث')
                            ->dateTime('Y-m-d H:i'),
                    ])
                    ->columns(2),
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
            'index' => Pages\ManageAcademicSettings::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return null; // Remove badge since we're dealing with single academy
    }

    public static function canCreate(): bool
    {
        return false; // Settings are auto-created
    }

    public static function canDeleteAny(): bool
    {
        return false; // Settings should not be deleted
    }

    public static function canDelete($record): bool
    {
        return false; // Settings should not be deleted
    }
}
