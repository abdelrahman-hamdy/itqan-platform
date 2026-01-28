<?php

namespace App\Filament\Academy\Resources;

use App\Filament\Academy\Resources\SessionRecordingResource\Pages;
use App\Filament\Shared\Resources\BaseSessionRecordingResource;
use App\Models\InteractiveCourseSession;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Session Recording Resource for Academy (Admin) Panel
 *
 * Admin can view and manage recordings from their academy's courses only.
 * Extends BaseSessionRecordingResource for shared form/table definitions.
 */
class SessionRecordingResource extends BaseSessionRecordingResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 10;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * Admin sees recordings only from their academy's courses.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        $user = Auth::user();
        $academyId = $user?->academy_id;

        if ($academyId) {
            // Filter recordings where the session's course belongs to this academy
            $query->whereHasMorph(
                'recordable',
                [InteractiveCourseSession::class],
                function ($q) use ($academyId) {
                    $q->whereHas('course', function ($courseQuery) use ($academyId) {
                        $courseQuery->where('academy_id', $academyId);
                    });
                }
            );
        }

        return $query;
    }

    /**
     * Full table actions for Academy Admin.
     */
    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make()->label('عرض'),
                static::makeDownloadAction(),
                static::makeStreamAction(),
                static::makeDeleteAction(),
            ]),
        ];
    }

    /**
     * Bulk actions for Academy Admin.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\BulkAction::make('delete_selected')
                    ->label('حذف المحدد')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('حذف التسجيلات المحددة')
                    ->modalDescription('هل أنت متأكد من حذف التسجيلات المحددة؟ لن يتم حذف الملفات نهائياً.')
                    ->modalSubmitActionLabel('نعم، احذف')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            if ($record->status->canDelete()) {
                                $record->markAsDeleted();
                            }
                        }
                    }),
            ]),
        ];
    }

    // ========================================
    // Additional Filters for Academy Admin
    // ========================================

    protected static function getTableFilters(): array
    {
        return [
            ...parent::getTableFilters(),

            // Filter by Course (scoped to academy)
            Tables\Filters\SelectFilter::make('course')
                ->label('الدورة')
                ->options(function () {
                    $academyId = Auth::user()?->academy_id;

                    return \App\Models\InteractiveCourse::query()
                        ->when($academyId, fn ($q) => $q->where('academy_id', $academyId))
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
