<?php

namespace App\Filament\Supervisor\Resources;

use Filament\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Supervisor\Resources\ManagedTeacherReviewsResource\Pages\ListManagedTeacherReviews;
use App\Filament\Supervisor\Resources\ManagedTeacherReviewsResource\Pages\ViewManagedTeacherReview;
use App\Enums\UserType;
use App\Filament\Shared\Resources\BaseTeacherReviewResource;
use App\Filament\Supervisor\Resources\ManagedTeacherReviewsResource\Pages;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Managed Teacher Reviews Resource for Supervisor Panel
 *
 * Read-only view of reviews for assigned teachers.
 * Extends BaseTeacherReviewResource for shared form/table definitions.
 * Uses BaseSupervisorResource static methods for teacher filtering.
 */
class ManagedTeacherReviewsResource extends BaseTeacherReviewResource
{
    protected static string | \UnitEnum | null $navigationGroup = 'إدارة المعلمين';

    protected static ?int $navigationSort = 4;

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
     * Scope query to only show reviews of assigned teachers.
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
                        $sq->where('reviewable_type', QuranTeacherProfile::class)
                            ->whereIn('reviewable_id', $profileIds['quran']);
                    });
                }
                if ($hasAcademic) {
                    $q->orWhere(function ($sq) use ($profileIds) {
                        $sq->where('reviewable_type', AcademicTeacherProfile::class)
                            ->whereIn('reviewable_id', $profileIds['academic']);
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
     * View-only actions for supervisors.
     */
    protected static function getTableActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض'),
        ];
    }

    /**
     * No bulk actions for supervisors.
     */
    protected static function getTableBulkActions(): array
    {
        return [];
    }

    // Note: canCreate(), canEdit(), canDelete() use base class defaults (return false)

    // ========================================
    // Supervisor-Specific Customizations
    // ========================================

    /**
     * Override table to add teacher filter specific to Supervisor.
     */
    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->filters([
                ...static::getTableFilters(),
                static::getTeacherFilter(),
            ])
            ->deferFilters(false);
    }

    /**
     * Additional teacher filter for Supervisor panel.
     */
    protected static function getTeacherFilter(): SelectFilter
    {
        return SelectFilter::make('teacher_id')
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
                        if ($user->user_type === UserType::QURAN_TEACHER->value) {
                            $profile = $user->quranTeacherProfile;
                            if ($profile) {
                                $query->where('reviewable_type', QuranTeacherProfile::class)
                                    ->where('reviewable_id', $profile->id);
                            }
                        } elseif ($user->user_type === UserType::ACADEMIC_TEACHER->value) {
                            $profile = $user->academicTeacherProfile;
                            if ($profile) {
                                $query->where('reviewable_type', AcademicTeacherProfile::class)
                                    ->where('reviewable_id', $profile->id);
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
            'index' => ListManagedTeacherReviews::route('/'),
            'view' => ViewManagedTeacherReview::route('/{record}'),
        ];
    }
}
