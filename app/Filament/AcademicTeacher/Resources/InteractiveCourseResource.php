<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Enums\InteractiveCourseStatus;
use App\Filament\AcademicTeacher\Resources\InteractiveCourseResource\Pages;
use App\Filament\Shared\Resources\BaseInteractiveCourseResource;
use App\Models\InteractiveCourse;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

/**
 * Interactive Course Resource for AcademicTeacher Panel
 *
 * Teachers can view and manage their own assigned courses only.
 * Limited permissions compared to SuperAdmin.
 * Extends BaseInteractiveCourseResource for shared form/table definitions.
 */
class InteractiveCourseResource extends BaseInteractiveCourseResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?string $navigationLabel = 'الدورات التفاعلية';

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 3;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * Filter courses to current teacher only.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user->isAcademicTeacher() || ! $user->academicTeacherProfile) {
            return $query->whereRaw('1 = 0'); // Return no results
        }

        return $query->where('assigned_teacher_id', $user->academicTeacherProfile->id);
    }

    /**
     * Get form schema for AcademicTeacher - limited fields.
     */
    protected static function getFormSchema(): array
    {
        return [
            Section::make('معلومات الدورة الأساسية')
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
                                ->options(InteractiveCourseStatus::options())
                                ->required()
                                ->helperText('يمكن للمعلم تعديل حالة الدورة'),
                        ]),
                ]),

            Section::make('تفاصيل الدورة')
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

            Section::make('التسعير والتسجيل')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('price_per_student')
                                ->label('سعر الطالب الواحد')
                                ->numeric()
                                ->minValue(0)
                                ->prefix(getCurrencySymbol())
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

            Section::make('المحتوى والوصف')
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

            Section::make('التواريخ والجدولة')
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

            Section::make('إعدادات المعلم')
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

    /**
     * Limited table actions for teachers.
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
                ->url(fn (InteractiveCourse $record): string => InteractiveCourseSessionResource::getUrl('index', [
                    'tableFilters' => [
                        'course_id' => ['value' => $record->id],
                    ],
                ])
                ),

            Tables\Actions\Action::make('view_enrolled_students')
                ->label('الطلاب المسجلين')
                ->icon('heroicon-m-users')
                ->color('success')
                ->modalHeading(fn (InteractiveCourse $record): string => 'الطلاب المسجلين في: '.$record->title)
                ->modalDescription(fn (InteractiveCourse $record): string => 'إجمالي الطلاب المسجلين: '.$record->enrolledStudents()->count().' من أصل '.$record->max_students
                )
                ->modalContent(fn (InteractiveCourse $record): HtmlString => new HtmlString(
                    '<div class="space-y-2">'.
                    $record->enrolledStudents()->with('user')->get()
                        ->map(fn ($student) => '<div class="flex items-center gap-2"><span class="text-gray-600">•</span><span>'.e($student->user->name).'</span><span class="text-gray-500 text-sm">('.e($student->user->email).')</span></div>')
                        ->join('').
                    '</div>'
                )
                )
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('إغلاق'),
        ];
    }

    /**
     * Bulk actions for teachers.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkAction::make('update_status')
                ->label('تحديث الحالة')
                ->icon('heroicon-m-pencil-square')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('الحالة الجديدة')
                        ->options([
                            InteractiveCourseStatus::PUBLISHED->value => InteractiveCourseStatus::PUBLISHED->label(),
                            InteractiveCourseStatus::ACTIVE->value => InteractiveCourseStatus::ACTIVE->label(),
                            InteractiveCourseStatus::COMPLETED->value => InteractiveCourseStatus::COMPLETED->label(),
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

    // ========================================
    // Table Columns Override (Teacher-specific)
    // ========================================

    /**
     * Table columns for teacher (no teacher column needed).
     */
    protected static function getTableColumns(): array
    {
        $columns = parent::getTableColumns();

        // Remove the teacher column since teacher only sees their own courses
        return array_filter($columns, fn ($column) => $column->getName() !== 'assignedTeacher.user.name');
    }

    // ========================================
    // Table Filters Override (Teacher-specific)
    // ========================================

    /**
     * Teacher-specific filters.
     */
    protected static function getTableFilters(): array
    {
        return [
            ...parent::getTableFilters(),

            TernaryFilter::make('has_enrolled_students')
                ->label('لديه طلاب مسجلين')
                ->queries(
                    true: fn (Builder $query) => $query->whereHas('enrolledStudents'),
                    false: fn (Builder $query) => $query->doesntHave('enrolledStudents'),
                ),
        ];
    }

    // ========================================
    // Helper Methods
    // ========================================

    /**
     * Get available subjects.
     */
    protected static function getAvailableSubjects(): array
    {
        return \App\Models\AcademicSubject::query()
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get available grade levels.
     */
    protected static function getAvailableGradeLevels(): array
    {
        return \App\Models\AcademicGradeLevel::query()
            ->pluck('name', 'id')
            ->toArray();
    }

    // ========================================
    // Authorization Overrides
    // ========================================

    /**
     * Teachers cannot create new courses.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        $user = Auth::user();

        if (! $user->isAcademicTeacher() || ! $user->academicTeacherProfile) {
            return false;
        }

        return $record->assigned_teacher_id === $user->academicTeacherProfile->id;
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        if (! $user->isAcademicTeacher() || ! $user->academicTeacherProfile) {
            return false;
        }

        return $record->assigned_teacher_id === $user->academicTeacherProfile->id;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInteractiveCourses::route('/'),
            'view' => Pages\ViewInteractiveCourse::route('/{record}'),
            'edit' => Pages\EditInteractiveCourse::route('/{record}/edit'),
        ];
    }
}
