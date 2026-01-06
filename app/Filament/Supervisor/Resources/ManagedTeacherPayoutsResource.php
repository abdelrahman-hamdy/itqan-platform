<?php

namespace App\Filament\Supervisor\Resources;

use App\Filament\Shared\Resources\BaseTeacherPayoutResource;
use App\Filament\Supervisor\Resources\ManagedTeacherPayoutsResource\Pages;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Managed Teacher Payouts Resource for Supervisor Panel
 *
 * View and approve payouts for assigned teachers.
 * Extends BaseTeacherPayoutResource for shared form/table definitions.
 * Uses BaseSupervisorResource static methods for teacher filtering.
 */
class ManagedTeacherPayoutsResource extends BaseTeacherPayoutResource
{
    protected static ?string $navigationGroup = 'إدارة المعلمين';

    protected static ?int $navigationSort = 3;

    // ========================================
    // Navigation Visibility
    // ========================================

    /**
     * Only show navigation if supervisor can manage teachers and has assigned teachers.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return BaseSupervisorResource::canManageTeachers()
            && BaseSupervisorResource::hasAssignedTeachers();
    }

    // ========================================
    // Abstract Method Implementations
    // ========================================

    /**
     * Scope query to only show payouts of assigned teachers.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        $profileIds = static::getAssignedTeacherProfileIds();

        $hasQuran = ! empty($profileIds['quran']);
        $hasAcademic = ! empty($profileIds['academic']);

        if ($hasQuran || $hasAcademic) {
            $query->where(function ($q) use ($profileIds, $hasQuran, $hasAcademic) {
                if ($hasQuran) {
                    $q->orWhere(function ($sq) use ($profileIds) {
                        $sq->where('teacher_type', QuranTeacherProfile::class)
                            ->whereIn('teacher_id', $profileIds['quran']);
                    });
                }
                if ($hasAcademic) {
                    $q->orWhere(function ($sq) use ($profileIds) {
                        $sq->where('teacher_type', AcademicTeacherProfile::class)
                            ->whereIn('teacher_id', $profileIds['academic']);
                    });
                }
            });
        } else {
            // No teachers assigned - return empty result
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Supervisor actions: view + approve/reject actions.
     */
    protected static function getTableActions(): array
    {
        return [
            static::getApproveAction(),
            static::getRejectAction(),
            Tables\Actions\ViewAction::make()
                ->label('عرض'),
        ];
    }

    /**
     * Supervisor bulk actions: approve only.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                static::getApproveBulkAction(),
            ]),
        ];
    }

    // Note: canCreate(), canEdit(), canDelete() use base class defaults (return false)

    // ========================================
    // Supervisor-Specific Customizations
    // ========================================

    /**
     * Override table to add teacher filter.
     */
    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->filters([
                ...static::getTableFilters(),
                static::getTeacherFilter(),
            ]);
    }

    /**
     * Additional teacher filter for Supervisor panel.
     */
    protected static function getTeacherFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('teacher_id')
            ->label('المعلم')
            ->options(function () {
                $teacherIds = BaseSupervisorResource::getAllAssignedTeacherIds();

                return User::whereIn('id', $teacherIds)
                    ->get()
                    ->mapWithKeys(fn ($user) => [$user->id => $user->full_name ?? $user->name ?? $user->email]);
            })
            ->query(function (Builder $query, array $data) {
                if (! empty($data['value'])) {
                    $userId = $data['value'];
                    $user = User::find($userId);

                    if ($user) {
                        if ($user->user_type === 'quran_teacher') {
                            $profile = $user->quranTeacherProfile;
                            if ($profile) {
                                $query->where('teacher_type', QuranTeacherProfile::class)
                                    ->where('teacher_id', $profile->id);
                            }
                        } elseif ($user->user_type === 'academic_teacher') {
                            $profile = $user->academicTeacherProfile;
                            if ($profile) {
                                $query->where('teacher_type', AcademicTeacherProfile::class)
                                    ->where('teacher_id', $profile->id);
                            }
                        }
                    }
                }
            })
            ->searchable()
            ->preload();
    }

    // ========================================
    // Helper Methods
    // ========================================

    /**
     * Get assigned teacher profile IDs by type.
     */
    protected static function getAssignedTeacherProfileIds(): array
    {
        $quranTeacherUserIds = BaseSupervisorResource::getAssignedQuranTeacherIds();
        $academicTeacherUserIds = BaseSupervisorResource::getAssignedAcademicTeacherIds();

        $quranProfileIds = [];
        $academicProfileIds = [];

        if (! empty($quranTeacherUserIds)) {
            $quranProfileIds = QuranTeacherProfile::whereIn('user_id', $quranTeacherUserIds)
                ->pluck('id')
                ->toArray();
        }

        if (! empty($academicTeacherUserIds)) {
            $academicProfileIds = AcademicTeacherProfile::whereIn('user_id', $academicTeacherUserIds)
                ->pluck('id')
                ->toArray();
        }

        return [
            'quran' => $quranProfileIds,
            'academic' => $academicProfileIds,
        ];
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManagedTeacherPayouts::route('/'),
            'view' => Pages\ViewManagedTeacherPayout::route('/{record}'),
        ];
    }
}
