<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicTeacherProfileResource\Pages;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\Subject;
use App\Models\GradeLevel;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\ScopedToAcademyViaRelationship;
use App\Services\AcademyContextService;

class AcademicTeacherProfileResource extends BaseResource
{
    use ScopedToAcademyViaRelationship;

    protected static ?string $model = AcademicTeacherProfile::class;
    
    protected static ?string $tenantOwnershipRelationshipName = 'user';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'المدرسين الأكاديميين';

    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';

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
                                    ->label('المستوى التعليمي')
                                    ->options([
                                        'diploma' => 'دبلوم',
                                        'bachelor' => 'بكالوريوس',
                                        'master' => 'ماجستير',
                                        'phd' => 'دكتوراه',
                                    ])
                                    ->default('bachelor')
                                    ->required(),
                                Forms\Components\TextInput::make('university')
                                    ->label('الجامعة')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('graduation_year')
                                    ->label('سنة التخرج')
                                    ->numeric()
                                    ->minValue(1970)
                                    ->maxValue(date('Y')),
                                Forms\Components\TextInput::make('qualification_degree')
                                    ->label('التخصص')
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

                Forms\Components\Section::make('التخصص التدريسي')
                    ->schema([
                        Forms\Components\CheckboxList::make('subject_ids')
                            ->label('المواد التي يمكن تدريسها')
                            ->options(function () {
                                $academyId = AcademyContextService::getCurrentAcademy()?->id;
                                if (!$academyId) {
                                    return [];
                                }
                                return Subject::forAcademy($academyId)
                                    ->active()
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->required()
                            ->columns(3),
                        Forms\Components\CheckboxList::make('grade_level_ids')
                            ->label('المراحل الدراسية')
                            ->options(function () {
                                $academyId = AcademyContextService::getCurrentAcademy()?->id;
                                if (!$academyId) {
                                    return [];
                                }
                                return GradeLevel::forAcademy($academyId)
                                    ->active()
                                    ->orderBy('level')
                                    ->get()
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->required()
                            ->columns(3),
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
                            ->options([
                                'sunday' => 'الأحد',
                                'monday' => 'الاثنين',
                                'tuesday' => 'الثلاثاء',
                                'wednesday' => 'الأربعاء',
                                'thursday' => 'الخميس',
                                'friday' => 'الجمعة',
                                'saturday' => 'السبت',
                            ])
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

                Forms\Components\Section::make('الحالة والموافقة')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('approval_status')
                                    ->label('حالة الموافقة')
                                    ->options([
                                        'pending' => 'في الانتظار',
                                        'approved' => 'معتمد',
                                        'rejected' => 'مرفوض',
                                    ])
                                    ->default('pending')
                                    ->required(),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('نشط')
                                    ->default(true),
                            ]),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات إدارية')
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn () => auth()->user()->isAdmin()),
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
                Tables\Columns\IconColumn::make('user_id')
                    ->label('مربوط بحساب')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\BadgeColumn::make('approval_status')
                    ->label('حالة الموافقة')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'pending' => 'في الانتظار',
                            'approved' => 'معتمد',
                            'rejected' => 'مرفوض',
                            default => $state,
                        };
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
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
                        'pending' => 'في الانتظار',
                        'approved' => 'معتمد',
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
