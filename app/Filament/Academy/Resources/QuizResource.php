<?php

namespace App\Filament\Academy\Resources;

use App\Enums\QuizAssignableType;
use App\Filament\Academy\Resources\QuizResource\Pages\CreateQuiz;
use App\Filament\Academy\Resources\QuizResource\Pages\EditQuiz;
use App\Filament\Academy\Resources\QuizResource\Pages\ListQuizzes;
use App\Filament\Academy\Resources\QuizResource\Pages\ViewQuiz;
use App\Filament\Resources\QuizResource\RelationManagers\QuestionsRelationManager;
use App\Filament\Shared\Resources\BaseQuizResource;
use App\Models\AcademicIndividualLesson;
use App\Models\InteractiveCourse;
use App\Models\Quiz;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\RecordedCourse;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Table;

/**
 * Quiz Resource for Academy Panel
 *
 * Academy admins can manage all quizzes in their academy.
 * Supports assignment to all education unit types.
 */
class QuizResource extends BaseQuizResource
{
    protected static ?string $navigationLabel = 'الاختبارات';

    protected static ?int $navigationSort = 1;

    /**
     * Navigation badge showing quizzes without questions (warning)
     */
    public static function getNavigationBadge(): ?string
    {
        $tenant = Filament::getTenant();
        if (!$tenant) {
            return null;
        }

        $count = Quiz::query()
            ->doesntHave('questions')
            ->where('academy_id', $tenant->id)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * Badge color - warning for quizzes without questions
     */
    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    /**
     * Badge tooltip
     */
    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'اختبارات بدون أسئلة';
    }

    /**
     * Academy admins can assign quizzes to all education unit types.
     */
    protected static function getAssignableTypes(): array
    {
        return QuizAssignableType::options();
    }

    /**
     * Get options for the assignable_id based on type, scoped to academy.
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

    public static function table(Table $table): Table
    {
        $table = parent::table($table);

        return $table
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('عرض'),
                    EditAction::make()
                        ->label('تعديل'),
                    static::getAssignAction(),
                    DeleteAction::make()
                        ->label('حذف'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuizzes::route('/'),
            'create' => CreateQuiz::route('/create'),
            'view' => ViewQuiz::route('/{record}'),
            'edit' => EditQuiz::route('/{record}/edit'),
        ];
    }
}
