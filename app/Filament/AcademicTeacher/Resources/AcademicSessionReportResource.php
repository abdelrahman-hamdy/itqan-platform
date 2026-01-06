<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource\Pages;
use App\Filament\Shared\Resources\BaseAcademicSessionReportResource;
use App\Models\AcademicSessionReport;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Tables;
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

    protected static ?string $navigationGroup = 'التقارير والتقييمات';

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
                Forms\Components\Select::make('session_id')
                    ->relationship('session', 'title', fn (Builder $query) => $query->whereHas('academicTeacher', fn ($q) => $q->where('user_id', Auth::id()))
                    )
                    ->label('الجلسة')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('student_id')
                    ->label('الطالب')
                    ->options(fn () => \App\Models\User::query()
                        ->where('user_type', 'student')
                        ->whereNotNull('name')
                        ->pluck('name', 'id')
                    )
                    ->required()
                    ->searchable()
                    ->disabled(fn (?AcademicSessionReport $record) => $record !== null),
            ])->columns(2);
    }

    /**
     * Limited table actions for teachers.
     */
    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make()
                ->label('عرض'),
            Tables\Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }

    /**
     * Bulk actions for teachers.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ];
    }

    // ========================================
    // Authorization Overrides
    // ========================================

    /**
     * Teachers can create reports for their sessions.
     */
    public static function canCreate(): bool
    {
        return true;
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
            'index' => Pages\ListAcademicSessionReports::route('/'),
            'create' => Pages\CreateAcademicSessionReport::route('/create'),
            'view' => Pages\ViewAcademicSessionReport::route('/{record}'),
            'edit' => Pages\EditAcademicSessionReport::route('/{record}/edit'),
        ];
    }
}
