<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Enums\UserType;
use App\Filament\AcademicTeacher\Resources\AcademicIndividualLessonResource\Pages;
use App\Filament\AcademicTeacher\Resources\AcademicIndividualLessonResource\Pages\CreateAcademicIndividualLesson;
use App\Filament\AcademicTeacher\Resources\AcademicIndividualLessonResource\Pages\EditAcademicIndividualLesson;
use App\Filament\AcademicTeacher\Resources\AcademicIndividualLessonResource\Pages\ListAcademicIndividualLessons;
use App\Filament\AcademicTeacher\Resources\AcademicIndividualLessonResource\Pages\ViewAcademicIndividualLesson;
use App\Filament\Shared\Resources\BaseAcademicIndividualLessonResource;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\AcademicTeacherProfile;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
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

    protected static string|\UnitEnum|null $navigationGroup = 'جلساتي';

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
                Hidden::make('academy_id')
                    ->default(fn () => $teacherProfile?->academy_id),

                Hidden::make('academic_teacher_id')
                    ->default(fn () => $teacherProfile?->id),

                TextInput::make('name')
                    ->label('اسم الدرس')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('وصف الدرس')
                    ->rows(3)
                    ->columnSpanFull(),

                Select::make('student_id')
                    ->label('الطالب')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search) {
                        return User::query()
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
                        $user = User::find($value);
                        if (! $user) {
                            return null;
                        }

                        return trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: $user->name ?? 'طالب #'.$user->id;
                    })
                    ->required(),

                Select::make('academic_subject_id')
                    ->label('المادة')
                    ->options(
                        AcademicSubject::where('academy_id', $teacherProfile?->academy_id ?? 0)
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->required(),

                Select::make('academic_grade_level_id')
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
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
            ]),
        ];
    }

    /**
     * Bulk actions for teachers.
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
                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3),

                Textarea::make('teacher_notes')
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
    protected static function getCurrentAcademicTeacherProfile(): ?AcademicTeacherProfile
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
            'index' => ListAcademicIndividualLessons::route('/'),
            'create' => CreateAcademicIndividualLesson::route('/create'),
            'view' => ViewAcademicIndividualLesson::route('/{record}'),
            'edit' => EditAcademicIndividualLesson::route('/{record}/edit'),
        ];
    }
}
