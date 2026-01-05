<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomeworkSubmissionsResource\Pages;
use App\Models\AcademicHomeworkSubmission;
use App\Models\InteractiveCourseHomeworkSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Homework Submissions Resource
 *
 * This is a list-only resource for viewing and grading homework submissions.
 * Submissions are created automatically when homework is assigned to students.
 * Grading is done via modal actions on the list page.
 *
 * The list page handles two models via tabs:
 * - AcademicHomeworkSubmission (for academic sessions)
 * - InteractiveCourseHomeworkSubmission (for interactive courses)
 */
class HomeworkSubmissionsResource extends BaseResource
{
    protected static ?string $model = AcademicHomeworkSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'تسليمات الواجبات';

    protected static ?string $modelLabel = 'تسليم واجب';

    protected static ?string $pluralModelLabel = 'تسليمات الواجبات';

    protected static ?string $navigationGroup = 'التقارير والحضور';

    protected static ?int $navigationSort = 10;

    /**
     * Get the navigation badge showing pending review count (only 'submitted' status)
     */
    public static function getNavigationBadge(): ?string
    {
        $academicCount = AcademicHomeworkSubmission::where('submission_status', 'submitted')->count();
        $interactiveCount = InteractiveCourseHomeworkSubmission::where('submission_status', 'submitted')->count();
        $total = $academicCount + $interactiveCount;

        return $total > 0 ? (string) $total : null;
    }

    /**
     * Get the navigation badge color
     */
    public static function getNavigationBadgeColor(): string|array|null
    {
        $academicCount = AcademicHomeworkSubmission::where('submission_status', 'submitted')->count();
        $interactiveCount = InteractiveCourseHomeworkSubmission::where('submission_status', 'submitted')->count();

        return ($academicCount + $interactiveCount) > 0 ? 'warning' : null;
    }

    /**
     * Get the navigation badge tooltip
     */
    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'بانتظار التصحيح';
    }

    /**
     * Eager load relationships to prevent N+1 queries
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['student', 'homework', 'session', 'grader']);
    }

    /**
     * Disable creation since submissions are auto-generated
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomeworkSubmissions::route('/'),
        ];
    }
}
