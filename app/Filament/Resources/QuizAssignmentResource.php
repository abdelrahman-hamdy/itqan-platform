<?php

namespace App\Filament\Resources;

use App\Enums\QuizAssignableType;
use App\Filament\Resources\QuizAssignmentResource\Pages\CreateQuizAssignment;
use App\Filament\Resources\QuizAssignmentResource\Pages\EditQuizAssignment;
use App\Filament\Resources\QuizAssignmentResource\Pages\ListQuizAssignments;
use App\Filament\Shared\Resources\BaseQuizAssignmentResource;
use App\Models\AcademicIndividualLesson;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\RecordedCourse;
use App\Services\AcademyContextService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Quiz Assignment Resource for SuperAdmin Panel
 *
 * SuperAdmin can manage all quiz assignments across academies.
 * Uses AcademyContextService for optional academy scoping.
 */
class QuizAssignmentResource extends BaseQuizAssignmentResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الاختبارات';

    protected static ?string $navigationLabel = 'تعيين الاختبارات';

    protected static function getAssignableTypes(): array
    {
        return QuizAssignableType::options();
    }

    protected static function getAssignableOptions(?string $type): array
    {
        $currentAcademy = AcademyContextService::getCurrentAcademy();

        if (! $type) {
            return [];
        }

        return match ($type) {
            QuranCircle::class => QuranCircle::when($currentAcademy, fn ($q) => $q->where('academy_id', $currentAcademy->id))
                ->limit(200)->pluck('name', 'id')->toArray(),

            QuranIndividualCircle::class => QuranIndividualCircle::when($currentAcademy, fn ($q) => $q->where('academy_id', $currentAcademy->id))
                ->with('student')->limit(200)->get()
                ->mapWithKeys(fn ($c) => [$c->id => ($c->student?->first_name ?? '').' '.($c->student?->last_name ?? '')])
                ->toArray(),

            AcademicIndividualLesson::class => AcademicIndividualLesson::when($currentAcademy, fn ($q) => $q->where('academy_id', $currentAcademy->id))
                ->with('student')->limit(200)->get()
                ->mapWithKeys(fn ($l) => [$l->id => $l->name ?? ($l->student?->name ?? 'درس #'.$l->id)])
                ->toArray(),

            InteractiveCourse::class => InteractiveCourse::when($currentAcademy, fn ($q) => $q->where('academy_id', $currentAcademy->id))
                ->limit(200)->pluck('title', 'id')->toArray(),

            RecordedCourse::class => RecordedCourse::when($currentAcademy, fn ($q) => $q->where('academy_id', $currentAcademy->id))
                ->limit(200)->pluck('title', 'id')->toArray(),

            default => [],
        };
    }

    /**
     * SuperAdmin sees all assignments — no teacher filter.
     */
    protected static function getTeacherId(): ?int
    {
        return null;
    }

    /**
     * Not used when getTeacherId() returns null.
     */
    protected static function getTeacherAssignableIds(): array
    {
        return [];
    }

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

    /**
     * Override to use AcademyContextService instead of Filament tenant
     * (SuperAdmin panel is not a tenant panel).
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $currentAcademy = AcademyContextService::getCurrentAcademy();
        if ($currentAcademy) {
            $query->whereHas('quiz', fn ($q) => $q->where('academy_id', $currentAcademy->id));
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListQuizAssignments::route('/'),
            'create' => CreateQuizAssignment::route('/create'),
            'edit'   => EditQuizAssignment::route('/{record}/edit'),
        ];
    }
}
