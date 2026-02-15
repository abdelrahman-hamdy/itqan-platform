<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Enums\UserType;
use App\Filament\AcademicTeacher\Resources\InteractiveSessionReportResource\Pages;
use App\Filament\Shared\Resources\BaseInteractiveSessionReportResource;
use App\Models\InteractiveSessionReport;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Interactive Session Report Resource for AcademicTeacher Panel
 *
 * Teachers can view and manage reports for their own course sessions.
 * Limited permissions compared to SuperAdmin.
 * Extends BaseInteractiveSessionReportResource for shared form/table definitions.
 */
class InteractiveSessionReportResource extends BaseInteractiveSessionReportResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationGroup = 'التقارير والتقييمات';

    protected static ?int $navigationSort = 2;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * Filter reports to current teacher's course sessions only.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        $teacherProfile = Auth::user()?->academicTeacherProfile;

        if ($teacherProfile) {
            return $query->whereHas('session.course', function ($q) use ($teacherProfile) {
                $q->where('assigned_teacher_id', $teacherProfile->id);
            });
        }

        // No teacher profile - show nothing
        return $query->whereRaw('1 = 0');
    }

    /**
     * Session info section - scoped to teacher's course sessions.
     */
    protected static function getSessionInfoFormSection(): Section
    {
        return Section::make('معلومات الجلسة')
            ->schema([
                Forms\Components\Select::make('session_id')
                    ->relationship('session', 'id', fn (Builder $query) => $query->whereHas('course', fn ($q) => $q->where('assigned_teacher_id', Auth::user()->academicTeacherProfile?->id)
                    )
                    )
                    ->label('الجلسة')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->course?->name.' - '.$record->scheduled_date?->format('Y-m-d')
                    ),

                Forms\Components\Select::make('student_id')
                    ->label('الطالب')
                    ->options(fn () => \App\Models\User::query()
                        ->where('user_type', UserType::STUDENT->value)
                        ->whereNotNull('name')
                        ->pluck('name', 'id')
                    )
                    ->required()
                    ->searchable()
                    ->disabled(fn (?InteractiveSessionReport $record) => $record !== null),
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
                ->label('تقييم'),
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
    // Table Filters Override (Teacher-specific)
    // ========================================

    /**
     * Session filter scoped to teacher's sessions.
     */
    protected static function getTableFilters(): array
    {
        return [
            ...parent::getTableFilters(),

            Tables\Filters\SelectFilter::make('session_id')
                ->label('الجلسة')
                ->relationship('session', 'id')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->course?->name.' - '.$record->scheduled_date?->format('Y-m-d')
                )
                ->searchable()
                ->preload(),
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
        $teacherProfile = Auth::user()?->academicTeacherProfile;

        if (! $teacherProfile) {
            return false;
        }

        // Check if report belongs to teacher's course session
        return $record->session?->course?->assigned_teacher_id === $teacherProfile->id;
    }

    public static function canView(Model $record): bool
    {
        $teacherProfile = Auth::user()?->academicTeacherProfile;

        if (! $teacherProfile) {
            return false;
        }

        return $record->session?->course?->assigned_teacher_id === $teacherProfile->id;
    }

    public static function canDelete(Model $record): bool
    {
        $teacherProfile = Auth::user()?->academicTeacherProfile;

        if (! $teacherProfile) {
            return false;
        }

        return $record->session?->course?->assigned_teacher_id === $teacherProfile->id;
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInteractiveSessionReports::route('/'),
            'create' => Pages\CreateInteractiveSessionReport::route('/create'),
            'view' => Pages\ViewInteractiveSessionReport::route('/{record}'),
            'edit' => Pages\EditInteractiveSessionReport::route('/{record}/edit'),
        ];
    }
}
