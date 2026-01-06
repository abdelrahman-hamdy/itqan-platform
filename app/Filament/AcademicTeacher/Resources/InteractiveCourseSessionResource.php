<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Filament\AcademicTeacher\Resources\InteractiveCourseSessionResource\Pages;
use App\Filament\Shared\Resources\BaseInteractiveCourseSessionResource;
use App\Models\InteractiveCourseSession;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Interactive Course Session Resource for AcademicTeacher Panel
 *
 * Teachers can view and manage sessions for their own courses only.
 * Limited permissions compared to SuperAdmin.
 * Extends BaseInteractiveCourseSessionResource for shared form/table definitions.
 */
class InteractiveCourseSessionResource extends BaseInteractiveCourseSessionResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 2;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * Filter sessions to current teacher's courses only.
     * InteractiveCourseSession doesn't have academy_id column - gets academy through course relationship.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        $teacherProfile = static::getCurrentAcademicTeacherProfile();
        $teacherAcademy = static::getCurrentTeacherAcademy();

        if ($teacherProfile) {
            // Filter by both teacher AND academy through course relationship
            $query->whereHas('course', function ($query) use ($teacherProfile, $teacherAcademy) {
                $query->where('assigned_teacher_id', $teacherProfile->id);

                // Also filter by academy through course
                if ($teacherAcademy) {
                    $query->where('academy_id', $teacherAcademy->id);
                }
            });
        } elseif ($teacherAcademy) {
            // If no teacher profile but has academy, filter by academy only
            $query->whereHas('course', function ($query) use ($teacherAcademy) {
                $query->where('academy_id', $teacherAcademy->id);
            });
        }

        return $query;
    }

    /**
     * Session info section - course selection filtered by teacher.
     */
    protected static function getSessionInfoFormSection(): Section
    {
        return Section::make('معلومات الجلسة الأساسية')
            ->schema([
                Forms\Components\Select::make('course_id')
                    ->relationship('course', 'title', function ($query) {
                        $teacherProfile = static::getCurrentAcademicTeacherProfile();
                        if ($teacherProfile) {
                            $query->where('assigned_teacher_id', $teacherProfile->id);
                        }
                    })
                    ->label('الدورة')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('session_code')
                    ->label('رمز الجلسة')
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\TextInput::make('session_number')
                    ->label('رقم الجلسة')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->helperText('رقم الجلسة ضمن الدورة'),
            ])->columns(2);
    }

    /**
     * Limited table actions for teachers.
     */
    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),

                static::makeStartSessionAction(),
                static::makeCompleteSessionAction(),
                static::makeCancelSessionAction('teacher'),
                static::makeJoinMeetingAction(),
            ]),
        ];
    }

    /**
     * Bulk actions for teachers.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ];
    }

    // ========================================
    // Additional Form Sections (Teacher-specific)
    // ========================================

    /**
     * Add attendance count display section.
     */
    protected static function getAdditionalFormSections(): array
    {
        return [
            static::getAttendanceInfoSection(),
        ];
    }

    /**
     * Attendance info section - read-only for teachers.
     */
    protected static function getAttendanceInfoSection(): Section
    {
        return Section::make('معلومات الحضور')
            ->schema([
                Forms\Components\TextInput::make('attendance_count')
                    ->label('عدد الحضور')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('يتم التحديث تلقائياً'),
            ])->columns(2);
    }

    // ========================================
    // Table Columns Override (Teacher-specific)
    // ========================================

    /**
     * Add attendance count column for teacher view.
     */
    protected static function getTableColumns(): array
    {
        $columns = parent::getTableColumns();

        // Add attendance count before created_at
        $attendanceColumn = Tables\Columns\TextColumn::make('attendance_count')
            ->label('عدد الحضور')
            ->numeric()
            ->sortable();

        $result = [];
        foreach ($columns as $column) {
            // Insert attendance column before homework_assigned
            if ($column->getName() === 'homework_assigned') {
                $result[] = $attendanceColumn;
            }
            $result[] = $column;
        }

        return $result;
    }

    // ========================================
    // Table Filters Override (Teacher-specific)
    // ========================================

    /**
     * Course filter scoped to teacher's courses.
     */
    protected static function getTableFilters(): array
    {
        return [
            ...parent::getTableFilters(),

            Tables\Filters\SelectFilter::make('course_id')
                ->label('الدورة')
                ->relationship('course', 'title', function ($query) {
                    $teacherProfile = static::getCurrentAcademicTeacherProfile();
                    if ($teacherProfile) {
                        $query->where('assigned_teacher_id', $teacherProfile->id);
                    }
                })
                ->searchable(),
        ];
    }

    // ========================================
    // Eloquent Query Override
    // ========================================

    /**
     * Override to bypass parent's academy_id filter.
     * InteractiveCourseSession doesn't have academy_id column.
     */
    public static function getEloquentQuery(): Builder
    {
        // Get base model query directly to bypass parent's academy_id filter
        $query = static::getModel()::query()
            ->with([
                'course',
                'course.assignedTeacher',
                'course.assignedTeacher.user',
            ]);

        return static::scopeEloquentQuery($query);
    }

    // ========================================
    // Helper Methods for Current Teacher
    // ========================================

    /**
     * Get the current logged-in teacher's profile.
     */
    protected static function getCurrentAcademicTeacherProfile(): ?\App\Models\AcademicTeacherProfile
    {
        return Auth::user()?->academicTeacherProfile;
    }

    /**
     * Get the current teacher's academy.
     */
    protected static function getCurrentTeacherAcademy(): ?\App\Models\Academy
    {
        return Auth::user()?->academy;
    }

    // ========================================
    // Authorization Overrides
    // ========================================

    /**
     * Teachers can create sessions for their courses.
     */
    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        if (! $user->academicTeacherProfile) {
            return false;
        }

        return $record->course?->assigned_teacher_id === $user->academicTeacherProfile->id;
    }

    public static function canView(Model $record): bool
    {
        $user = Auth::user();

        if (! $user->academicTeacherProfile) {
            return false;
        }

        return $record->course?->assigned_teacher_id === $user->academicTeacherProfile->id;
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        if (! $user->academicTeacherProfile) {
            return false;
        }

        // Only allow deletion of scheduled sessions
        return $record->course?->assigned_teacher_id === $user->academicTeacherProfile->id &&
               $record->status === \App\Enums\SessionStatus::SCHEDULED;
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInteractiveCourseSessions::route('/'),
            'create' => Pages\CreateInteractiveCourseSession::route('/create'),
            'view' => Pages\ViewInteractiveCourseSession::route('/{record}'),
            'edit' => Pages\EditInteractiveCourseSession::route('/{record}/edit'),
        ];
    }
}
