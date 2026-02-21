<?php

namespace App\Filament\Academy\Resources;

use App\Filament\Academy\Resources\StudentSessionReportResource\Pages\EditStudentSessionReport;
use App\Filament\Academy\Resources\StudentSessionReportResource\Pages\ListStudentSessionReports;
use App\Filament\Academy\Resources\StudentSessionReportResource\Pages\ViewStudentSessionReport;
use App\Filament\Shared\Resources\BaseStudentSessionReportResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;

/**
 * Student Session Report Resource for Academy Panel (Quran Reports)
 *
 * Academy admins can view and edit all Quran session reports in their academy.
 * Reports are auto-generated - no manual creation.
 */
class StudentSessionReportResource extends BaseStudentSessionReportResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'التقارير والحضور';

    protected static ?int $navigationSort = 1;

    /**
     * Filter reports to current academy only.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        // BP-004: Null check to prevent errors in queue/testing context
        $academyId = auth()->user()?->academy_id;
        if (! $academyId) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('academy_id', $academyId);
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
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
                DeleteAction::make()
                    ->label('حذف'),
            ]),
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
            'index' => ListStudentSessionReports::route('/'),
            'view' => ViewStudentSessionReport::route('/{record}'),
            'edit' => EditStudentSessionReport::route('/{record}/edit'),
        ];
    }
}
