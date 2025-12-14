<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Filament\Shared\Resources\BaseQuizResource;
use App\Filament\AcademicTeacher\Resources\QuizResource\Pages;
use App\Models\AcademicSubscription;
use App\Models\InteractiveCourse;

/**
 * Quiz Resource for AcademicTeacher Panel
 *
 * Extends BaseQuizResource for shared functionality.
 * Configures assignment for academic subscriptions and interactive courses.
 */
class QuizResource extends BaseQuizResource
{
    /**
     * Get assignable types for Academic teachers.
     */
    protected static function getAssignableTypes(): array
    {
        return [
            AcademicSubscription::class => 'اشتراك أكاديمي',
            InteractiveCourse::class => 'دورة تفاعلية',
        ];
    }

    /**
     * Get options for the assignable_id based on type.
     */
    protected static function getAssignableOptions(?string $type): array
    {
        $teacherId = auth()->user()->academicTeacherProfile?->id;

        if (!$type || !$teacherId) {
            return [];
        }

        if ($type === AcademicSubscription::class) {
            return AcademicSubscription::where('teacher_id', $teacherId)
                ->with('student')
                ->get()
                ->mapWithKeys(fn ($s) => [
                    $s->id => ($s->student?->first_name ?? '') . ' ' . ($s->student?->last_name ?? '') . ' - ' . ($s->subject_name ?? 'درس خاص')
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
            'index' => Pages\ListQuizzes::route('/'),
            'create' => Pages\CreateQuiz::route('/create'),
            'edit' => Pages\EditQuiz::route('/{record}/edit'),
        ];
    }
}
