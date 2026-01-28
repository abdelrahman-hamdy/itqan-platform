<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Filament\AcademicTeacher\Resources\SessionRecordingResource\Pages;
use App\Filament\Shared\Resources\BaseSessionRecordingResource;
use App\Models\InteractiveCourseSession;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Session Recording Resource for AcademicTeacher Panel
 *
 * Teachers can view and download recordings from their own courses only.
 * No delete permission (per RecordingPolicy).
 * Extends BaseSessionRecordingResource for shared form/table definitions.
 */
class SessionRecordingResource extends BaseSessionRecordingResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 5;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * Teachers only see recordings from their own courses.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        $user = Auth::user();
        $teacherProfile = $user?->academicTeacherProfile;
        $academyId = $user?->academy_id;

        if ($teacherProfile) {
            // Filter recordings where teacher is assigned to the course
            $query->whereHasMorph(
                'recordable',
                [InteractiveCourseSession::class],
                function ($q) use ($teacherProfile, $academyId) {
                    $q->whereHas('course', function ($courseQuery) use ($teacherProfile, $academyId) {
                        $courseQuery->where('assigned_teacher_id', $teacherProfile->id);

                        // Also filter by academy
                        if ($academyId) {
                            $courseQuery->where('academy_id', $academyId);
                        }
                    });
                }
            );
        }

        return $query;
    }

    /**
     * Limited table actions for teachers (no delete).
     */
    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make()->label('عرض'),
                static::makeDownloadAction(),
                static::makeStreamAction(),
                // Teachers cannot delete recordings per RecordingPolicy
            ]),
        ];
    }

    /**
     * No bulk actions for teachers.
     */
    protected static function getTableBulkActions(): array
    {
        return []; // No bulk actions for teachers
    }

    // ========================================
    // Additional Filters for Teachers
    // ========================================

    protected static function getTableFilters(): array
    {
        return [
            ...parent::getTableFilters(),

            // Filter by Course (scoped to teacher's courses)
            Tables\Filters\SelectFilter::make('course')
                ->label('الدورة')
                ->options(function () {
                    $teacherProfile = Auth::user()?->academicTeacherProfile;

                    return \App\Models\InteractiveCourse::query()
                        ->when($teacherProfile, fn ($q) => $q->where('assigned_teacher_id', $teacherProfile->id))
                        ->orderBy('title')
                        ->pluck('title', 'id')
                        ->toArray();
                })
                ->query(function (Builder $query, array $data) {
                    if (empty($data['value'])) {
                        return $query;
                    }

                    return $query->whereHasMorph(
                        'recordable',
                        [InteractiveCourseSession::class],
                        function ($q) use ($data) {
                            $q->where('course_id', $data['value']);
                        }
                    );
                })
                ->searchable()
                ->preload(),
        ];
    }

    // ========================================
    // Authorization Overrides
    // ========================================

    /**
     * Teachers can only view recordings from their own courses.
     */
    public static function canView(Model $record): bool
    {
        $user = Auth::user();
        $teacherProfile = $user?->academicTeacherProfile;

        if (! $teacherProfile) {
            return false;
        }

        $session = $record->recordable;
        if ($session instanceof InteractiveCourseSession) {
            return $session->course?->assigned_teacher_id === $teacherProfile->id;
        }

        return false;
    }

    /**
     * Teachers cannot delete recordings (per RecordingPolicy).
     */
    public static function canDelete(Model $record): bool
    {
        // RecordingPolicy::delete only allows admin/super_admin
        return false;
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSessionRecordings::route('/'),
            'view' => Pages\ViewSessionRecording::route('/{record}'),
        ];
    }
}
