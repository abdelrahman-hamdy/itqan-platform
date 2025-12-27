<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicTeacherProfileResource\Pages;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Services\AcademyContextService;
use Filament\Notifications\Notification;
use App\Enums\SubscriptionStatus;
use App\Enums\EducationalQualification;

class AcademicTeacherProfileResource extends BaseResource
{

    protected static ?string $model = AcademicTeacherProfile::class;
    
    protected static ?string $tenantOwnershipRelationshipName = 'user';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'المدرسين الأكاديميين';

    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'مدرس أكاديمي';

    protected static ?string $pluralModelLabel = 'المدرسين الأكاديميين';

    protected static function getAcademyRelationshipPath(): string
    {
        return 'user.academy'; // AcademicTeacherProfile -> User -> Academy
    }

    // Note: getEloquentQuery() is now handled by ScopedToAcademyViaRelationship trait

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الشخصية')
                    ->schema([
                        // Academy selection field for super admin when in global view or creating new records
                        Forms\Components\Select::make('academy_id')
                            ->label('الأكاديمية')
                            ->options(Academy::active()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(fn () => AcademyContextService::getCurrentAcademy()?->id)
                            ->visible(function () {
                                $user = auth()->user();
                                return $user && $user->isSuperAdmin() && !AcademyContextService::getCurrentAcademy();
                            })
                            ->dehydrated(true) // CRITICAL: Always include in form data even when hidden
                            ->helperText('حدد الأكاديمية التي سينتمي إليها هذا المدرس')
                            ->live(), // Make it reactive so subjects and grade levels update when changed
                        
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
                                Forms\Components\Select::make('gender')
                                    ->label('الجنس')
                                    ->options([
                                        'male' => 'معلم',
                                        'female' => 'معلمة',
                                    ])
                                    ->required()
                                    ->native(false),
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
                            ->directory('avatars/academic-teachers')
                            ->maxSize(2048),
                    ]),

                Forms\Components\Section::make('المؤهلات التعليمية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('education_level')
                                    ->label('المؤهل التعليمي')
                                    ->options(EducationalQualification::options())
                                    ->default(EducationalQualification::BACHELOR->value)
                                    ->required(),
                                Forms\Components\TextInput::make('university')
                                    ->label('الجامعة')
                                    ->maxLength(255),
                            ]),
                        Forms\Components\TextInput::make('teaching_experience_years')
                            ->label('سنوات الخبرة التدريسية')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(50)
                            ->default(0),
                        Forms\Components\TagsInput::make('certifications')
                            ->label('الشهادات والدورات')
                            ->placeholder('أضف شهادة')
                            ->helperText('اضغط Enter لإضافة شهادة جديدة'),
                        Forms\Components\CheckboxList::make('languages')
                            ->label('اللغات')
                            ->options(function () {
                                $academyId = AcademyContextService::getCurrentAcademy()?->id;
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
                                
                                return array_intersect_key($languageNames, array_flip($availableLanguages));
                            })
                            ->default(['arabic'])
                            ->columns(3),
                    ]),

                Forms\Components\Section::make('التخصص')
                    ->schema([
                        Forms\Components\Select::make('subject_ids')
                            ->label('المواد التي يقوم بتدريسها')
                            ->options(function (Forms\Get $get, ?AcademicTeacherProfile $record) {
                                // Get academy_id from the record being edited, or from the form data for new records
                                $academyId = $record?->academy_id ?? $get('academy_id') ?? AcademyContextService::getCurrentAcademy()?->id;
                                
                                if (!$academyId) {
                                    return [];
                                }
                                
                                return \App\Models\AcademicSubject::where('academy_id', $academyId)
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText(function (Forms\Get $get, ?AcademicTeacherProfile $record) {
                                // Get academy_id from the record being edited, or from the form data for new records
                                $academyId = $record?->academy_id ?? $get('academy_id') ?? AcademyContextService::getCurrentAcademy()?->id;
                                
                                if (!$academyId) {
                                    return 'لا يمكن تحديد الأكاديمية. يرجى تحديد الأكاديمية أولاً.';
                                }
                                
                                $count = \App\Models\AcademicSubject::where('academy_id', $academyId)->where('is_active', true)->count();
                                if ($count === 0) {
                                    return 'لا توجد مواد دراسية متاحة في هذه الأكاديمية. يرجى إضافة المواد الدراسية أولاً.';
                                }
                                
                                return "يوجد {$count} مادة دراسية متاحة للاختيار";
                            })
                            ->columns(2),
                        Forms\Components\Select::make('grade_level_ids')
                            ->label('الصفوف الدراسية')
                            ->options(function (Forms\Get $get, ?AcademicTeacherProfile $record) {
                                // Get academy_id from the record being edited, or from the form data for new records
                                $academyId = $record?->academy_id ?? $get('academy_id') ?? AcademyContextService::getCurrentAcademy()?->id;
                                
                                if (!$academyId) {
                                    return [];
                                }
                                
                                return \App\Models\AcademicGradeLevel::where('academy_id', $academyId)
                                    ->where('is_active', true)
                                    ->whereNotNull('name')
                                    ->where('name', '!=', '')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText(function (Forms\Get $get, ?AcademicTeacherProfile $record) {
                                // Get academy_id from the record being edited, or from the form data for new records
                                $academyId = $record?->academy_id ?? $get('academy_id') ?? AcademyContextService::getCurrentAcademy()?->id;
                                
                                if (!$academyId) {
                                    return 'لا يمكن تحديد الأكاديمية. يرجى تحديد الأكاديمية أولاً.';
                                }
                                
                                $count = \App\Models\AcademicGradeLevel::where('academy_id', $academyId)
                                    ->where('is_active', true)
                                    ->whereNotNull('name')
                                    ->where('name', '!=', '')
                                    ->count();
                                if ($count === 0) {
                                    return 'لا توجد صفوف دراسية متاحة في هذه الأكاديمية. يرجى إضافة الصفوف الدراسية أولاً.';
                                }
                                
                                return "يوجد {$count} صف دراسي متاح للاختيار";
                            })
                            ->columns(2),
                        Forms\Components\CheckboxList::make('package_ids')
                            ->label('الباقات التي يمكن تدريسها')
                            ->options(function (Forms\Get $get, ?AcademicTeacherProfile $record) {
                                // Get academy_id from the record being edited, or from the form data for new records
                                $academyId = $record?->academy_id ?? $get('academy_id') ?? AcademyContextService::getCurrentAcademy()?->id;
                                
                                if (!$academyId) {
                                    return [];
                                }
                                
                                $packages = \App\Models\AcademicPackage::where('academy_id', $academyId)
                                    ->where('is_active', true)
                                    ->orderBy('sort_order')
                                    ->orderBy('name_ar')
                                    ->pluck('name_ar', 'id')
                                    ->toArray();
                                
                                return $packages;
                            })
                            ->helperText(function (Forms\Get $get, ?AcademicTeacherProfile $record) {
                                // Get academy_id from the record being edited, or from the form data for new records
                                $academyId = $record?->academy_id ?? $get('academy_id') ?? AcademyContextService::getCurrentAcademy()?->id;
                                
                                if (!$academyId) {
                                    return 'لا يمكن تحديد الأكاديمية. يرجى تحديد الأكاديمية أولاً.';
                                }
                                
                                $count = \App\Models\AcademicPackage::where('academy_id', $academyId)->where('is_active', true)->count();
                                if ($count === 0) {
                                    return 'لا توجد باقات أكاديمية متاحة في هذه الأكاديمية. يرجى إضافة الباقات أولاً من قسم إدارة الباقات الأكاديمية.';
                                }
                                
                                return "يوجد {$count} باقة أكاديمية متاحة للاختيار";
                            })
                            ->columns(2),
                    ]),

                Forms\Components\Section::make('الأوقات المتاحة والأسعار')
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
                            ->options(\App\Enums\WeekDays::options())
                            ->columns(3)
                            ->required(),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('session_price_individual')
                                    ->label('سعر الحصة الفردية')
                                    ->numeric()
                                    ->prefix('ر.س')
                                    ->minValue(0)
                                    ->step(5)
                                    ->default(100),
                            ]),
                    ]),

                Forms\Components\Section::make('السيرة الذاتية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('bio_arabic')
                                    ->label('السيرة الذاتية (عربي)')
                                    ->maxLength(1000)
                                    ->rows(4),
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
                            ->default(true)
                            ->helperText('تفعيل أو إلغاء تفعيل المدرس'),
                        Forms\Components\Select::make('approval_status')
                            ->label('حالة الموافقة')
                            ->options([
                                SubscriptionStatus::PENDING->value => 'قيد الانتظار',
                                'approved' => 'موافق عليه',
                                'rejected' => 'مرفوض',
                            ])
                            ->default(SubscriptionStatus::PENDING->value)
                            ->required()
                            ->helperText('يجب أن يكون المدرس موافق عليه ونشط ليظهر للطلاب'),
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
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getAcademyColumn(), // Add academy column when viewing all academies
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('الصورة')
                    ->circular(),
                Tables\Columns\TextColumn::make('teacher_code')
                    ->label('رمز المدرس')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('الاسم')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('gender')
                    ->label('الجنس')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'male' => 'معلم',
                        'female' => 'معلمة',
                        default => '-',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'male' => 'info',
                        'female' => 'pink',
                        default => 'gray',
                    }),
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
                        SubscriptionStatus::PENDING->value => 'قيد الانتظار',
                        'rejected' => 'مرفوض',
                        default => 'قيد الانتظار'
                    })
                    ->colors([
                        'success' => 'approved',
                        'warning' => SubscriptionStatus::PENDING->value,
                        'danger' => 'rejected',
                    ])
                    ->icon(fn (?string $state): string => match($state) {
                        'approved' => 'heroicon-o-check-circle',
                        SubscriptionStatus::PENDING->value => 'heroicon-o-clock',
                        'rejected' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle'
                    }),

                Tables\Columns\TextColumn::make('teaching_experience_years')
                    ->label('سنوات الخبرة')
                    ->sortable(),
                Tables\Columns\TextColumn::make('session_price_individual')
                    ->label('سعر الحصة الفردية')
                    ->money('SAR')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('academy_id')
                    ->label('الأكاديمية')
                    ->options(Academy::where('is_active', true)->pluck('name', 'id'))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('approval_status')
                    ->label('حالة الموافقة')
                    ->options([
                        SubscriptionStatus::PENDING->value => 'قيد الانتظار',
                        'approved' => 'موافق عليه',
                        'rejected' => 'مرفوض',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('نشط'),
                Tables\Filters\TernaryFilter::make('user_id')
                    ->label('مربوط بحساب')
                    ->nullable()
                    ->trueLabel('مربوط')
                    ->falseLabel('غير مربوط'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('موافقة')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => $record->approval_status !== 'approved')
                    ->requiresConfirmation()
                    ->modalHeading('الموافقة على المدرس')
                    ->modalDescription('هل أنت متأكد من الموافقة على هذا المدرس؟')
                    ->action(function ($record) {
                        $record->update([
                            'approval_status' => 'approved',
                            'approved_by' => auth()->user()->id,
                            'approved_at' => now(),
                        ]);
                        Notification::make()
                            ->title('تمت الموافقة على المدرس بنجاح')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('activate')
                    ->label('تفعيل')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_active)
                    ->requiresConfirmation()
                    ->modalHeading('تفعيل المدرس')
                    ->modalDescription('هل أنت متأكد من تفعيل هذا المدرس؟ سيتم تفعيل حسابه والموافقة عليه.')
                    ->action(function ($record) {
                        $record->activate(auth()->user()->id);
                        Notification::make()
                            ->title('تم تفعيل المدرس')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('deactivate')
                    ->label('إلغاء التفعيل')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->is_active)
                    ->requiresConfirmation()
                    ->modalHeading('إلغاء تفعيل المدرس')
                    ->modalDescription('هل أنت متأكد من إلغاء تفعيل هذا المدرس؟ سيتم إلغاء تفعيل حسابه.')
                    ->action(function ($record) {
                        $record->deactivate();
                        Notification::make()
                            ->title('تم إلغاء تفعيل المدرس')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListAcademicTeacherProfiles::route('/'),
            'create' => Pages\CreateAcademicTeacherProfile::route('/create'),
            'edit' => Pages\EditAcademicTeacherProfile::route('/{record}/edit'),
        ];
    }
}
