<?php

namespace App\Filament\Academy\Resources;

use App\Enums\UserType;
use App\Filament\Academy\Resources\AcademicIndividualLessonResource\Pages\CreateAcademicIndividualLesson;
use App\Filament\Academy\Resources\AcademicIndividualLessonResource\Pages\EditAcademicIndividualLesson;
use App\Filament\Academy\Resources\AcademicIndividualLessonResource\Pages\ListAcademicIndividualLessons;
use App\Filament\Academy\Resources\AcademicIndividualLessonResource\Pages\ViewAcademicIndividualLesson;
use App\Filament\Shared\Resources\BaseAcademicIndividualLessonResource;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSubject;
use App\Models\AcademicTeacherProfile;
use App\Models\User;
use App\Services\AcademyContextService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Academic Individual Lesson Resource for Academy Panel
 *
 * Academy admins can manage all individual lessons in their academy.
 * Shows all lessons (not filtered by teacher).
 */
class AcademicIndividualLessonResource extends BaseAcademicIndividualLessonResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 5;

    /**
     * Filter lessons to current academy only, including soft-deleted.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query
            ->where('academy_id', auth()->user()->academy_id)
            // Include soft-deleted records for admin management
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /**
     * Lesson info section with teacher, student, subject, and grade level selection.
     */
    protected static function getLessonInfoFormSection(): Section
    {
        $academyId = auth()->user()->academy_id;

        return Section::make('معلومات الدرس الأساسية')
            ->schema([
                Hidden::make('academy_id')
                    ->default(fn () => $academyId),

                TextInput::make('name')
                    ->label('اسم الدرس')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('وصف الدرس')
                    ->rows(3)
                    ->columnSpanFull(),

                Select::make('academic_teacher_id')
                    ->label('المعلم')
                    ->options(function () use ($academyId) {
                        return AcademicTeacherProfile::where('academy_id', $academyId)
                            ->with('user')
                            ->get()
                            ->mapWithKeys(fn ($profile) => [
                                $profile->id => $profile->user
                                    ? trim(($profile->user->first_name ?? '').' '.($profile->user->last_name ?? '')) ?: 'معلم #'.$profile->id
                                    : 'معلم #'.$profile->id,
                            ])
                            ->toArray();
                    })
                    ->searchable()
                    ->required(),

                Select::make('student_id')
                    ->label('الطالب')
                    ->options(function () use ($academyId) {
                        return User::where('academy_id', $academyId)
                            ->where('user_type', UserType::STUDENT->value)
                            ->get()
                            ->mapWithKeys(fn ($user) => [
                                $user->id => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'طالب #'.$user->id,
                            ])
                            ->toArray();
                    })
                    ->searchable()
                    ->required(),

                Select::make('academic_subject_id')
                    ->label('المادة')
                    ->options(
                        AcademicSubject::where('academy_id', $academyId)
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->required(),

                Select::make('academic_grade_level_id')
                    ->label('المستوى الدراسي')
                    ->options(
                        AcademicGradeLevel::where('academy_id', $academyId)
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->required(),
            ])
            ->columns(2);
    }

    /**
     * Table actions with view_sessions and soft deletes.
     */
    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
                Action::make('view_sessions')
                    ->label('الجلسات')
                    ->icon('heroicon-o-calendar-days')
                    ->url(fn (AcademicIndividualLesson $record): string => AcademicSessionResource::getUrl('index', [
                        'tableFilters[academic_individual_lesson_id][value]' => $record->id,
                    ])),
                DeleteAction::make()
                    ->label('حذف'),
            ]),
        ];
    }

    /**
     * Full bulk actions with soft deletes.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->label('حذف المحدد'),
            ]),
        ];
    }

    // ========================================
    // Additional Form Sections
    // ========================================

    /**
     * Add timing and notes sections for academy admins.
     */
    protected static function getAdditionalFormSections(): array
    {
        return [
            static::getTimingFormSection(),
            static::getNotesFormSection(),
        ];
    }

    /**
     * Timing section.
     */
    protected static function getTimingFormSection(): Section
    {
        return Section::make('التوقيت')
            ->schema([
                DateTimePicker::make('started_at')
                    ->label('تاريخ البدء')
                    ->timezone(AcademyContextService::getTimezone()),

                DateTimePicker::make('completed_at')
                    ->label('تاريخ الإكمال')
                    ->timezone(AcademyContextService::getTimezone()),

                DateTimePicker::make('last_session_at')
                    ->label('آخر جلسة')
                    ->timezone(AcademyContextService::getTimezone())
                    ->disabled(),
            ])
            ->columns(3);
    }

    /**
     * Notes section.
     */
    protected static function getNotesFormSection(): Section
    {
        return Section::make('ملاحظات')
            ->schema([
                Grid::make(2)
                    ->schema([
                        Textarea::make('admin_notes')
                            ->label('ملاحظات الإدارة')
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('ملاحظات داخلية للإدارة'),
                        Textarea::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->rows(3)
                            ->maxLength(2000)
                            ->helperText('ملاحظات مرئية للمشرف والإدارة فقط'),
                    ]),
            ]);
    }

    // ========================================
    // Table Columns Override
    // ========================================

    /**
     * Add teacher column for academy admins.
     */
    protected static function getTableColumns(): array
    {
        $columns = parent::getTableColumns();

        // Add teacher column after student
        $teacherColumn = TextColumn::make('academicTeacher.user.name')
            ->label('المعلم')
            ->searchable()
            ->sortable();

        // Insert columns at appropriate positions
        $result = [];
        foreach ($columns as $column) {
            $result[] = $column;

            // Add teacher column after student
            if ($column->getName() === 'student.name') {
                array_splice($result, count($result) - 1, 0, [$teacherColumn]);
            }
        }

        return $result;
    }

    // ========================================
    // Table Filters Override
    // ========================================

    /**
     * Extended filters with teacher and trashed.
     */
    protected static function getTableFilters(): array
    {
        return [
            ...parent::getTableFilters(),

            SelectFilter::make('academic_teacher_id')
                ->label('المعلم')
                ->relationship('academicTeacher.user', 'name')
                ->searchable()
                ->preload(),

            TrashedFilter::make()
                ->label(__('filament.filters.trashed')),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAcademicIndividualLessons::route('/'),
            'create' => CreateAcademicIndividualLesson::route('/create'),
            'view' => ViewAcademicIndividualLesson::route('/{record}'),
            'edit' => EditAcademicIndividualLesson::route('/{record}/edit'),
        ];
    }
}
