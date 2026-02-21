<?php

namespace App\Filament\Supervisor\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Supervisor\Resources\MonitoredInteractiveCoursesResource\Pages\ListMonitoredInteractiveCourses;
use App\Filament\Supervisor\Resources\MonitoredInteractiveCoursesResource\Pages\CreateMonitoredInteractiveCourse;
use App\Filament\Supervisor\Resources\MonitoredInteractiveCoursesResource\Pages\ViewMonitoredInteractiveCourse;
use App\Filament\Supervisor\Resources\MonitoredInteractiveCoursesResource\Pages\EditMonitoredInteractiveCourse;
use App\Enums\DifficultyLevel;
use App\Enums\InteractiveCourseStatus;
use App\Enums\SessionDuration;
use App\Filament\Supervisor\Resources\MonitoredInteractiveCoursesResource\Pages;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use Filament\Forms;
use Filament\Tables;
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

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationLabel = 'الدورات التفاعلية';

    protected static ?string $modelLabel = 'دورة تفاعلية';

    protected static ?string $pluralModelLabel = 'الدورات التفاعلية';

    protected static string | \UnitEnum | null $navigationGroup = 'الدورات التفاعلية';

    protected static ?int $navigationSort = 1;

    /**
     * Only show navigation if supervisor has academic teachers with interactive courses.
     * Interactive courses are derived from assigned academic teachers.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::hasDerivedInteractiveCourses();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الدورة الأساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('course_code')
                                    ->label('رمز الدورة')
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('title')
                                    ->label('عنوان الدورة')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('title_en')
                                    ->label('عنوان الدورة (إنجليزي)')
                                    ->maxLength(255),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Textarea::make('description')
                                    ->label('وصف الدورة')
                                    ->required()
                                    ->maxLength(1000)
                                    ->rows(4),

                                Textarea::make('description_en')
                                    ->label('وصف الدورة (إنجليزي)')
                                    ->maxLength(1000)
                                    ->rows(4),
                            ]),
                    ]),

                Section::make('التخصص والمستوى')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('subject_id')
                                    ->relationship('subject', 'name')
                                    ->label('المادة الدراسية')
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                Select::make('grade_level_id')
                                    ->relationship('gradeLevel', 'name')
                                    ->label('المرحلة الدراسية')
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                Select::make('assigned_teacher_id')
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

                Section::make('إعدادات الدورة')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('total_sessions')
                                    ->label('إجمالي الجلسات')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(200)
                                    ->default(16)
                                    ->required()
                                    ->suffix('جلسة'),

                                TextInput::make('sessions_per_week')
                                    ->label('الجلسات أسبوعياً')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(7)
                                    ->default(2)
                                    ->required()
                                    ->suffix('جلسة'),

                                TextInput::make('duration_weeks')
                                    ->label('مدة الدورة (أسابيع)')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false),

                                Select::make('session_duration_minutes')
                                    ->label('مدة الجلسة (دقيقة)')
                                    ->options(SessionDuration::options())
                                    ->default(SessionDuration::SIXTY_MINUTES->value)
                                    ->required(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('max_students')
                                    ->label('أقصى عدد طلاب')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(50)
                                    ->default(20)
                                    ->required()
                                    ->suffix('طالب'),

                                Select::make('difficulty_level')
                                    ->label('مستوى الصعوبة')
                                    ->options(DifficultyLevel::options())
                                    ->default(DifficultyLevel::BEGINNER->value)
                                    ->required(),
                            ]),
                    ]),

                Section::make('الإعدادات المالية')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('student_price')
                                    ->label('سعر الدورة للطالب')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(500)
                                    ->required()
                                    ->prefix(getCurrencyCode()),

                                TextInput::make('teacher_payment')
                                    ->label('دفع المعلم')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(2000)
                                    ->required()
                                    ->prefix(getCurrencyCode()),

                                Select::make('payment_type')
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

                Section::make('التواريخ والجدولة')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label('تاريخ البداية')
                                    ->required(),

                                DatePicker::make('end_date')
                                    ->label('تاريخ النهاية')
                                    ->disabled()
                                    ->dehydrated(false),

                                DatePicker::make('enrollment_deadline')
                                    ->label('آخر موعد للتسجيل')
                                    ->before('start_date')
                                    ->helperText('اتركه فارغاً للسماح بالتسجيل طوال فترة الدورة'),
                            ]),

                        KeyValue::make('schedule')
                            ->label('الجدول الأسبوعي')
                            ->keyLabel('اليوم')
                            ->valueLabel('التوقيت')
                            ->default([
                                'الأحد' => '16:00 - 17:00',
                                'الثلاثاء' => '16:00 - 17:00',
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('محتوى الدورة والأهداف')
                    ->schema([
                        Repeater::make('learning_outcomes')
                            ->label('مخرجات التعلم')
                            ->schema([
                                TextInput::make('outcome')
                                    ->label('المخرج')
                                    ->required(),
                            ])
                            ->addActionLabel('إضافة مخرج')
                            ->collapsible()
                            ->collapsed()
                            ->columnSpanFull(),

                        Repeater::make('prerequisites')
                            ->label('المتطلبات المسبقة')
                            ->schema([
                                TextInput::make('prerequisite')
                                    ->label('المتطلب')
                                    ->required(),
                            ])
                            ->addActionLabel('إضافة متطلب')
                            ->collapsible()
                            ->collapsed()
                            ->columnSpanFull(),

                        Textarea::make('course_outline')
                            ->label('مخطط الدورة')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),

                Section::make('حالة الدورة والإعدادات')
                    ->schema([
                        Toggle::make('is_published')
                            ->label('مفعل للنشر')
                            ->default(false)
                            ->helperText('هل يمكن للطلاب رؤية هذه الدورة والتسجيل فيها؟'),

                        Select::make('status')
                            ->label('حالة الدورة')
                            ->options(InteractiveCourseStatus::options())
                            ->default(InteractiveCourseStatus::PUBLISHED->value)
                            ->required()
                            ->helperText('حالة الدورة الحالية'),

                        Toggle::make('recording_enabled')
                            ->label('تسجيل جلسات الدورة')
                            ->default(true)
                            ->helperText('تفعيل تسجيل جميع جلسات هذه الدورة'),
                    ])->columns(3),

                Section::make('ملاحظات المشرف')
                    ->schema([
                        Textarea::make('supervisor_notes')
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

                TextColumn::make('subject.name')
                    ->badge()
                    ->label('المادة')
                    ->color('info'),

                TextColumn::make('gradeLevel.name')
                    ->badge()
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

                TextColumn::make('course_type_in_arabic')
                    ->badge()
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
                    ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR'))
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
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
                SelectFilter::make('subject_id')
                    ->label('المادة')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('الكل'),

                SelectFilter::make('grade_level_id')
                    ->label('المرحلة')
                    ->relationship('gradeLevel', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('الكل'),

                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(InteractiveCourseStatus::options())
                    ->placeholder('الكل'),

                SelectFilter::make('difficulty_level')
                    ->label('مستوى الصعوبة')
                    ->options(DifficultyLevel::options())
                    ->placeholder('الكل'),

                TernaryFilter::make('is_published')
                    ->label('منشور')
                    ->placeholder('الكل')
                    ->trueLabel('منشور')
                    ->falseLabel('غير منشور'),

                Filter::make('upcoming')
                    ->label('القادمة')
                    ->query(fn (Builder $query) => $query->where('start_date', '>', now()))
                    ->toggle(),

                Filter::make('ongoing')
                    ->label('الجارية')
                    ->query(fn (Builder $query) => $query
                        ->where('start_date', '<=', now())
                        ->where('end_date', '>=', now()))
                    ->toggle(),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('عرض'),
                    EditAction::make()
                        ->label('تعديل'),
                    Action::make('view_sessions')
                        ->label('الجلسات')
                        ->icon('heroicon-o-calendar-days')
                        ->url(fn (InteractiveCourse $record): string => MonitoredInteractiveCourseSessionsResource::getUrl('index', [
                            'tableFilters[course_id][value]' => $record->id,
                        ])),
                    DeleteAction::make()
                        ->label('حذف'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
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
            'index' => ListMonitoredInteractiveCourses::route('/'),
            'create' => CreateMonitoredInteractiveCourse::route('/create'),
            'view' => ViewMonitoredInteractiveCourse::route('/{record}'),
            'edit' => EditMonitoredInteractiveCourse::route('/{record}/edit'),
        ];
    }

    // CRUD permissions inherited from BaseSupervisorResource
    // Supervisors can edit/delete interactive courses for their assigned teachers
}
