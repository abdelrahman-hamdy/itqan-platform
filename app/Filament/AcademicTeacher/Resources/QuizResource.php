<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Filament\AcademicTeacher\Resources\QuizResource\Pages\ListQuizzes;
use App\Filament\AcademicTeacher\Resources\QuizResource\Pages\CreateQuiz;
use App\Filament\AcademicTeacher\Resources\QuizResource\Pages\EditQuiz;
use App\Enums\QuizAssignableType;
use App\Filament\AcademicTeacher\Resources\QuizResource\Pages;
use App\Filament\Shared\Resources\BaseQuizResource;
use App\Models\AcademicIndividualLesson;
use App\Models\InteractiveCourse;

/**
 * Quiz Resource for AcademicTeacher Panel
 *
 * Extends BaseQuizResource for shared functionality.
 * Configures assignment for academic lessons and interactive courses.
 */
class QuizResource extends BaseQuizResource
{
    /**
     * Get assignable types for Academic teachers.
     */
    protected static function getAssignableTypes(): array
    {
        return [
            QuizAssignableType::ACADEMIC_INDIVIDUAL_LESSON->value => QuizAssignableType::ACADEMIC_INDIVIDUAL_LESSON->label(),
            QuizAssignableType::INTERACTIVE_COURSE->value => QuizAssignableType::INTERACTIVE_COURSE->label(),
        ];
    }

    /**
     * Get options for the assignable_id based on type.
     */
    protected static function getAssignableOptions(?string $type): array
    {
        $teacherId = auth()->user()->academicTeacherProfile?->id;

        if (! $type || ! $teacherId) {
            return [];
        }

        if ($type === AcademicIndividualLesson::class) {
            return AcademicIndividualLesson::where('academic_teacher_id', $teacherId)
                ->with('student')
                ->get()
                ->mapWithKeys(fn ($l) => [
                    $l->id => ($l->name ?? '').' - '.($l->student?->first_name ?? '').' '.($l->student?->last_name ?? ''),
                ])
                ->toArray();
        }

        return InteractiveCourse::where('assigned_teacher_id', $teacherId)
            ->pluck('title', 'id')
            ->toArray();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuizzes::route('/'),
            'create' => CreateQuiz::route('/create'),
            'edit' => EditQuiz::route('/{record}/edit'),
        ];
    }
}
