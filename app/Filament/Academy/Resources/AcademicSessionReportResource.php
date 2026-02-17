<?php

namespace App\Filament\Academy\Resources;

use App\Filament\Academy\Resources\AcademicSessionReportResource\Pages\EditAcademicSessionReport;
use App\Filament\Academy\Resources\AcademicSessionReportResource\Pages\ListAcademicSessionReports;
use App\Filament\Academy\Resources\AcademicSessionReportResource\Pages\ViewAcademicSessionReport;
use App\Filament\Shared\Resources\BaseAcademicSessionReportResource;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;

/**
 * Academic Session Report Resource for Academy Panel
 *
 * Academy admins can view and edit all academic session reports in their academy.
 * Reports are auto-generated - no manual creation.
 */
class AcademicSessionReportResource extends BaseAcademicSessionReportResource
{
    protected static string | \UnitEnum | null $navigationGroup = 'التقارير والحضور';

    protected static ?int $navigationSort = 2;

    /**
     * Filter reports to current academy only.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query->where('academy_id', auth()->user()->academy_id);
    }

    /**
     * Read-only session info section for academy admins.
     */
    protected static function getSessionInfoFormSection(): Section
    {
        return Section::make('معلومات الجلسة')
            ->schema([
                TextInput::make('session.title')
                    ->label('الجلسة')
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

    public static function getPages(): array
    {
        return [
            'index' => ListAcademicSessionReports::route('/'),
            'view' => ViewAcademicSessionReport::route('/{record}'),
            'edit' => EditAcademicSessionReport::route('/{record}/edit'),
        ];
    }
}
