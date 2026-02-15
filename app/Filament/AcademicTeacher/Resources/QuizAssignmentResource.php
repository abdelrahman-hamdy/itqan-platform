<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Enums\QuizAssignableType;
use App\Filament\AcademicTeacher\Resources\QuizAssignmentResource\Pages;
use App\Filament\Shared\Resources\BaseQuizAssignmentResource;
use App\Models\AcademicIndividualLesson;
use App\Models\InteractiveCourse;

/**
 * Quiz Assignment Resource for AcademicTeacher Panel
 *
 * Extends BaseQuizAssignmentResource for shared functionality.
 * Configures assignment for academic lessons and interactive courses.
 */
class QuizAssignmentResource extends BaseQuizAssignmentResource
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
        $teacherId = static::getTeacherId();

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

        if ($type === InteractiveCourse::class) {
            return InteractiveCourse::where('assigned_teacher_id', $teacherId)
                ->pluck('title', 'id')
                ->toArray();
        }

        return [];
    }

    /**
     * Get the current Academic teacher's ID.
     */
    protected static function getTeacherId(): ?int
    {
        return auth()->user()->academicTeacherProfile?->id;
    }

    /**
     * Get the IDs for query filtering based on assignable types.
     */
    protected static function getTeacherAssignableIds(): array
    {
        $teacherId = static::getTeacherId();

        if (! $teacherId) {
            return [];
        }

        return [
            AcademicIndividualLesson::class => AcademicIndividualLesson::where('academic_teacher_id', $teacherId)->pluck('id')->toArray(),
            InteractiveCourse::class => InteractiveCourse::where('assigned_teacher_id', $teacherId)->pluck('id')->toArray(),
        ];
    }

    /**
     * Format the assignable name for display.
     */
    protected static function formatAssignableName($record): string
    {
        $assignable = $record->assignable;

        if (! $assignable) {
            return '-';
        }

        if ($record->assignable_type === AcademicIndividualLesson::class) {
            return ($assignable->name ?? '').' - '.($assignable->student?->first_name ?? '').' '.($assignable->student?->last_name ?? '');
        }

        return $assignable->title ?? $assignable->name ?? $assignable->id;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuizAssignments::route('/'),
            'create' => Pages\CreateQuizAssignment::route('/create'),
            'edit' => Pages\EditQuizAssignment::route('/{record}/edit'),
        ];
    }
}
