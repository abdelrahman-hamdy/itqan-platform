<?php

namespace App\Filament\Academy\Resources;

use App\Filament\Academy\Resources\InteractiveSessionReportResource\Pages\EditInteractiveSessionReport;
use App\Filament\Academy\Resources\InteractiveSessionReportResource\Pages\ListInteractiveSessionReports;
use App\Filament\Academy\Resources\InteractiveSessionReportResource\Pages\ViewInteractiveSessionReport;
use App\Filament\Shared\Resources\BaseInteractiveSessionReportResource;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;

/**
 * Interactive Session Report Resource for Academy Panel
 *
 * Academy admins can view and edit all interactive course session reports.
 * Reports are auto-generated - no manual creation.
 * Scoped via session.course.academy relationship.
 */
class InteractiveSessionReportResource extends BaseInteractiveSessionReportResource
{
    protected static string | \UnitEnum | null $navigationGroup = 'التقارير والحضور';

    protected static ?int $navigationSort = 3;

    /**
     * Filter reports via session.course.academy relationship.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        $academyId = auth()->user()->academy_id;

        return $query->whereHas('session.course', function ($q) use ($academyId) {
            $q->where('academy_id', $academyId);
        });
    }

    /**
     * Read-only session info section for academy admins.
     */
    protected static function getSessionInfoFormSection(): Section
    {
        return Section::make('معلومات الجلسة')
            ->schema([
                TextInput::make('session.course.name')
                    ->label('الدورة')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('student.name')
                    ->label('الطالب')
                    ->disabled()
                    ->dehydrated(false),
            ])->columns(2);
    }

    /**
     * Table actions for academy admins.
     */
    protected static function getTableActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض'),
            EditAction::make()
                ->label('تعديل'),
        ];
    }

    /**
     * No bulk actions for reports.
     */
    protected static function getTableBulkActions(): array
    {
        return [];
    }

    /**
     * Override to bypass parent's tenant scoping.
     * InteractiveSessionReport has no direct academy_id.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = static::getModel()::query()
            ->with([
                'session',
                'session.course',
                'student',
            ]);

        return static::scopeEloquentQuery($query);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInteractiveSessionReports::route('/'),
            'view' => ViewInteractiveSessionReport::route('/{record}'),
            'edit' => EditInteractiveSessionReport::route('/{record}/edit'),
        ];
    }
}
