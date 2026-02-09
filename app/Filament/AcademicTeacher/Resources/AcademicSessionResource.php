<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Enums\SessionStatus;
use App\Enums\UserType;
use App\Filament\AcademicTeacher\Resources\AcademicSessionResource\Pages;
use App\Filament\Shared\Actions\SessionStatusActions;
use App\Filament\Shared\Resources\BaseAcademicSessionResource;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Academic Session Resource for AcademicTeacher Panel
 *
 * Teachers can view and manage their own sessions only.
 * Limited permissions compared to SuperAdmin.
 * Extends BaseAcademicSessionResource for shared form/table definitions.
 */
class AcademicSessionResource extends BaseAcademicSessionResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationLabel = 'الجلسات الأكاديمية';

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 1;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * Filter sessions to current teacher only.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        $teacherProfile = static::getCurrentAcademicTeacherProfile();

        if ($teacherProfile) {
            return $query->where('academic_teacher_id', $teacherProfile->id);
        }

        return $query->whereRaw('1 = 0'); // Return no results
    }

    /**
     * Session info section - teacher is auto-assigned.
     */
    protected static function getSessionInfoFormSection(): Section
    {
        return Section::make('معلومات الجلسة الأساسية')
            ->schema([
                // Hidden fields for auto-assignment
                Forms\Components\Hidden::make('academy_id')
                    ->default(fn () => static::getCurrentTeacherAcademy()?->id),

                Forms\Components\Hidden::make('academic_teacher_id')
                    ->default(fn () => static::getCurrentAcademicTeacherProfile()?->id),

                Forms\Components\Hidden::make('academic_subscription_id'),

                Forms\Components\Select::make('student_id')
                    ->label('الطالب')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => static::searchStudentsInAcademy($search))
                    ->getOptionLabelUsing(fn ($value) => static::getStudentLabelById($value))
                    ->disabled(fn ($record) => $record !== null)
                    ->dehydrated(),

                Forms\Components\TextInput::make('session_code')
                    ->label('رمز الجلسة')
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\Hidden::make('session_type')
                    ->default('individual'),
            ])->columns(2);
    }

    /**
     * Limited table actions for teachers.
     */
    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),

                static::makeStartSessionAction(),
                static::makeCompleteSessionAction(),
                static::makeJoinMeetingAction(),
                SessionStatusActions::cancelSession(role: 'teacher'),
            ]),
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
     * Teacher-specific filters (simplified - no teacher filter needed).
     */
    protected static function getTableFilters(): array
    {
        return [
            ...parent::getTableFilters(),

            Tables\Filters\SelectFilter::make('student_id')
                ->label('الطالب')
                ->options(fn () => \App\Models\User::query()
                    ->where('user_type', UserType::STUDENT->value)
                    ->whereNotNull('name')
                    ->pluck('name', 'id')
                )
                ->searchable(),
        ];
    }

    // ========================================
    // Helper Methods for Current Teacher
    // ========================================

    /**
     * Get the current logged-in teacher's profile.
     */
    protected static function getCurrentAcademicTeacherProfile(): ?\App\Models\AcademicTeacherProfile
    {
        return Auth::user()?->academicTeacherProfile;
    }

    /**
     * Get the current teacher's academy.
     */
    protected static function getCurrentTeacherAcademy(): ?\App\Models\Academy
    {
        return Auth::user()?->academy;
    }

    /**
     * Search for students within the current academy.
     * Used by Select component's getSearchResultsUsing.
     */
    protected static function searchStudentsInAcademy(string $search): array
    {
        $academyId = static::getCurrentTeacherAcademy()?->id;

        return \App\Models\User::query()
            ->where('user_type', UserType::STUDENT->value)
            ->when($academyId, fn ($q) => $q->where('academy_id', $academyId))
            ->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(50)
            ->get()
            ->mapWithKeys(fn ($user) => [
                $user->id => static::formatStudentName($user),
            ])
            ->toArray();
    }

    /**
     * Get student display name by ID (with academy verification).
     * Used by Select component's getOptionLabelUsing.
     */
    protected static function getStudentLabelById($value): ?string
    {
        $academyId = static::getCurrentTeacherAcademy()?->id;
        $user = \App\Models\User::where('id', $value)
            ->when($academyId, fn ($q) => $q->where('academy_id', $academyId))
            ->first();

        return $user ? static::formatStudentName($user) : null;
    }

    /**
     * Format student name for display.
     */
    protected static function formatStudentName(\App\Models\User $user): string
    {
        $fullName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        return $fullName ?: $user->name ?? 'طالب #'.$user->id;
    }

    // ========================================
    // Authorization Overrides
    // ========================================

    /**
     * Teachers can create sessions.
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

        return $record->academic_teacher_id === $user->academicTeacherProfile->id;
    }

    public static function canView(Model $record): bool
    {
        $user = Auth::user();

        if (! $user->academicTeacherProfile) {
            return false;
        }

        return $record->academic_teacher_id === $user->academicTeacherProfile->id;
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        if (! $user->academicTeacherProfile) {
            return false;
        }

        // Only allow deletion of scheduled sessions
        return $record->academic_teacher_id === $user->academicTeacherProfile->id &&
               $record->status === SessionStatus::SCHEDULED;
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicSessions::route('/'),
            'create' => Pages\CreateAcademicSession::route('/create'),
            'view' => Pages\ViewAcademicSession::route('/{record}'),
            'edit' => Pages\EditAcademicSession::route('/{record}/edit'),
        ];
    }
}
