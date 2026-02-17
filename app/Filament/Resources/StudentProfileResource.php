<?php

namespace App\Filament\Resources;

use App\Enums\UserType;
use App\Filament\Resources\StudentProfileResource\Pages;
use App\Filament\Shared\Resources\Profiles\BaseStudentProfileResource;
use App\Helpers\CountryList;
use App\Models\AcademicGradeLevel;
use App\Services\AcademyContextService;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentProfileResource extends BaseStudentProfileResource
{
    // Override academy relationship path for nested relationship
    protected static function getAcademyRelationshipPath(): string
    {
        return 'gradeLevel.academy'; // StudentProfile -> GradeLevel -> Academy
    }

    // ========================================
    // Panel-Specific Implementations
    // ========================================

    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        // Admin sees all academies with soft delete scope removed
        return $query->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
            Tables\Actions\RestoreAction::make()
                ->label(__('filament.actions.restore')),
            Tables\Actions\ForceDeleteAction::make()
                ->label(__('filament.actions.force_delete')),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make()
                    ->label(__('filament.actions.restore_selected')),
                Tables\Actions\ForceDeleteBulkAction::make()
                    ->label(__('filament.actions.force_delete_selected')),
            ]),
        ];
    }

    protected static function getGradeLevelOptions(): array
    {
        $query = AcademicGradeLevel::query();

        // Filter by academy context if selected
        if (AcademyContextService::hasAcademySelected()) {
            $query->where('academy_id', AcademyContextService::getCurrentAcademyId());
        }

        return $query->where('is_active', true)
            ->pluck('name', 'id')
            ->toArray();
    }

    // ========================================
    // Admin-Specific Table Customization
    // ========================================

    protected static function getTableColumns(): array
    {
        $columns = [];

        // Add academy column first (when viewing all academies)
        $columns[] = static::getAcademyColumn();

        // Add avatar column (Admin-specific)
        $columns[] = Tables\Columns\ImageColumn::make('avatar')
            ->label('الصورة')
            ->circular()
            ->defaultImageUrl(fn ($record) => config('services.ui_avatars.base_url', 'https://ui-avatars.com/api/').'?name='.urlencode($record->full_name ?? 'N/A').'&background=4169E1&color=fff');

        // Add shared columns from base
        $columns[] = Tables\Columns\TextColumn::make('student_code')
            ->label('رمز الطالب')
            ->searchable()
            ->sortable()
            ->copyable();

        $columns[] = Tables\Columns\TextColumn::make('full_name')
            ->label('الاسم')
            ->searchable(['first_name', 'last_name'])
            ->sortable()
            ->weight(FontWeight::Bold);

        $columns[] = Tables\Columns\TextColumn::make('email')
            ->label('البريد الإلكتروني')
            ->searchable()
            ->sortable()
            ->copyable();

        $columns[] = Tables\Columns\TextColumn::make('gradeLevel.name')
            ->label('المرحلة الدراسية')
            ->sortable();

        $columns[] = Tables\Columns\TextColumn::make('parent.full_name')
            ->label('ولي الأمر')
            ->searchable(['first_name', 'last_name'])
            ->sortable()
            ->default('—')
            ->description(fn ($record) => $record->parent?->parent_code);

        $columns[] = Tables\Columns\TextColumn::make('nationality')
            ->label('الجنسية')
            ->formatStateUsing(function (?string $state): string {
                if (! $state) {
                    return '';
                }

                return CountryList::getLabel($state);
            })
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);

        $columns[] = Tables\Columns\TextColumn::make('enrollment_date')
            ->label('تاريخ التسجيل')
            ->date()
            ->sortable()
            ->toggleable();

        $columns[] = Tables\Columns\TextColumn::make('created_at')
            ->label(__('filament.created_at'))
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);

        $columns[] = Tables\Columns\TextColumn::make('updated_at')
            ->label(__('filament.updated_at'))
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);

        return $columns;
    }

    // ========================================
    // Admin-Specific Authorization
    // ========================================

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // SuperAdmin and Admin can always view
        if ($user->isSuperAdmin() || $user->hasRole(UserType::ADMIN->value)) {
            return true;
        }

        // Teachers can view (will be filtered to their students)
        if ($user->isQuranTeacher() || $user->isAcademicTeacher()) {
            return true;
        }

        // Supervisors can view
        if ($user->hasRole(UserType::SUPERVISOR->value)) {
            return true;
        }

        return false;
    }

    public static function canView($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // SuperAdmin has full access
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can view students in their academy context
        if ($user->hasRole(UserType::ADMIN->value)) {
            $currentAcademyId = AcademyContextService::getCurrentAcademyId();
            if (! $currentAcademyId) {
                return false;
            }

            return static::getRecordAcademyId($record) === $currentAcademyId;
        }

        // Supervisors can view students in academies they supervise
        if ($user->hasRole(UserType::SUPERVISOR->value)) {
            $currentAcademyId = AcademyContextService::getCurrentAcademyId();

            return static::getRecordAcademyId($record) === $currentAcademyId;
        }

        // Teachers can view their own students
        if ($user->isQuranTeacher() || $user->isAcademicTeacher()) {
            return static::isTeacherOfStudent($user, $record);
        }

        return false;
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // SuperAdmin has full access
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can edit students in their academy
        if ($user->hasRole(UserType::ADMIN->value)) {
            $currentAcademyId = AcademyContextService::getCurrentAcademyId();
            if (! $currentAcademyId) {
                return false;
            }

            return static::getRecordAcademyId($record) === $currentAcademyId;
        }

        // Teachers and others cannot edit
        return false;
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Only SuperAdmin can delete student profiles
        return $user->isSuperAdmin();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // SuperAdmin and Admin can create
        if ($user->isSuperAdmin() || $user->hasRole(UserType::ADMIN->value)) {
            return true;
        }

        return false;
    }

    // ========================================
    // Helper Methods
    // ========================================

    protected static function isTeacherOfStudent($user, $studentProfile): bool
    {
        $studentUserId = $studentProfile->user_id;

        // Check Quran subscriptions
        if ($user->isQuranTeacher()) {
            $hasQuranStudent = \App\Models\QuranSubscription::query()
                ->where('student_id', $studentUserId)
                ->whereHas('circle', function ($q) use ($user) {
                    $q->where('quran_teacher_id', $user->id);
                })
                ->orWhereHas('individualCircle', function ($q) use ($user) {
                    $q->where('quran_teacher_id', $user->id);
                })
                ->exists();

            if ($hasQuranStudent) {
                return true;
            }
        }

        // Check Academic subscriptions
        if ($user->isAcademicTeacher()) {
            $hasAcademicStudent = \App\Models\AcademicSubscription::query()
                ->where('student_id', $studentUserId)
                ->whereHas('teacher', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->exists();

            if ($hasAcademicStudent) {
                return true;
            }

            // Check interactive courses
            $hasInteractiveStudent = \App\Models\InteractiveCourseEnrollment::query()
                ->where('student_id', $studentUserId)
                ->whereHas('course', function ($q) use ($user) {
                    $q->where('assigned_teacher_id', $user->id);
                })
                ->exists();

            if ($hasInteractiveStudent) {
                return true;
            }
        }

        return false;
    }

    protected static function getRecordAcademyId($record): ?int
    {
        if (! $record->grade_level_id) {
            return null;
        }

        // Query grade level directly, bypassing the academy scope
        $gradeLevel = \App\Models\AcademicGradeLevel::withoutGlobalScope('academy')
            ->find($record->grade_level_id);

        return $gradeLevel?->academy_id;
    }

    // ========================================
    // Resource Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentProfiles::route('/'),
            'create' => Pages\CreateStudentProfile::route('/create'),
            'view' => Pages\ViewStudentProfile::route('/{record}'),
            'edit' => Pages\EditStudentProfile::route('/{record}/edit'),
        ];
    }
}
