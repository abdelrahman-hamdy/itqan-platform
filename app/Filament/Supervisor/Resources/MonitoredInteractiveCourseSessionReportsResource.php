<?php

namespace App\Filament\Supervisor\Resources;

use App\Filament\Shared\Resources\BaseInteractiveSessionReportResource;
use App\Filament\Supervisor\Resources\MonitoredInteractiveCourseSessionReportsResource\Pages\ListMonitoredInteractiveCourseSessionReports;
use App\Filament\Supervisor\Resources\MonitoredInteractiveCourseSessionReportsResource\Pages\ViewMonitoredInteractiveCourseSessionReport;
use App\Models\InteractiveSessionReport;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Monitored Interactive Course Session Reports Resource for Supervisor Panel
 * Shows interactive session reports for the supervisor's derived interactive courses.
 * Read-only: supervisors can monitor but not edit reports.
 */
class MonitoredInteractiveCourseSessionReportsResource extends BaseInteractiveSessionReportResource
{
    protected static bool $isScopedToTenant = false;

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 3;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        $courseIds = BaseSupervisorResource::getDerivedInteractiveCourseIds();
        if (empty($courseIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('session', fn (Builder $q) => $q->whereIn('course_id', $courseIds));
    }

    protected static function getSessionInfoFormSection(): Section
    {
        return Section::make('معلومات الجلسة')
            ->schema([
                TextInput::make('session_id')
                    ->label('رقم الجلسة')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('student_id')
                    ->label('رقم الطالب')
                    ->disabled()
                    ->dehydrated(false),
            ])->columns(2);
    }

    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->label('عرض'),
            ]),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [];
    }

    // ========================================
    // Authorization
    // ========================================

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return BaseSupervisorResource::hasDerivedInteractiveCourses();
    }

    // ========================================
    // Eloquent Query - bypass Filament tenant scoping
    // ========================================

    public static function getEloquentQuery(): Builder
    {
        $query = InteractiveSessionReport::query()->with(['session', 'session.course', 'student']);

        return static::scopeEloquentQuery($query);
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => ListMonitoredInteractiveCourseSessionReports::route('/'),
            'view' => ViewMonitoredInteractiveCourseSessionReport::route('/{record}'),
        ];
    }
}
