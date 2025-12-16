<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuranTeacherProfileResource\Pages;
use App\Models\QuranTeacherProfile;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Services\AcademyContextService;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\BadgeColumn;

class QuranTeacherProfileResource extends BaseResource
{

    protected static ?string $model = QuranTeacherProfile::class;
    
    protected static ?string $tenantOwnershipRelationshipName = 'user';

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'معلمو القرآن';

    protected static ?string $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 0;

    protected static ?string $modelLabel = 'معلم قرآن';

    protected static ?string $pluralModelLabel = 'معلمو القرآن';

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy'; // QuranTeacherProfile -> Academy (direct relationship)
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الشخصية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('first_name')
                                    ->label('الاسم الأول')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('last_name')
                                    ->label('الاسم الأخير')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->helperText('سيستخدم المعلم هذا البريد للدخول إلى المنصة'),
                                Forms\Components\TextInput::make('phone')
                                    ->label('رقم الهاتف')
                                    ->tel()
                                    ->maxLength(20),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('password')
                                    ->label('كلمة المرور')
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->minLength(8)
                                    ->maxLength(255)
                                    ->helperText('سيتم إنشاء حساب تلقائياً للمعلم باستخدام هذه الكلمة. الحد الأدنى 8 أحرف.')
                                    ->visible(fn ($record) => !$record || !$record->user_id),
                                Forms\Components\TextInput::make('password_confirmation')
                                    ->label('تأكيد كلمة المرور')
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(false)
                                    ->required(fn (string $context, $get): bool => $context === 'create' && filled($get('password')))
                                    ->same('password')
                                    ->maxLength(255)
                                    ->visible(fn ($record) => !$record || !$record->user_id),
                            ]),

                        Forms\Components\FileUpload::make('avatar')
                            ->label('الصورة الشخصية')
                            ->image()
                            ->imageEditor()
                            ->circleCropper()
                            ->directory('avatars/quran-teachers')
                            ->maxSize(2048),
                    ]),

                Forms\Components\Section::make('المؤهلات والخبرة')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('educational_qualification')
                                    ->label('المؤهل التعليمي')
                                    ->options([
                                        'bachelor' => 'بكالوريوس',
                                        'master' => 'ماجستير',
                                        'phd' => 'دكتوراه',
                                        'diploma' => 'دبلوم',
                                        'other' => 'أخرى',
                                    ])
                                    ->default('bachelor')
                                    ->required(),
                                Forms\Components\TextInput::make('teaching_experience_years')
                                    ->label('سنوات الخبرة في تدريس القرآن')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(50)
                                    ->default(0),
                            ]),
                        Forms\Components\TagsInput::make('certifications')
                            ->label('الشهادات والإجازات')
                            ->placeholder('أضف شهادة أو إجازة')
                            ->helperText('مثل: إجازة في القراءات، شهادة تجويد، إلخ'),
                        Forms\Components\CheckboxList::make('languages')
                            ->label('اللغات التي يجيدها')
                            ->options(function () {
                                $academyId = \App\Services\AcademyContextService::getCurrentAcademy()?->id;
                                if (!$academyId) {
                                    $availableLanguages = ['arabic', 'english'];
                                } else {
                                    $settings = \App\Models\AcademicSettings::getForAcademy($academyId);
                                    $availableLanguages = $settings->available_languages ?? ['arabic', 'english'];
                                }
                                
                                $languageNames = [
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
                                ];
                                
                                return collect($availableLanguages)
                                    ->mapWithKeys(fn($lang) => [$lang => $languageNames[$lang] ?? $lang])
                                    ->toArray();
                            })
                            ->default(['arabic'])
                            ->columns(2),
                    ]),

                Forms\Components\Section::make('الأوقات المتاحة')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('available_time_start')
                                    ->label('وقت البدء')
                                    ->default('08:00')
                                    ->required(),
                                Forms\Components\TimePicker::make('available_time_end')
                                    ->label('وقت الانتهاء')
                                    ->default('18:00')
                                    ->required(),
                            ]),
                        Forms\Components\CheckboxList::make('available_days')
                            ->label('الأيام المتاحة')
                            ->options([
                                'sunday' => 'الأحد',
                                'monday' => 'الاثنين',
                                'tuesday' => 'الثلاثاء',
                                'wednesday' => 'الأربعاء',
                                'thursday' => 'الخميس',
                                'friday' => 'الجمعة',
                                'saturday' => 'السبت',
                            ])
                            ->columns(2)
                            ->required(),
                    ]),

                Forms\Components\Section::make('الأسعار والرسوم')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('session_price_individual')
                                    ->label('سعر الحصة الفردية')
                                    ->numeric()
                                    ->prefix('ر.س')
                                    ->minValue(0)
                                    ->step(5)
                                    ->default(50)
                                    ->helperText('سعر الحصة الواحدة للطالب الواحد'),
                                Forms\Components\TextInput::make('session_price_group')
                                    ->label('سعر الحصة الجماعية')
                                    ->numeric()
                                    ->prefix('ر.س')
                                    ->minValue(0)
                                    ->step(5)
                                    ->default(30)
                                    ->helperText('سعر الحصة الجماعية كاملة بغض النظر عن عدد الطلاب في الحلقة'),
                            ]),
                    ]),

                Forms\Components\Section::make('السيرة الذاتية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('bio_arabic')
                                    ->label('السيرة الذاتية (عربي)')
                                    ->maxLength(1000)
                                    ->rows(4)
                                    ->helperText('اكتب نبذة عن خبرتك في تدريس القرآن الكريم'),
                                Forms\Components\Textarea::make('bio_english')
                                    ->label('السيرة الذاتية (إنجليزي)')
                                    ->maxLength(1000)
                                    ->rows(4),
                            ]),
                    ]),

                Forms\Components\Section::make('الحالة')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true),
                        Forms\Components\Select::make('approval_status')
                            ->label('حالة الموافقة')
                            ->options([
                                'pending' => 'قيد الانتظار',
                                'approved' => 'موافق عليه',
                                'rejected' => 'مرفوض',
                            ])
                            ->default('pending')
                            ->required()
                            ->helperText('يجب أن يكون المعلم موافق عليه ونشط ليظهر للطلاب'),
                        Forms\Components\Toggle::make('offers_trial_sessions')
                            ->label('يقدم جلسات تجريبية')
                            ->default(true)
                            ->helperText('عند تفعيل هذا الخيار، سيتمكن الطلاب من طلب جلسات تجريبية مع هذا المعلم'),
                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('تاريخ الموافقة')
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->approval_status === 'approved'),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات إدارية')
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('ملاحظات إدارية حول المدرس'),
                    ])
                    ->columns(2)
                    ->visible(fn () => Auth::check() && Auth::user()->isAdmin()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getAcademyColumn(), // Add academy column when viewing all academies
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('الصورة')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->full_name) . '&background=4169E1&color=fff'),

                Tables\Columns\TextColumn::make('teacher_code')
                    ->label('رمز المعلم')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('اسم المعلم')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\IconColumn::make('user_id')
                    ->label('مربوط بحساب')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\BadgeColumn::make('is_active')
                    ->label('نشط')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'نشط' : 'غير نشط')
                    ->colors([
                        'success' => true,
                        'gray' => false,
                    ]),

                Tables\Columns\BadgeColumn::make('approval_status')
                    ->label('حالة الموافقة')
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'approved' => 'موافق عليه',
                        'pending' => 'قيد الانتظار',
                        'rejected' => 'مرفوض',
                        default => 'قيد الانتظار'
                    })
                    ->colors([
                        'success' => 'approved',
                        'warning' => 'pending',
                        'danger' => 'rejected',
                    ])
                    ->icon(fn (?string $state): string => match($state) {
                        'approved' => 'heroicon-o-check-circle',
                        'pending' => 'heroicon-o-clock',
                        'rejected' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle'
                    }),

                Tables\Columns\BadgeColumn::make('offers_trial_sessions')
                    ->label('الجلسات التجريبية')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'متاحة' : 'غير متاحة')
                    ->colors([
                        'success' => true,
                        'gray' => false,
                    ]),

                Tables\Columns\TextColumn::make('total_students')
                    ->label('عدد الطلاب')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_sessions')
                    ->label('عدد الجلسات')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('التقييم')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        return str_repeat('⭐', round($state)) . " ({$state}/5)";
                    }),

                Tables\Columns\TextColumn::make('languages')
                    ->label('اللغات')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state)) return '-';
                        $languageNames = [
                            'arabic' => 'العربية',
                            'english' => 'الإنجليزية',
                            'french' => 'الفرنسية',
                            'urdu' => 'الأردو',
                            'turkish' => 'التركية',
                            'malay' => 'الماليزية',
                        ];
                        return collect($state)->map(fn($lang) => $languageNames[$lang] ?? $lang)->implode(', ');
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('certifications')
                    ->label('الشهادات')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state)) return '-';
                        return collect($state)->take(2)->implode(', ') . (count($state) > 2 ? '...' : '');
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('educational_qualification')
                    ->label('المؤهل التعليمي')
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'bachelor' => 'بكالوريوس',
                            'master' => 'ماجستير',
                            'phd' => 'دكتوراه',
                            'diploma' => 'دبلوم',
                            'other' => 'أخرى',
                            default => $state,
                        };
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('teaching_experience_years')
                    ->label('سنوات الخبرة')
                    ->numeric()
                    ->suffix(' سنة')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('approval_status')
                    ->label('حالة الموافقة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'approved' => 'موافق عليه',
                        'rejected' => 'مرفوض',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('نشط'),
                Tables\Filters\TernaryFilter::make('offers_trial_sessions')
                    ->label('الجلسات التجريبية')
                    ->trueLabel('متاحة')
                    ->falseLabel('غير متاحة'),
                Tables\Filters\TernaryFilter::make('user_id')
                    ->label('مربوط بحساب')
                    ->nullable()
                    ->trueLabel('مربوط')
                    ->falseLabel('غير مربوط'),
                Tables\Filters\SelectFilter::make('educational_qualification')
                    ->label('المؤهل التعليمي')
                    ->options([
                        'bachelor' => 'بكالوريوس',
                        'master' => 'ماجستير',
                        'phd' => 'دكتوراه',
                        'diploma' => 'دبلوم',
                        'other' => 'أخرى',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('approve')
                    ->label('موافقة')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => $record->approval_status !== 'approved')
                    ->requiresConfirmation()
                    ->modalHeading('الموافقة على المعلم')
                    ->modalDescription('هل أنت متأكد من الموافقة على هذا المعلم؟')
                    ->action(function ($record) {
                        $record->update([
                            'approval_status' => 'approved',
                            'approved_by' => auth()->user()->id,
                            'approved_at' => now(),
                        ]);
                    })
                    ->successNotificationTitle('تمت الموافقة على المعلم بنجاح'),

                Tables\Actions\Action::make('activate')
                    ->label('تفعيل')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_active)
                    ->requiresConfirmation()
                    ->modalHeading('تفعيل المعلم')
                    ->modalDescription('هل أنت متأكد من تفعيل هذا المعلم؟ سيتم تفعيل حسابه والموافقة عليه.')
                    ->action(function ($record) {
                        $record->activate(auth()->user()->id);
                    })
                    ->successNotificationTitle('تم تفعيل المعلم بنجاح'),

                Tables\Actions\Action::make('deactivate')
                    ->label('إلغاء تفعيل')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->is_active)
                    ->requiresConfirmation()
                    ->modalHeading('إلغاء تفعيل المعلم')
                    ->modalDescription('هل أنت متأكد من إلغاء تفعيل هذا المعلم؟ سيتم إلغاء تفعيل حسابه.')
                    ->action(function ($record) {
                        $record->deactivate();
                    })
                    ->successNotificationTitle('تم إلغاء تفعيل المعلم بنجاح'),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranTeacherProfiles::route('/'),
            'create' => Pages\CreateQuranTeacherProfile::route('/create'),
            'view' => Pages\ViewQuranTeacherProfile::route('/{record}'),
            'edit' => Pages\EditQuranTeacherProfile::route('/{record}/edit'),
        ];
    }
}
