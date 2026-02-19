<?php

namespace App\Filament\Resources;

use App\Enums\UserType;
use App\Filament\Resources\StudentProfileResource\Pages;
use App\Filament\Resources\StudentProfileResource\Pages\CreateStudentProfile;
use App\Filament\Resources\StudentProfileResource\Pages\EditStudentProfile;
use App\Filament\Resources\StudentProfileResource\Pages\ListStudentProfiles;
use App\Filament\Resources\StudentProfileResource\Pages\ViewStudentProfile;
use App\Filament\Shared\Resources\Profiles\BaseStudentProfileResource;
use App\Helpers\CountryList;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubscription;
use App\Models\InteractiveCourseEnrollment;
use App\Models\QuranSubscription;
use App\Services\AcademyContextService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
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
            ActionGroup::make([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                Action::make('activate')
                    ->label('تفعيل')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->user?->update(['active_status' => true]))
                    ->visible(fn ($record) => $record->user && ! $record->user->active_status),
                Action::make('deactivate')
                    ->label('إيقاف')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->user?->update(['active_status' => false]))
                    ->visible(fn ($record) => $record->user && $record->user->active_status),
                DeleteAction::make()->label('حذف'),
                RestoreAction::make()->label(__('filament.actions.restore')),
                ForceDeleteAction::make()->label(__('filament.actions.force_delete')),
            ]),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                BulkAction::make('activate')
                    ->label('تفعيل المحددين')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each(fn ($record) => $record->user?->update(['active_status' => true]))),
                BulkAction::make('deactivate')
                    ->label('إيقاف المحددين')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each(fn ($record) => $record->user?->update(['active_status' => false]))),
                DeleteBulkAction::make(),
                RestoreBulkAction::make()
                    ->label(__('filament.actions.restore_selected')),
                ForceDeleteBulkAction::make()
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
        $columns[] = ImageColumn::make('avatar')
            ->label('الصورة')
            ->circular()
            ->defaultImageUrl(fn ($record) => config('services.ui_avatars.base_url', 'https://ui-avatars.com/api/').'?name='.urlencode($record->full_name ?? 'N/A').'&background=4169E1&color=fff');

        // Add shared columns from base
        $columns[] = TextColumn::make('student_code')
            ->label('رمز الطالب')
            ->searchable()
            ->sortable()
            ->copyable();

        $columns[] = TextColumn::make('full_name')
            ->label('الاسم')
            ->searchable(['first_name', 'last_name'])
            ->sortable()
            ->weight(FontWeight::Bold);

        $columns[] = TextColumn::make('email')
            ->label('البريد الإلكتروني')
            ->searchable()
            ->sortable()
            ->copyable();

        $columns[] = TextColumn::make('gradeLevel.name')
            ->label('المرحلة الدراسية')
            ->sortable();

        $columns[] = TextColumn::make('parent.full_name')
            ->label('ولي الأمر')
            ->searchable(['first_name', 'last_name'])
            ->sortable()
            ->default('—')
            ->description(fn ($record) => $record->parent?->parent_code);

        $columns[] = TextColumn::make('nationality')
            ->label('الجنسية')
            ->formatStateUsing(function (?string $state): string {
                if (! $state) {
                    return '';
                }

                return CountryList::getLabel($state);
            })
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);

        $columns[] = IconColumn::make('user.active_status')
            ->label('الحالة')
            ->boolean()
            ->trueIcon('heroicon-o-check-circle')
            ->falseIcon('heroicon-o-x-circle')
            ->trueColor('success')
            ->falseColor('danger');

        $columns[] = TextColumn::make('enrollment_date')
            ->label('تاريخ التسجيل')
            ->date()
            ->sortable()
            ->toggleable();

        $columns[] = TextColumn::make('created_at')
            ->label(__('filament.created_at'))
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);

        $columns[] = TextColumn::make('updated_at')
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
            $hasQuranStudent = QuranSubscription::query()
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
            $hasAcademicStudent = AcademicSubscription::query()
                ->where('student_id', $studentUserId)
                ->whereHas('teacher', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->exists();

            if ($hasAcademicStudent) {
                return true;
            }

            // Check interactive courses
            $hasInteractiveStudent = InteractiveCourseEnrollment::query()
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
        // Use direct academy_id column first (fast path, available since migration)
        $directId = $record->getAttributes()['academy_id'] ?? null;
        if ($directId !== null) {
            return (int) $directId;
        }

        // Fallback: resolve via grade level for legacy records without academy_id column value
        if (! $record->grade_level_id) {
            return null;
        }

        // Bypass academy scope — relationship scoped by direct FK, not academy context
        $gradeLevel = AcademicGradeLevel::withoutGlobalScope('academy')
            ->find($record->grade_level_id);

        return $gradeLevel?->academy_id;
    }

    // ========================================
    // Resource Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => ListStudentProfiles::route('/'),
            'create' => CreateStudentProfile::route('/create'),
            'view' => ViewStudentProfile::route('/{record}'),
            'edit' => EditStudentProfile::route('/{record}/edit'),
        ];
    }
}
