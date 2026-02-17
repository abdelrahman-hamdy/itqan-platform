<?php

namespace App\Filament\Academy\Resources;

use App\Enums\QuizAssignableType;
use App\Filament\Academy\Resources\QuizAssignmentResource\Pages\CreateQuizAssignment;
use App\Filament\Academy\Resources\QuizAssignmentResource\Pages\EditQuizAssignment;
use App\Filament\Academy\Resources\QuizAssignmentResource\Pages\ListQuizAssignments;
use App\Filament\Shared\Resources\BaseQuizAssignmentResource;
use App\Models\AcademicIndividualLesson;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\RecordedCourse;

/**
 * Quiz Assignment Resource for Academy Panel
 *
 * Academy admins can manage all quiz assignments in their academy.
 * Shows all assignments (not filtered by teacher).
 */
class QuizAssignmentResource extends BaseQuizAssignmentResource
{
    protected static ?int $navigationSort = 2;

    /**
     * All assignable types available for academy admins.
     */
    protected static function getAssignableTypes(): array
    {
        return QuizAssignableType::options();
    }

    /**
     * Get options scoped to the academy.
     */
    protected static function getAssignableOptions(?string $type): array
    {
        $academyId = auth()->user()->academy_id;

        if (! $type || ! $academyId) {
            return [];
        }

        return match ($type) {
            QuranCircle::class => QuranCircle::where('academy_id', $academyId)
                ->pluck('name', 'id')
                ->toArray(),

            QuranIndividualCircle::class => QuranIndividualCircle::where('academy_id', $academyId)
                ->with('student')
                ->get()
                ->mapWithKeys(fn ($c) => [$c->id => ($c->student?->first_name ?? '').' '.($c->student?->last_name ?? '')])
                ->toArray(),

            AcademicIndividualLesson::class => AcademicIndividualLesson::where('academy_id', $academyId)
                ->with('student')
                ->get()
                ->mapWithKeys(fn ($l) => [$l->id => $l->name ?? ($l->student?->name ?? 'درس #'.$l->id)])
                ->toArray(),

            InteractiveCourse::class => InteractiveCourse::where('academy_id', $academyId)
                ->pluck('title', 'id')
                ->toArray(),

            RecordedCourse::class => RecordedCourse::where('academy_id', $academyId)
                ->pluck('title', 'id')
                ->toArray(),

            default => [],
        };
    }

    /**
     * Academy admin - no teacher filter (sees all).
     */
    protected static function getTeacherId(): ?int
    {
        return null;
    }

    /**
     * Return all assignable IDs in the academy.
     */
    protected static function getTeacherAssignableIds(): array
    {
        $academyId = auth()->user()->academy_id;

        if (! $academyId) {
            return [];
        }

        return [
            QuranCircle::class => QuranCircle::where('academy_id', $academyId)->pluck('id')->toArray(),
            QuranIndividualCircle::class => QuranIndividualCircle::where('academy_id', $academyId)->pluck('id')->toArray(),
            AcademicIndividualLesson::class => AcademicIndividualLesson::where('academy_id', $academyId)->pluck('id')->toArray(),
            InteractiveCourse::class => InteractiveCourse::where('academy_id', $academyId)->pluck('id')->toArray(),
            RecordedCourse::class => RecordedCourse::where('academy_id', $academyId)->pluck('id')->toArray(),
        ];
    }

    /**
     * Format the assignable name for display in the table.
     */
    protected static function formatAssignableName($record): string
    {
        $assignable = $record->assignable;

        if (! $assignable) {
            return '-';
        }

        if ($record->assignable_type === QuranIndividualCircle::class) {
            return ($assignable->student?->first_name ?? '').' '.($assignable->student?->last_name ?? '');
        }

        if ($record->assignable_type === AcademicIndividualLesson::class) {
            return $assignable->name ?? ($assignable->student?->name ?? 'درس #'.$assignable->id);
        }

        return $assignable->name ?? $assignable->title ?? (string) $assignable->id;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuizAssignments::route('/'),
            'create' => CreateQuizAssignment::route('/create'),
            'edit' => EditQuizAssignment::route('/{record}/edit'),
        ];
    }
}
