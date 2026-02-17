<?php

namespace App\Filament\Resources;

use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Tables\Filters\SelectFilter;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Filament\Resources\SessionRecordingResource\Pages\ListSessionRecordings;
use App\Filament\Resources\SessionRecordingResource\Pages\ViewSessionRecording;
use App\Filament\Resources\SessionRecordingResource\Pages;
use App\Filament\Shared\Resources\BaseSessionRecordingResource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

/**
 * Session Recording Resource for SuperAdmin Panel
 *
 * SuperAdmin can view and manage all recordings across all academies.
 * Extends BaseSessionRecordingResource for shared form/table definitions.
 */
class SessionRecordingResource extends BaseSessionRecordingResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 10;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * SuperAdmin sees all recordings across all academies.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        // No additional filtering - super admin sees everything
        return $query;
    }

    /**
     * Full table actions for SuperAdmin.
     */
    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->label('عرض'),
                static::makeDownloadAction(),
                static::makeStreamAction(),
                static::makeDeleteAction(),
            ]),
        ];
    }

    /**
     * Bulk actions for SuperAdmin.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                BulkAction::make('delete_selected')
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
    // Additional Filters for SuperAdmin
    // ========================================

    protected static function getTableFilters(): array
    {
        return [
            ...parent::getTableFilters(),

            // Filter by Course
            SelectFilter::make('course')
                ->label('الدورة')
                ->options(function () {
                    return InteractiveCourse::query()
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
            'index' => ListSessionRecordings::route('/'),
            'view' => ViewSessionRecording::route('/{record}'),
        ];
    }
}
