<?php

namespace App\Filament\Academy\Resources;

use App\Enums\UserType;
use App\Filament\Academy\Resources\AcademicIndividualLessonResource\Pages\CreateAcademicIndividualLesson;
use App\Filament\Academy\Resources\AcademicIndividualLessonResource\Pages\EditAcademicIndividualLesson;
use App\Filament\Academy\Resources\AcademicIndividualLessonResource\Pages\ListAcademicIndividualLessons;
use App\Filament\Academy\Resources\AcademicIndividualLessonResource\Pages\ViewAcademicIndividualLesson;
use App\Filament\Shared\Resources\BaseAcademicIndividualLessonResource;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\AcademicTeacherProfile;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;

/**
 * Academic Individual Lesson Resource for Academy Panel
 *
 * Academy admins can manage all individual lessons in their academy.
 * Shows all lessons (not filtered by teacher).
 */
class AcademicIndividualLessonResource extends BaseAcademicIndividualLessonResource
{
    protected static string | \UnitEnum | null $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 5;

    /**
     * Filter lessons to current academy only.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query->where('academy_id', auth()->user()->academy_id);
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
                                    ? trim(($profile->user->first_name ?? '') . ' ' . ($profile->user->last_name ?? '')) ?: 'معلم #' . $profile->id
                                    : 'معلم #' . $profile->id,
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
                                $user->id => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'طالب #' . $user->id,
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
     * Table actions for academy admins.
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
     * Bulk actions for academy admins.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
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
