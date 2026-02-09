<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Enums\UserType;
use App\Filament\AcademicTeacher\Resources\AcademicIndividualLessonResource\Pages;
use App\Filament\Shared\Resources\BaseAcademicIndividualLessonResource;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Academic Individual Lesson Resource for AcademicTeacher Panel
 *
 * Teachers can view and manage their own lessons only.
 * Limited permissions compared to SuperAdmin.
 * Extends BaseAcademicIndividualLessonResource for shared form/table definitions.
 */
class AcademicIndividualLessonResource extends BaseAcademicIndividualLessonResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 4;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * Filter lessons to current teacher only.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        $teacherProfile = static::getCurrentAcademicTeacherProfile();

        if ($teacherProfile) {
            return $query->where('academic_teacher_id', $teacherProfile->id);
        }

        return $query->whereRaw('1 = 0'); // Return no results
    }

    /**
     * Lesson info section - teacher is auto-assigned.
     */
    protected static function getLessonInfoFormSection(): Section
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        return Section::make('معلومات الدرس الأساسية')
            ->schema([
                // Hidden fields for auto-assignment
                Forms\Components\Hidden::make('academy_id')
                    ->default(fn () => $teacherProfile?->academy_id),

                Forms\Components\Hidden::make('academic_teacher_id')
                    ->default(fn () => $teacherProfile?->id),

                Forms\Components\TextInput::make('name')
                    ->label('اسم الدرس')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->label('وصف الدرس')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Select::make('student_id')
                    ->label('الطالب')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search) {
                        return \App\Models\User::query()
                            ->where('user_type', UserType::STUDENT->value)
                            ->where(function ($q) use ($search) {
                                $q->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn ($user) => [
                                $user->id => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: $user->name ?? 'طالب #'.$user->id,
                            ]);
                    })
                    ->getOptionLabelUsing(function ($value) {
                        $user = \App\Models\User::find($value);
                        if (! $user) {
                            return null;
                        }

                        return trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: $user->name ?? 'طالب #'.$user->id;
                    })
                    ->required(),

                Forms\Components\Select::make('academic_subject_id')
                    ->label('المادة')
                    ->options(
                        AcademicSubject::where('academy_id', $teacherProfile?->academy_id ?? 0)
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('academic_grade_level_id')
                    ->label('المستوى الدراسي')
                    ->options(
                        AcademicGradeLevel::where('academy_id', $teacherProfile?->academy_id ?? 0)
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->required(),
            ])
            ->columns(2);
    }

    /**
     * Limited table actions for teachers.
     */
    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make()
                ->label('عرض'),
            Tables\Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }

    /**
     * Bulk actions for teachers.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('حذف المحدد'),
            ]),
        ];
    }

    // ========================================
    // Additional Form Sections (Teacher-specific)
    // ========================================

    /**
     * Add teacher notes section.
     */
    protected static function getAdditionalFormSections(): array
    {
        return [
            static::getTeacherNotesFormSection(),
        ];
    }

    /**
     * Teacher notes section.
     */
    protected static function getTeacherNotesFormSection(): Section
    {
        return Section::make('الملاحظات')
            ->schema([
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3),

                Forms\Components\Textarea::make('teacher_notes')
                    ->label('ملاحظات المعلم')
                    ->rows(3)
                    ->helperText('ملاحظات خاصة بك حول هذا الدرس'),
            ]);
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

    // ========================================
    // Authorization Overrides
    // ========================================

    /**
     * Check if user can access this resource.
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && $user->isAcademicTeacher();
    }

    /**
     * Teachers can create lessons.
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

        return $record->academic_teacher_id === $user->academicTeacherProfile->id;
    }

    public static function canView(Model $record): bool
    {
        $user = Auth::user();

        if (! $user->academicTeacherProfile) {
            return false;
        }

        return $record->academic_teacher_id === $user->academicTeacherProfile->id;
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        if (! $user->academicTeacherProfile) {
            return false;
        }

        // Only allow deletion of lessons without completed sessions
        return $record->academic_teacher_id === $user->academicTeacherProfile->id &&
               $record->sessions_completed === 0;
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicIndividualLessons::route('/'),
            'create' => Pages\CreateAcademicIndividualLesson::route('/create'),
            'view' => Pages\ViewAcademicIndividualLesson::route('/{record}'),
            'edit' => Pages\EditAcademicIndividualLesson::route('/{record}/edit'),
        ];
    }
}
