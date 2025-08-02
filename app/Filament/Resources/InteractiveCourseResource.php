<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InteractiveCourseResource\Pages;
use App\Models\InteractiveCourse;
use App\Models\AcademicTeacherProfile;
use App\Models\Subject;
use App\Models\GradeLevel;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\ScopedToAcademy;
use App\Services\AcademyContextService;

class InteractiveCourseResource extends BaseResource
{
    use ScopedToAcademy;

    protected static ?string $model = InteractiveCourse::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'الدورات التفاعلية';
    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';
    protected static ?string $modelLabel = 'دورة تفاعلية';
    protected static ?string $pluralModelLabel = 'الدورات التفاعلية';

    // Note: getEloquentQuery() is now handled by ScopedToAcademy trait

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الدورة الأساسية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('عنوان الدورة')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('مثل: رياضيات متقدمة - الفصل الأول'),

                                Forms\Components\Select::make('course_type')
                                    ->label('نوع الدورة')
                                    ->options([
                                        'regular' => 'منتظم',
                                        'intensive' => 'مكثف',
                                        'exam_prep' => 'تحضير للامتحانات',
                                    ])
                                    ->default('regular')
                                    ->required(),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف الدورة')
                            ->required()
                            ->maxLength(1000)
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('التخصص والمستوى')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('subject_id')
                                    ->label('المادة الدراسية')
                                    ->options(function () {
                                        $academyId = AcademyContextService::getCurrentAcademyId();
                                        return $academyId ? Subject::forAcademy($academyId)->pluck('name', 'id') : [];
                                    })
                                    ->required()
                                    ->searchable(),

                                Forms\Components\Select::make('grade_level_id')
                                    ->label('المرحلة الدراسية')
                                    ->options(function () {
                                        $academyId = AcademyContextService::getCurrentAcademyId();
                                        return $academyId ? GradeLevel::forAcademy($academyId)->pluck('name', 'id') : [];
                                    })
                                    ->required()
                                    ->searchable(),

