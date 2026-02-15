<?php

namespace App\Filament\Teacher\Resources;

use App\Enums\QuizAssignableType;
use App\Filament\Shared\Resources\BaseQuizResource;
use App\Filament\Teacher\Resources\QuizResource\Pages;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;

/**
 * Quiz Resource for Teacher (Quran) Panel
 *
 * Extends BaseQuizResource for shared functionality.
 * Configures assignment for Quran circles.
 */
class QuizResource extends BaseQuizResource
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
        $teacherId = auth()->user()->quranTeacherProfile?->id;

        if (! $type || ! $teacherId) {
            return [];
        }

        if ($type === QuranCircle::class) {
            return QuranCircle::where('quran_teacher_id', $teacherId)
                ->pluck('name', 'id')
                ->toArray();
        }

        return QuranIndividualCircle::where('quran_teacher_id', $teacherId)
            ->with('student')
            ->get()
            ->mapWithKeys(fn ($c) => [$c->id => $c->student?->first_name.' '.$c->student?->last_name])
            ->toArray();
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
            'index' => Pages\ListQuizzes::route('/'),
            'create' => Pages\CreateQuiz::route('/create'),
            'edit' => Pages\EditQuiz::route('/{record}/edit'),
        ];
    }
}
