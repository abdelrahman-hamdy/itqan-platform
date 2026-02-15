<?php

namespace App\Filament\Teacher\Resources;

use App\Enums\QuizAssignableType;
use App\Filament\Shared\Resources\BaseQuizAssignmentResource;
use App\Filament\Teacher\Resources\QuizAssignmentResource\Pages;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;

/**
 * Quiz Assignment Resource for Teacher (Quran) Panel
 *
 * Extends BaseQuizAssignmentResource for shared functionality.
 * Configures assignment for Quran circles.
 */
class QuizAssignmentResource extends BaseQuizAssignmentResource
{
    /**
     * Get assignable types for Quran teachers.
     */
    protected static function getAssignableTypes(): array
    {
        return [
            QuizAssignableType::QURAN_CIRCLE->value => QuizAssignableType::QURAN_CIRCLE->label(),
            QuizAssignableType::QURAN_INDIVIDUAL_CIRCLE->value => QuizAssignableType::QURAN_INDIVIDUAL_CIRCLE->label(),
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

        if ($type === QuranCircle::class) {
            return QuranCircle::where('quran_teacher_id', $teacherId)
                ->pluck('name', 'id')
                ->toArray();
        }

        if ($type === QuranIndividualCircle::class) {
            return QuranIndividualCircle::where('quran_teacher_id', $teacherId)
                ->with('student')
                ->get()
                ->mapWithKeys(fn ($c) => [
                    $c->id => ($c->student?->first_name ?? '').' '.($c->student?->last_name ?? ''),
                ])
                ->toArray();
        }

        return [];
    }

    /**
     * Get the current Quran teacher's user ID.
     */
    protected static function getTeacherId(): ?int
    {
        return auth()->id();
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
            QuranCircle::class => QuranCircle::where('quran_teacher_id', $teacherId)->pluck('id')->toArray(),
            QuranIndividualCircle::class => QuranIndividualCircle::where('quran_teacher_id', $teacherId)->pluck('id')->toArray(),
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

        if ($record->assignable_type === QuranIndividualCircle::class) {
            return ($assignable->student?->first_name ?? '').' '.($assignable->student?->last_name ?? '');
        }

        return $assignable->name ?? $assignable->id;
    }

    /**
     * Override labels for Quran context.
     */
    protected static function getAssignableTypeLabel(): string
    {
        return 'نوع الحلقة';
    }

    protected static function getAssignableTargetLabel(): string
    {
        return 'الحلقة';
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
