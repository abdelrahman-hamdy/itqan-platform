<?php

namespace App\Filament\Teacher\Resources;

use App\Enums\QuizAssignableType;
use App\Filament\Shared\Resources\BaseQuizResource;
use App\Filament\Teacher\Resources\QuizResource\Pages\CreateQuiz;
use App\Filament\Teacher\Resources\QuizResource\Pages\EditQuiz;
use App\Filament\Teacher\Resources\QuizResource\Pages\ListQuizzes;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

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
     * Uses auth()->id() (User ID) because QuranCircle/QuranIndividualCircle
     * store quran_teacher_id as users.id, NOT quran_teacher_profiles.id.
     */
    protected static function getAssignableOptions(?string $type): array
    {
        $teacherId = auth()->id();

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

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordActions([
                ActionGroup::make([
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

    public static function getPages(): array
    {
        return [
            'index' => ListQuizzes::route('/'),
            'create' => CreateQuiz::route('/create'),
            'edit' => EditQuiz::route('/{record}/edit'),
        ];
    }
}
