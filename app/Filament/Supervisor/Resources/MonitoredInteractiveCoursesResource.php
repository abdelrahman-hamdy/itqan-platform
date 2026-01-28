<?php

namespace App\Filament\Supervisor\Resources;

use App\Enums\DifficultyLevel;
use App\Enums\InteractiveCourseStatus;
use App\Enums\SessionDuration;
use App\Filament\Supervisor\Resources\MonitoredInteractiveCoursesResource\Pages;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Monitored Interactive Courses Resource for Supervisor Panel
 * Aligned with Admin InteractiveCourseResource - allows supervisors to manage
 * interactive courses for their assigned academic teachers
 */
class MonitoredInteractiveCoursesResource extends BaseSupervisorResource
{
    protected static ?string $model = InteractiveCourse::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationLabel = 'الدورات التفاعلية';

    protected static ?string $modelLabel = 'دورة تفاعلية';

    protected static ?string $pluralModelLabel = 'الدورات التفاعلية';

    protected static ?string $navigationGroup = 'الدورات التفاعلية';

    protected static ?int $navigationSort = 1;

    /**
     * Only show navigation if supervisor has academic teachers with interactive courses.
     * Interactive courses are derived from assigned academic teachers.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::hasDerivedInteractiveCourses();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الدورة الأساسية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('course_code')
                                    ->label('رمز الدورة')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('title')
                                    ->label('عنوان الدورة')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('title_en')
                                    ->label('عنوان الدورة (إنجليزي)')
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('description')
                                    ->label('وصف الدورة')
                                    ->required()
                                    ->maxLength(1000)
                                    ->rows(4),

                                Forms\Components\Textarea::make('description_en')
                                    ->label('وصف الدورة (إنجليزي)')
                                    ->maxLength(1000)
                                    ->rows(4),
                            ]),
                    ]),

                Forms\Components\Section::make('التخصص والمستوى')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('subject_id')
                                    ->relationship('subject', 'name')
                                    ->label('المادة الدراسية')
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\Select::make('grade_level_id')
                                    ->relationship('gradeLevel', 'name')
                                    ->label('المرحلة الدراسية')
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\Select::make('assigned_teacher_id')
                                    ->label('المعلم المعين')
                                    ->options(function () {
                                        $profileIds = static::getAssignedAcademicTeacherProfileIds();
                                        if (empty($profileIds)) {
                                            return ['0' => 'لا توجد معلمين مُسندين'];
                                        }

                                        return AcademicTeacherProfile::whereIn('id', $profileIds)
                                            ->with('user')
                                            ->get()
                                            ->mapWithKeys(function ($teacher) {
                                                $displayName = $teacher->user?->name ?? $teacher->full_name ?? 'غير محدد';

                                                return [$teacher->id => $displayName];
                                            })->toArray();
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ]),

                Forms\Components\Section::make('إعدادات الدورة')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('total_sessions')
                                    ->label('إجمالي الجلسات')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(200)
                                    ->default(16)
                                    ->required()
                                    ->suffix('جلسة'),

                                Forms\Components\TextInput::make('sessions_per_week')
                                    ->label('الجلسات أسبوعياً')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(7)
                                    ->default(2)
                                    ->required()
                                    ->suffix('جلسة'),

                                Forms\Components\TextInput::make('duration_weeks')
                                    ->label('مدة الدورة (أسابيع)')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\Select::make('session_duration_minutes')
                                    ->label('مدة الجلسة (دقيقة)')
                                    ->options(SessionDuration::options())
                                    ->default(SessionDuration::SIXTY_MINUTES->value)
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('max_students')
                                    ->label('أقصى عدد طلاب')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(50)
                                    ->default(20)
                                    ->required()
                                    ->suffix('طالب'),

                                Forms\Components\Select::make('difficulty_level')
                                    ->label('مستوى الصعوبة')
                                    ->options(DifficultyLevel::options())
                                    ->default(DifficultyLevel::BEGINNER->value)
                                    ->required(),
                            ]),
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
                                    ->prefix(getCurrencyCode()),

                                Forms\Components\TextInput::make('teacher_payment')
                                    ->label('دفع المعلم')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(2000)
                                    ->required()
                                    ->prefix(getCurrencyCode()),

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
                                    ->required(),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('تاريخ النهاية')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\DatePicker::make('enrollment_deadline')
                                    ->label('آخر موعد للتسجيل')
                                    ->before('start_date')
                                    ->helperText('اتركه فارغاً للسماح بالتسجيل طوال فترة الدورة'),
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

                Forms\Components\Section::make('محتوى الدورة والأهداف')
                    ->schema([
                        Forms\Components\Repeater::make('learning_outcomes')
                            ->label('مخرجات التعلم')
                            ->schema([
                                Forms\Components\TextInput::make('outcome')
                                    ->label('المخرج')
                                    ->required(),
                            ])
                            ->addActionLabel('إضافة مخرج')
                            ->collapsible()
                            ->collapsed()
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('prerequisites')
                            ->label('المتطلبات المسبقة')
                            ->schema([
                                Forms\Components\TextInput::make('prerequisite')
                                    ->label('المتطلب')
                                    ->required(),
                            ])
                            ->addActionLabel('إضافة متطلب')
                            ->collapsible()
                            ->collapsed()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('course_outline')
                            ->label('مخطط الدورة')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('حالة الدورة والإعدادات')
                    ->schema([
                        Forms\Components\Toggle::make('is_published')
                            ->label('مفعل للنشر')
                            ->default(false)
                            ->helperText('هل يمكن للطلاب رؤية هذه الدورة والتسجيل فيها؟'),

                        Forms\Components\Select::make('status')
                            ->label('حالة الدورة')
                            ->options(InteractiveCourseStatus::options())
                            ->default('published')
                            ->required()
                            ->helperText('حالة الدورة الحالية'),

                        Forms\Components\Toggle::make('recording_enabled')
                            ->label('تسجيل جلسات الدورة')
                            ->default(true)
                            ->helperText('تفعيل تسجيل جميع جلسات هذه الدورة'),
                    ])->columns(3),

                Forms\Components\Section::make('ملاحظات المشرف')
                    ->schema([
                        Forms\Components\Textarea::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->rows(4)
                            ->helperText('ملاحظات المشرف الخاصة بهذه الدورة'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course_code')
                    ->label('رمز الدورة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 30 ? $state : null;
                    }),

                BadgeColumn::make('subject.name')
                    ->label('المادة')
                    ->color('info'),

                BadgeColumn::make('gradeLevel.name')
                    ->label('المرحلة')
                    ->color('success'),

                TextColumn::make('assignedTeacher.user.name')
                    ->label('المعلم')
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        if (! $record->assignedTeacher) {
                            return 'غير معين';
                        }

                        return $record->assignedTeacher->user?->name ?? $record->assignedTeacher->full_name ?? 'غير محدد';
                    }),

                BadgeColumn::make('course_type_in_arabic')
                    ->label('النوع')
                    ->color(fn (string $state): string => match ($state) {
                        'مكثف' => 'warning',
                        'تحضير للامتحانات' => 'danger',
                        default => 'primary',
                    }),

                TextColumn::make('enrollments_count')
                    ->label('المسجلين')
                    ->counts('enrollments')
                    ->suffix(function ($record) {
                        return ' / '.$record->max_students;
                    })
                    ->color(function ($record) {
                        if (! $record->max_students) {
                            return 'gray';
                        }
                        $percentage = ($record->enrollments_count / $record->max_students) * 100;

                        return $percentage >= 80 ? 'danger' : ($percentage >= 60 ? 'warning' : 'success');
                    }),

                TextColumn::make('student_price')
                    ->label('السعر')
                    ->money(fn ($record) => $record->academy?->currency?->value ?? 'SAR')
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors(InteractiveCourseStatus::colorOptions())
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof InteractiveCourseStatus) {
                            return $state->label();
                        }
                        $statusEnum = InteractiveCourseStatus::tryFrom($state);

                        return $statusEnum?->label() ?? $state;
                    }),

                TextColumn::make('start_date')
                    ->label('تاريخ البداية')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_published')
                    ->label('منشور')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('المادة')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('grade_level_id')
                    ->label('المرحلة')
                    ->relationship('gradeLevel', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(InteractiveCourseStatus::options()),

                Tables\Filters\SelectFilter::make('difficulty_level')
                    ->label('مستوى الصعوبة')
                    ->options(DifficultyLevel::options()),

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('منشور')
                    ->trueLabel('منشور')
                    ->falseLabel('غير منشور'),

                Tables\Filters\Filter::make('upcoming')
                    ->label('القادمة')
                    ->query(fn (Builder $query) => $query->where('start_date', '>', now()))
                    ->toggle(),

                Tables\Filters\Filter::make('ongoing')
                    ->label('الجارية')
                    ->query(fn (Builder $query) => $query
                        ->where('start_date', '<=', now())
                        ->where('end_date', '>=', now()))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\Action::make('view_sessions')
                        ->label('الجلسات')
                        ->icon('heroicon-o-calendar-days')
                        ->url(fn (InteractiveCourse $record): string => MonitoredAllSessionsResource::getUrl('index', [
                            'activeTab' => 'interactive',
                            'tableFilters[course_id][value]' => $record->id,
                        ])),
                    Tables\Actions\DeleteAction::make()
                        ->label('حذف'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ]);
    }

    /**
     * Override query to filter by assigned academic teachers.
     * Interactive courses are derived from assigned academic teachers.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'assignedTeacher.user',
                'subject',
                'gradeLevel',
                'category',
            ])
            ->withCount('enrollments');

        // Filter by derived course IDs from assigned academic teachers
        $courseIds = static::getDerivedInteractiveCourseIds();

        if (! empty($courseIds)) {
            $query->whereIn('id', $courseIds);
        } else {
            // No courses from assigned teachers - return empty result
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonitoredInteractiveCourses::route('/'),
            'create' => Pages\CreateMonitoredInteractiveCourse::route('/create'),
            'view' => Pages\ViewMonitoredInteractiveCourse::route('/{record}'),
            'edit' => Pages\EditMonitoredInteractiveCourse::route('/{record}/edit'),
        ];
    }

    // CRUD permissions inherited from BaseSupervisorResource
    // Supervisors can edit/delete interactive courses for their assigned teachers
}
