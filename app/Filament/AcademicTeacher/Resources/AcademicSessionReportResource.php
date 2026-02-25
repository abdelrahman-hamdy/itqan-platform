<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Enums\UserType;
use App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource\Pages;
use App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource\Pages\EditAcademicSessionReport;
use App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource\Pages\ListAcademicSessionReports;
use App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource\Pages\ViewAcademicSessionReport;
use App\Filament\Shared\Resources\BaseAcademicSessionReportResource;
use App\Models\AcademicSessionReport;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Academic Session Report Resource for AcademicTeacher Panel
 *
 * Teachers can view and manage reports for their own sessions only.
 * Limited permissions compared to SuperAdmin.
 * Extends BaseAcademicSessionReportResource for shared form/table definitions.
 */
class AcademicSessionReportResource extends BaseAcademicSessionReportResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير والتقييمات';

    protected static ?int $navigationSort = 1;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * Filter reports to current teacher's sessions only.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query->whereHas('session.academicTeacher', function ($q) {
            $q->where('user_id', Auth::id());
        });
    }

    /**
     * Session info section - scoped to teacher's sessions.
     */
    protected static function getSessionInfoFormSection(): Section
    {
        return Section::make('معلومات الجلسة')
            ->schema([
                Select::make('session_id')
                    ->relationship('session', 'title', fn (Builder $query) => $query->whereHas('academicTeacher', fn ($q) => $q->where('user_id', Auth::id()))
                    )
                    ->label('الجلسة')
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('student_id')
                    ->label('الطالب')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search) {
                        $academyId = Auth::user()?->academy_id;

                        return User::query()
                            ->where('user_type', UserType::STUDENT->value)
                            ->whereNotNull('name')
                            ->when($academyId, fn ($q) => $q->where('academy_id', $academyId))
                            ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%"))
                            ->limit(50)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->getOptionLabelUsing(fn ($value) => User::where('id', $value)->value('name'))
                    ->required()
                    ->disabled(fn (?AcademicSessionReport $record) => $record !== null),
            ])->columns(2);
    }

    /**
     * Limited table actions for teachers.
     */
    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
            ]),
        ];
    }

    /**
     * Bulk actions for teachers.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ];
    }

    // ========================================
    // Authorization Overrides
    // ========================================

    /**
     * Reports are auto-generated, not created manually.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        if (! $user->academicTeacherProfile) {
            return false;
        }

        // Check if report belongs to teacher's session
        return $record->session?->academicTeacher?->user_id === $user->id;
    }

    public static function canView(Model $record): bool
    {
        $user = Auth::user();

        if (! $user->academicTeacherProfile) {
            return false;
        }

        return $record->session?->academicTeacher?->user_id === $user->id;
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        if (! $user->academicTeacherProfile) {
            return false;
        }

        return $record->session?->academicTeacher?->user_id === $user->id;
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => ListAcademicSessionReports::route('/'),
            'view' => ViewAcademicSessionReport::route('/{record}'),
            'edit' => EditAcademicSessionReport::route('/{record}/edit'),
        ];
    }
}
