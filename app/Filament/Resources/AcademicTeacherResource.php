<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicTeacherResource\Pages;
use App\Models\AcademicTeacher;
use App\Models\User;
use App\Models\Subject;
use App\Models\GradeLevel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Illuminate\Database\Eloquent\Builder;

class AcademicTeacherResource extends Resource
{
    protected static ?string $model = AcademicTeacher::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationLabel = 'المعلمون الأكاديميون';
    
    protected static ?string $navigationGroup = 'القسم الأكاديمي';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $modelLabel = 'معلم أكاديمي';
    
    protected static ?string $pluralModelLabel = 'المعلمون الأكاديميون';

    // Override to scope to current user's academy
    public static function getEloquentQuery(): Builder
    {
        $academyId = auth()->user()->academy_id ?? 1;
        return parent::getEloquentQuery()->where('academy_id', $academyId);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الشخصية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('المستخدم')
                                    ->options(function () {
                                        $academyId = auth()->user()->academy_id ?? 1;
                                        return User::where('academy_id', $academyId)
                                            ->where('role', 'teacher')
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->createOptionForm([
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
                                            ->unique(User::class),
                                        Forms\Components\TextInput::make('phone')
                                            ->label('رقم الهاتف')
                                            ->tel()
                                            ->maxLength(20),
                                        Forms\Components\TextInput::make('password')
                                            ->label('كلمة المرور')
                                            ->password()
                                            ->required()
                                            ->minLength(8),
                                    ])
                                    ->createOptionUsing(function (array $data): int {
                                        $academyId = auth()->user()->academy_id ?? 1;
                                        $user = User::create([
                                            ...$data,
                                            'academy_id' => $academyId,
                                            'role' => 'teacher',
                                            'status' => 'active',
                                            'password' => bcrypt($data['password']),
                                        ]);
                                        return $user->id;
                                    })
                                    ->required(),

                                Forms\Components\TextInput::make('teacher_code')
                                    ->label('رمز المعلم')
                                    ->default(function () {
                                        $academyId = auth()->user()->academy_id ?? 1;
                                        $count = AcademicTeacher::where('academy_id', $academyId)->count() + 1;
                                        return 'ACAD-' . $academyId . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
                                    })
                                    ->disabled()
                                    ->dehydrated(),
                            ]),
                    ]),

                Forms\Components\Section::make('المؤهلات والخبرة')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('education_level')
                                    ->label('المؤهل العلمي')
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
                                    ->minValue(1980)
                                    ->maxValue(date('Y')),
                            ]),

                        Forms\Components\TextInput::make('teaching_experience_years')
                            ->label('سنوات الخبرة في التدريس')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(50)
                            ->default(0)
                            ->required()
                            ->suffix('سنة'),
                    ]),

                Forms\Components\Section::make('التدريس والمواد')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\CheckboxList::make('subjects')
                                    ->label('المواد التي يدرسها')
                                    ->relationship('subjects', 'name')
                                    ->options(function () {
                                        $academyId = auth()->user()->academy_id ?? 1;
                                        return \App\Models\Subject::where('academy_id', $academyId)
                                            ->where('is_active', true)
                                            ->pluck('name', 'id');
                                    })
                                    ->columns(2)
                                    ->required(),

                                Forms\Components\CheckboxList::make('gradeLevels')
                                    ->label('المراحل التي يدرسها')
                                    ->relationship('gradeLevels', 'name')
                                    ->options(function () {
                                        $academyId = auth()->user()->academy_id ?? 1;
                                        return \App\Models\GradeLevel::where('academy_id', $academyId)
                                            ->where('is_active', true)
                                            ->pluck('name', 'id');
                                    })
                                    ->columns(2)
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('session_price_individual')
                                    ->label('سعر الحصة الخاصة (ريال/ساعة)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(100)
                                    ->required()
                                    ->prefix('SAR')
                                    ->helperText('سعر الساعة الواحدة للدروس الخاصة'),

                                Forms\Components\TextInput::make('min_session_duration')
                                    ->label('أقل مدة للحصة (دقيقة)')
                                    ->numeric()
                                    ->minValue(15)
                                    ->maxValue(180)
                                    ->default(45)
                                    ->required()
                                    ->suffix('دقيقة'),

                                Forms\Components\TextInput::make('max_session_duration')
                                    ->label('أقصى مدة للحصة (دقيقة)')
                                    ->numeric()
                                    ->minValue(30)
                                    ->maxValue(180)
                                    ->default(90)
                                    ->required()
                                    ->suffix('دقيقة'),
                            ]),
                    ]),

                Forms\Components\Section::make('الجدول الزمني والتوفر')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\CheckboxList::make('available_days')
                                    ->label('الأيام المتاحة')
                                    ->options([
                                        'saturday' => 'السبت',
                                        'sunday' => 'الأحد',
                                        'monday' => 'الاثنين',
                                        'tuesday' => 'الثلاثاء',
                                        'wednesday' => 'الأربعاء',
                                        'thursday' => 'الخميس',
                                        'friday' => 'الجمعة',
                                    ])
                                    ->columns(2)
                                    ->required(),

                                Forms\Components\CheckboxList::make('languages')
                                    ->label('اللغات التي يدرس بها')
                                    ->options([
                                        'ar' => 'العربية',
                                        'en' => 'الإنجليزية',
                                        'fr' => 'الفرنسية',
                                        'de' => 'الألمانية',
                                    ])
                                    ->default(['ar'])
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('available_time_start')
                                    ->label('وقت البداية المتاح')
                                    ->default('08:00')
                                    ->required()
                                    ->seconds(false)
                                    ->helperText('الوقت الذي يبدأ فيه المعلم العمل يومياً'),

                                Forms\Components\TimePicker::make('available_time_end')
                                    ->label('وقت النهاية المتاح')
                                    ->default('18:00')
                                    ->required()
                                    ->seconds(false)
                                    ->helperText('الوقت الذي ينتهي فيه المعلم من العمل يومياً')
                                    ->after('available_time_start'),
                            ]),
                    ]),

                Forms\Components\Section::make('معلومات إضافية')
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

                        Forms\Components\Toggle::make('can_create_courses')
                            ->label('يمكنه إنشاء دورات تفاعلية')
                            ->default(false),
                    ]),

                Forms\Components\Section::make('الحالة والاعتماد')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('الحالة')
                                    ->options([
                                        'pending' => 'في الانتظار',
                                        'active' => 'نشط',
                                        'inactive' => 'غير نشط',
                                        'suspended' => 'معلق',
                                        'rejected' => 'مرفوض',
                                    ])
                                    ->default('pending')
                                    ->required(),

                                Forms\Components\Toggle::make('is_approved')
                                    ->label('معتمد')
                                    ->default(false),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('نشط')
                                    ->default(true),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات إدارية')
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher_code')
                    ->label('رمز المعلم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('اسم المعلم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('education_level_in_arabic')
                    ->label('المؤهل')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('session_price_individual')
                    ->label('سعر الحصة الخاصة')
                    ->money('SAR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('teaching_experience_years')
                    ->label('سنوات الخبرة')
                    ->suffix(' سنة')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_approved')
                    ->label('معتمد')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'active',
                        'danger' => 'inactive',
                        'danger' => 'suspended',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'في الانتظار',
                        'active' => 'نشط',
                        'inactive' => 'غير نشط',
                        'suspended' => 'معلق',
                        'rejected' => 'مرفوض',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('total_students')
                    ->label('عدد الطلاب')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->date('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('education_level')
                    ->label('المؤهل العلمي')
                    ->options([
                        'diploma' => 'دبلوم',
                        'bachelor' => 'بكالوريوس',
                        'master' => 'ماجستير',
                        'phd' => 'دكتوراه',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'في الانتظار',
                        'active' => 'نشط',
                        'inactive' => 'غير نشط',
                        'suspended' => 'معلق',
                        'rejected' => 'مرفوض',
                    ]),

                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('الاعتماد')
                    ->placeholder('الكل')
                    ->trueLabel('معتمد')
                    ->falseLabel('غير معتمد'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('النشاط')
                    ->placeholder('الكل')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
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
                Components\Section::make('المعلومات الأساسية')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('teacher_code')
                                    ->label('رمز المعلم')
                                    ->badge()
                                    ->color('primary'),
                                Components\TextEntry::make('user.name')
                                    ->label('اسم المعلم'),
                                Components\TextEntry::make('user.email')
                                    ->label('البريد الإلكتروني'),
                            ]),
                    ]),

                Components\Section::make('المؤهلات والتخصص')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('education_level_in_arabic')
                                    ->label('المؤهل العلمي')
                                    ->badge()
                                    ->color('success'),
                                Components\TextEntry::make('university')
                                    ->label('الجامعة')
                                    ->placeholder('غير محدد'),
                                Components\TextEntry::make('teaching_experience_years')
                                    ->label('سنوات الخبرة')
                                    ->suffix(' سنة'),
                            ]),
                    ]),

                Components\Section::make('الأسعار والجدولة')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('session_price_individual')
                                    ->label('سعر الحصة الخاصة')
                                    ->money('SAR'),
                                Components\TextEntry::make('available_time_start')
                                    ->label('وقت البداية')
                                    ->time('H:i'),
                                Components\TextEntry::make('available_time_end')
                                    ->label('وقت النهاية')
                                    ->time('H:i'),
                            ]),
                        Components\RepeatableEntry::make('available_days')
                            ->label('الأيام المتاحة')
                            ->badge()
                            ->columnSpanFull(),
                    ]),

                Components\Section::make('الحالة والإحصائيات')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\IconEntry::make('is_approved')
                                    ->label('معتمد')
                                    ->boolean(),
                                Components\TextEntry::make('status')
                                    ->label('الحالة')
                                    ->badge(),
                                Components\TextEntry::make('total_students')
                                    ->label('عدد الطلاب'),
                                Components\TextEntry::make('created_at')
                                    ->label('تاريخ التسجيل')
                                    ->date('Y-m-d'),
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
            'index' => Pages\ListAcademicTeachers::route('/'),
            'create' => Pages\CreateAcademicTeacher::route('/create'),
            'view' => Pages\ViewAcademicTeacher::route('/{record}'),
            'edit' => Pages\EditAcademicTeacher::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $academyId = auth()->user()->academy_id ?? 1;
        return static::getModel()::where('academy_id', $academyId)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }
}
