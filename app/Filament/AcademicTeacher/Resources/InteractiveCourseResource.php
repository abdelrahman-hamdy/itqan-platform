<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Filament\AcademicTeacher\Resources\InteractiveCourseResource\Pages;
use App\Filament\AcademicTeacher\Resources\InteractiveCourseSessionResource;
use App\Models\InteractiveCourse;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\AcademicTeacher\Resources\BaseAcademicTeacherResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Enums\SessionStatus;
use App\Enums\InteractiveCourseStatus;

class InteractiveCourseResource extends BaseAcademicTeacherResource
{
    protected static ?string $model = InteractiveCourse::class;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?string $navigationLabel = 'الدورات التفاعلية';

    protected static ?string $modelLabel = 'دورة تفاعلية';

    protected static ?string $pluralModelLabel = 'الدورات التفاعلية';

    protected static ?int $navigationSort = 3;

    /**
     * Check if current user can view this record
     * Academic teachers can only view courses assigned to them
     */
    public static function canView(Model $record): bool
    {
        $user = Auth::user();
        
        if (!$user->isAcademicTeacher() || !$user->academicTeacherProfile) {
            return false;
        }

        // Allow viewing if course is assigned to current teacher
        return $record->assigned_teacher_id === $user->academicTeacherProfile->id;
    }

    /**
     * Check if current user can edit this record
     * Academic teachers have limited editing capabilities
     */
    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();
        
        if (!$user->isAcademicTeacher() || !$user->academicTeacherProfile) {
            return false;
        }