                                Forms\Components\Select::make('assigned_teacher_id')
                                    ->label('المعلم المعين')
                                    ->options(function () {
                                        $academyId = AcademyContextService::getCurrentAcademyId();
                                        return $academyId ? AcademicTeacherProfile::forAcademy($academyId)
                                            ->approved()
                                            ->active()
                                            ->with('user')
                                            ->get()
                                            ->mapWithKeys(function($teacher) {
                                                // Use linked user name if available, otherwise use profile's full name
                                                $displayName = $teacher->user ? $teacher->user->name : $teacher->full_name;
                                                $qualification = $teacher->educational_qualification ?? 'غير محدد';
                                                return [$teacher->id => $displayName . ' (' . $qualification . ')'];
                                            }) : [];
                                    })
                                    ->required()
                                    ->searchable(),
                            ]),
                    ]),

                Forms\Components\Section::make('إعدادات الدورة')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('duration_weeks')
                                    ->label('مدة الدورة (أسابيع)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(52)
                                    ->default(8)
                                    ->required()
                                    ->live()
                                    ->suffix('أسبوع'),

                                Forms\Components\TextInput::make('sessions_per_week')
                                    ->label('الجلسات أسبوعياً')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(7)
                                    ->default(2)
                                    ->required()
                                    ->live()
                                    ->suffix('جلسة'),

                                Forms\Components\TextInput::make('session_duration_minutes')
                                    ->label('مدة الجلسة (دقيقة)')
                                    ->numeric()
                                    ->minValue(30)
                                    ->maxValue(180)
                                    ->default(60)
                                    ->required()
                                    ->suffix('دقيقة'),

                                Forms\Components\TextInput::make('max_students')
                                    ->label('أقصى عدد طلاب')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(50)
                                    ->default(20)
                                    ->required()
                                    ->suffix('طالب'),
                            ]),

                        Forms\Components\TextInput::make('total_sessions')
                            ->label('إجمالي الجلسات')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder(function (Forms\Get $get) {
                                $weeks = $get('duration_weeks') ?? 0;
                                $sessionsPerWeek = $get('sessions_per_week') ?? 0;
                                return $weeks * $sessionsPerWeek . ' جلسة';
                            }),
                    ]),

                Forms\Components\Section::make('الإعدادات المالية')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('student_price')
                                    ->label('سعر الدورة للطالب')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(500)
                                    ->required()
                                    ->prefix('SAR'),

                                Forms\Components\TextInput::make('teacher_payment')
                                    ->label('دفع المعلم')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(2000)
                                    ->required()
                                    ->prefix('SAR'),

                                Forms\Components\Select::make('payment_type')
                                    ->label('نوع دفع المعلم')
                                    ->options([
                                        'fixed_amount' => 'مبلغ ثابت',
                                        'per_student' => 'لكل طالب',
                                        'per_session' => 'لكل جلسة',
                                    ])
                                    ->default('fixed_amount')
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('التواريخ والجدولة')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('تاريخ البداية')
                                    ->required()
                                    ->minDate(now()->addDays(7))
                                    ->live(),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('تاريخ النهاية')
                                    ->required()
                                    ->after('start_date')
                                    ->live(),

                                Forms\Components\DatePicker::make('enrollment_deadline')
                                    ->label('آخر موعد للتسجيل')
                                    ->required()
                                    ->before('start_date')
                                    ->maxDate(function (Forms\Get $get) {
                                        $startDate = $get('start_date');
                                        return $startDate ? date('Y-m-d', strtotime($startDate . ' -3 days')) : null;
                                    }),
                            ]),

                        Forms\Components\KeyValue::make('schedule')
                            ->label('الجدول الأسبوعي')
                            ->keyLabel('اليوم')
                            ->valueLabel('التوقيت')
                            ->default([
                                'الأحد' => '16:00 - 17:00',
                                'الثلاثاء' => '16:00 - 17:00',
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('حالة الدورة')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('الحالة')
                                    ->options([
                                        'draft' => 'مسودة',
                                        'published' => 'منشور',
                                        'active' => 'نشط',
                                        'completed' => 'مكتمل',
                                        'cancelled' => 'ملغي',
                                    ])
                                    ->default('draft')
                                    ->required(),

                                Forms\Components\Toggle::make('is_published')
                                    ->label('مفعل للنشر')
                                    ->default(false)
                                    ->helperText('هل يمكن للطلاب رؤية هذه الدورة والتسجيل فيها؟'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getAcademyColumn(), // Add academy column when viewing all academies
                Tables\Columns\TextColumn::make('course_code')
                    ->label('رمز الدورة')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('المادة')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('gradeLevel.name')
                    ->label('المرحلة')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->badge()
                    ->color('info')
                    ->visible(fn () => AcademyContextService::isSuperAdmin() && AcademyContextService::isGlobalViewMode()),

                Tables\Columns\TextColumn::make('assignedTeacher.user.name')
                    ->label('المعلم')
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        if (!$record->assignedTeacher) {
                            return 'غير معين';
                        }
                        return $record->assignedTeacher->user 
                            ? $record->assignedTeacher->user->name 
                            : $record->assignedTeacher->full_name;
                    }),

                Tables\Columns\TextColumn::make('course_type_in_arabic')
                    ->label('النوع')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'مكثف' => 'warning',
                        'تحضير للامتحانات' => 'danger',
                        default => 'primary',
                    }),

                Tables\Columns\TextColumn::make('enrollments_count')
                    ->label('المسجلين')
                    ->counts('enrollments')
                    ->suffix(function ($record) {
                        return ' / ' . $record->max_students;
                    })
                    ->color(function ($record) {
                        $percentage = ($record->enrollments_count / $record->max_students) * 100;
                        return $percentage >= 80 ? 'danger' : ($percentage >= 60 ? 'warning' : 'success');
                    }),

                Tables\Columns\TextColumn::make('student_price')
                    ->label('السعر')
                    ->money('SAR')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status_in_arabic')
                    ->label('الحالة')
                    ->colors([
                        'secondary' => 'مسودة',
                        'info' => 'منشور',
                        'success' => 'نشط',
                        'primary' => 'مكتمل',
                        'danger' => 'ملغي',
                    ]),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البداية')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('منشور')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('المادة')
                    ->relationship('subject', 'name'),

                Tables\Filters\SelectFilter::make('grade_level_id')
                    ->label('المرحلة')
                    ->relationship('gradeLevel', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'published' => 'منشور',
                        'active' => 'نشط',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ]),

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('منشور')
                    ->placeholder('الكل')
                    ->trueLabel('منشور')
                    ->falseLabel('غير منشور'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListInteractiveCourses::route('/'),
            'create' => Pages\CreateInteractiveCourse::route('/create'),
            'edit' => Pages\EditInteractiveCourse::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $academyId = AcademyContextService::getCurrentAcademyId();
        return $academyId ? static::getModel()::forAcademy($academyId)->count() : '0';
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }
}