        // Allow editing if course is assigned to current teacher
        // Academic teachers can update course content and sessions
        return $record->assigned_teacher_id === $user->academicTeacherProfile->id;
    }

    /**
     * Get the Eloquent query with academic teacher-specific filtering
     * Only show courses assigned to the current academic teacher
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();
        
        if (!$user->isAcademicTeacher() || !$user->academicTeacherProfile) {
            return $query->whereRaw('1 = 0'); // Return no results
        }

        return $query->where('assigned_teacher_id', $user->academicTeacherProfile->id);
    }

    /**
     * Academic teachers cannot create new courses
     * This is managed by admin or academy staff
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(static::getAcademicTeacherFormSchema());
    }

    /**
     * Get form schema customized for academic teachers
     * Teachers have limited editing capabilities compared to admin
     */
    protected static function getAcademicTeacherFormSchema(): array
    {
        return [
            Forms\Components\Section::make('معلومات الدورة الأساسية')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('course_code')
                                ->label('رمز الدورة')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('رمز الدورة (يُحدد من قبل الإدارة)'),

                            Forms\Components\TextInput::make('title')
                                ->label('عنوان الدورة')
                                ->required()
                                ->maxLength(255)
                                ->helperText('عنوان واضح ومميز للدورة'),

                            Forms\Components\Select::make('status')
                                ->label('حالة الدورة')
                                ->options(\App\Enums\InteractiveCourseStatus::options())
                                ->required()
                                ->helperText('يمكن للمعلم تعديل حالة الدورة'),
                        ]),
                ]),

            Forms\Components\Section::make('تفاصيل الدورة')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('subject_id')
                                ->label('المادة')
                                ->options(static::getAvailableSubjects())
                                ->required()
                                ->searchable()
                                ->preload()
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('المادة الدراسية (يُحدد من قبل الإدارة)'),

                            Forms\Components\Select::make('grade_level_id')
                                ->label('المستوى الأكاديمي')
                                ->options(static::getAvailableGradeLevels())
                                ->required()
                                ->searchable()
                                ->preload()
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('المستوى الأكاديمي (يُحدد من قبل الإدارة)'),

                            Forms\Components\TextInput::make('total_sessions')
                                ->label('عدد الجلسات')
                                ->numeric()
                                ->minValue(1)
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('إجمالي عدد جلسات الدورة'),

                            Forms\Components\TextInput::make('duration_per_session')
                                ->label('مدة الجلسة بالدقائق')
                                ->numeric()
                                ->minValue(15)
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('مدة كل جلسة بالدقائق'),
                        ]),
                ]),

            Forms\Components\Section::make('التسعير والتسجيل')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('price_per_student')
                                ->label('سعر الطالب الواحد')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('ر.س ')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('السعر للطالب الواحد (يُحدد من قبل الإدارة)'),

                            Forms\Components\TextInput::make('max_students')
                                ->label('الحد الأقصى للطلاب')
                                ->numeric()
                                ->minValue(1)
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('العدد الأقصى للطلاب في الدورة'),

                            Forms\Components\TextInput::make('min_students')
                                ->label('الحد الأدنى للطلاب')
                                ->numeric()
                                ->minValue(1)
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('العدد الأدنى اللازم لبدء الدورة'),
                        ]),
                ]),

            Forms\Components\Section::make('المحتوى والوصف')
                ->schema([
                    Forms\Components\RichEditor::make('description')
                        ->label('وصف الدورة')
                        ->helperText('وصف تفصيلي لمحتوى وأهداف الدورة')
                        ->columnSpanFull(),

                    Forms\Components\TagsInput::make('learning_outcomes')
                        ->label('النتائج التعليمية')
                        ->helperText('الأهداف والنتائج المتوقعة من الدورة')
                        ->placeholder('اضغط Enter لإضافة هدف جديد')
                        ->columnSpanFull(),

                    Forms\Components\TagsInput::make('prerequisites')
                        ->label('المتطلبات المسبقة')
                        ->helperText('المتطلبات التي يجب توفرها قبل الالتحاق بالدورة')
                        ->placeholder('اضغط Enter لإضافة متطلب جديد')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('التواريخ والجدولة')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\DatePicker::make('start_date')
                                ->label('تاريخ البداية')
                                ->required()
                                ->native(false)
                                ->helperText('تاريخ بداية الدورة'),

                            Forms\Components\DatePicker::make('end_date')
                                ->label('تاريخ النهاية')
                                ->required()
                                ->native(false)
                                ->helperText('تاريخ نهاية الدورة'),

                            Forms\Components\DatePicker::make('enrollment_deadline')
                                ->label('آخر موعد للتسجيل')
                                ->native(false)
                                ->helperText('اتركه فارغاً للسماح بالتسجيل طوال فترة الدورة'),
                        ]),
                ]),

            Forms\Components\Section::make('إعدادات المعلم')
                ->schema([
                    Forms\Components\Toggle::make('recording_enabled')
                        ->label('تسجيل جلسات الدورة')
                        ->default(true)
                        ->helperText('تفعيل تسجيل جميع جلسات هذه الدورة'),

                    Forms\Components\Textarea::make('teacher_notes')
                        ->label('ملاحظات المعلم')
                        ->rows(4)
                        ->helperText('ملاحظات خاصة للمعلم حول هذه الدورة')
                        ->columnSpanFull(),
                ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters())
            ->actions(static::getTableActions())
            ->bulkActions(static::supportsBulkActions() ? static::getBulkActions() : []);
    }

    /**
     * Get table columns customized for academic teachers
     */
    protected static function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('course_code')
                ->label('رمز الدورة')
                ->searchable()
                ->sortable()
                ->copyable(),

            Tables\Columns\TextColumn::make('title')
                ->label('عنوان الدورة')
                ->searchable()
                ->limit(40)
                ->tooltip(function (InteractiveCourse $record): ?string {
                    return $record->title;
                }),

            Tables\Columns\TextColumn::make('subject.name')
                ->label('المادة')
                ->searchable()
                ->badge()
                ->color('info'),

            Tables\Columns\TextColumn::make('gradeLevel.name')
                ->label('المستوى')
                ->searchable()
                ->badge()
                ->color('success'),

            Tables\Columns\TextColumn::make('total_sessions')
                ->label('عدد الجلسات')
                ->sortable()
                ->badge()
                ->color('primary'),

            Tables\Columns\TextColumn::make('enrolled_students_count')
                ->label('الطلاب المسجلين')
                ->counts('enrolledStudents')
                ->suffix(fn (InteractiveCourse $record): string => " / {$record->max_students}")
                ->sortable()
                ->color(fn (InteractiveCourse $record): string => 
                    $record->enrolled_students_count >= $record->max_students ? 'danger' : 'success'
                ),

            Tables\Columns\TextColumn::make('price_per_student')
                ->label('السعر')
                ->prefix('ر.س ')
                ->sortable()
                ->money('SAR'),

            Tables\Columns\BadgeColumn::make('status')
                ->label('الحالة')
                ->colors(\App\Enums\InteractiveCourseStatus::colorOptions())
                ->formatStateUsing(function ($state): string {
                    if ($state instanceof \App\Enums\InteractiveCourseStatus) {
                        return $state->label();
                    }
                    $statusEnum = \App\Enums\InteractiveCourseStatus::tryFrom($state);
                    return $statusEnum?->label() ?? $state;
                }),

            Tables\Columns\TextColumn::make('start_date')
                ->label('تاريخ البداية')
                ->date()
                ->sortable()
                ->color(fn (InteractiveCourse $record): string => 
                    $record->start_date && $record->start_date->isPast() ? 'success' : 'primary'
                ),

            Tables\Columns\TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime('d/m/Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * Get table filters for academic teachers
     */
    protected static function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('status')
                ->label('حالة الدورة')
                ->options(\App\Enums\InteractiveCourseStatus::options()),

            Tables\Filters\SelectFilter::make('subject_id')
                ->label('المادة')
                ->options(static::getAvailableSubjects()),

            Tables\Filters\SelectFilter::make('grade_level_id')
                ->label('المستوى')
                ->options(static::getAvailableGradeLevels()),

            Tables\Filters\TernaryFilter::make('has_enrolled_students')
                ->label('لديه طلاب مسجلين')
                ->queries(
                    true: fn (Builder $query) => $query->whereHas('enrolledStudents'),
                    false: fn (Builder $query) => $query->doesntHave('enrolledStudents'),
                ),

            Tables\Filters\Filter::make('upcoming')
                ->label('دورات قادمة')
                ->query(fn (Builder $query): Builder =>
                    $query->where('start_date', '>=', now())
                          ->where('status', '!=', InteractiveCourseStatus::COMPLETED->value)
                ),
        ];
    }

    /**
     * Get table actions for academic teachers
     */
    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make()
                ->label('عرض')
                ->icon('heroicon-m-eye'),

            Tables\Actions\EditAction::make()
                ->label('تعديل')
                ->icon('heroicon-m-pencil'),

            Tables\Actions\Action::make('manage_sessions')
                ->label('إدارة الجلسات')
                ->icon('heroicon-m-calendar')
                ->color('info')
                ->url(fn (InteractiveCourse $record): string =>
                    InteractiveCourseSessionResource::getUrl('index', [
                        'tableFilters' => [
                            'course_id' => ['value' => $record->id],
                        ],
                    ])
                ),

            Tables\Actions\Action::make('view_enrolled_students')
                ->label('الطلاب المسجلين')
                ->icon('heroicon-m-users')
                ->color('success')
                ->modalHeading(fn (InteractiveCourse $record): string => 'الطلاب المسجلين في: ' . $record->title)
                ->modalDescription(fn (InteractiveCourse $record): string =>
                    'إجمالي الطلاب المسجلين: ' . $record->enrolledStudents()->count() . ' من أصل ' . $record->max_students
                )
                ->modalContent(fn (InteractiveCourse $record): \Illuminate\Support\HtmlString =>
                    new \Illuminate\Support\HtmlString(
                        '<div class="space-y-2">' .
                        $record->enrolledStudents()->with('user')->get()
                            ->map(fn ($student) => '<div class="flex items-center gap-2"><span class="text-gray-600">•</span><span>' . e($student->user->name) . '</span><span class="text-gray-500 text-sm">(' . e($student->user->email) . ')</span></div>')
                            ->join('') .
                        '</div>'
                    )
                )
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('إغلاق'),
        ];
    }

    /**
     * Get bulk actions for academic teachers
     */
    protected static function getBulkActions(): array
    {
        return [
            Tables\Actions\BulkAction::make('update_status')
                ->label('تحديث الحالة')
                ->icon('heroicon-m-pencil-square')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('الحالة الجديدة')
                        ->options([
                            \App\Enums\InteractiveCourseStatus::PUBLISHED->value => \App\Enums\InteractiveCourseStatus::PUBLISHED->label(),
                            \App\Enums\InteractiveCourseStatus::ACTIVE->value => \App\Enums\InteractiveCourseStatus::ACTIVE->label(),
                            \App\Enums\InteractiveCourseStatus::COMPLETED->value => \App\Enums\InteractiveCourseStatus::COMPLETED->label(),
                        ])
                        ->required(),
                ])
                ->action(function (array $data, $records) {
                    foreach ($records as $record) {
                        $record->update([
                            'status' => $data['status'],
                        ]);
                    }
                }),
        ];
    }

    /**
     * Get available subjects for the current teacher
     */
    protected static function getAvailableSubjects(): array
    {
        return \App\Models\AcademicSubject::query()
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get available grade levels
     */
    protected static function getAvailableGradeLevels(): array
    {
        return \App\Models\GradeLevel::query()
            ->pluck('name', 'id')
            ->toArray();
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
            'view' => Pages\ViewInteractiveCourse::route('/{record}'),
            'edit' => Pages\EditInteractiveCourse::route('/{record}/edit'),
        ];
    }
}
