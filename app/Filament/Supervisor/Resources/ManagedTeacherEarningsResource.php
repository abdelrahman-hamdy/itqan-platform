<?php

namespace App\Filament\Supervisor\Resources;

use App\Filament\Shared\Resources\BaseTeacherEarningResource;
use App\Filament\Supervisor\Resources\ManagedTeacherEarningsResource\Pages\ListManagedTeacherEarnings;
use App\Filament\Supervisor\Resources\ManagedTeacherEarningsResource\Pages\ViewManagedTeacherEarning;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
use DB;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

/**
 * Managed Teacher Earnings Resource for Supervisor Panel
 *
 * Read-only view of earnings for assigned teachers with approval actions.
 * Extends BaseTeacherEarningResource for shared form/table definitions.
 * Uses BaseSupervisorResource static methods for teacher filtering.
 */
class ManagedTeacherEarningsResource extends BaseTeacherEarningResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'إدارة المعلمين';

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
     * Scope query to only show earnings of assigned teachers.
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
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Supervisor actions: view + finalize/dispute/resolve actions.
     */
    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
                static::getFinalizeAction(),
                static::getDisputeAction(),
                static::getResolveDisputeAction(),
            ]),
        ];
    }

    /**
     * Supervisor bulk actions: finalize only.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                static::getFinalizeBulkAction(),
            ]),
        ];
    }

    // ========================================
    // Table Override with Comprehensive Filters
    // ========================================

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->deferFilters(false)
            ->deferColumnManager(false);
    }

    protected static function getTableFilters(): array
    {
        $profileIds = static::getAssignedTeacherProfileIds();

        return [
            SelectFilter::make('teacher')
                ->label('المعلم')
                ->options(function () use ($profileIds) {
                    $options = [];

                    if (! empty($profileIds['quran'])) {
                        $quranTeachers = QuranTeacherProfile::with('user')
                            ->whereIn('id', $profileIds['quran'])
                            ->get();
                        foreach ($quranTeachers as $teacher) {
                            if ($teacher->user) {
                                $options['quran_'.$teacher->id] = $teacher->user->name.' (قرآن)';
                            }
                        }
                    }

                    if (! empty($profileIds['academic'])) {
                        $academicTeachers = AcademicTeacherProfile::with('user')
                            ->whereIn('id', $profileIds['academic'])
                            ->get();
                        foreach ($academicTeachers as $teacher) {
                            if ($teacher->user) {
                                $options['academic_'.$teacher->id] = $teacher->user->name.' (أكاديمي)';
                            }
                        }
                    }

                    return $options;
                })
                ->searchable()
                ->multiple()
                ->query(function (Builder $query, array $data): Builder {
                    if (! empty($data['values'])) {
                        $quranIds = [];
                        $academicIds = [];

                        foreach ($data['values'] as $value) {
                            $parts = explode('_', $value, 2);
                            if (count($parts) === 2) {
                                [$type, $id] = $parts;
                                if ($type === 'quran') {
                                    $quranIds[] = $id;
                                } elseif ($type === 'academic') {
                                    $academicIds[] = $id;
                                }
                            }
                        }

                        return $query->where(function ($query) use ($quranIds, $academicIds) {
                            if (! empty($quranIds)) {
                                $query->orWhere(function ($q) use ($quranIds) {
                                    $q->where('teacher_type', QuranTeacherProfile::class)
                                        ->whereIn('teacher_id', $quranIds);
                                });
                            }
                            if (! empty($academicIds)) {
                                $query->orWhere(function ($q) use ($academicIds) {
                                    $q->where('teacher_type', AcademicTeacherProfile::class)
                                        ->whereIn('teacher_id', $academicIds);
                                });
                            }
                        });
                    }

                    return $query;
                }),

            SelectFilter::make('teacher_type')
                ->label('نوع المعلم')
                ->options([
                    QuranTeacherProfile::class => 'معلم قرآن',
                    AcademicTeacherProfile::class => 'معلم أكاديمي',
                ])
                ->multiple(),

            SelectFilter::make('earning_month')
                ->label('الشهر')
                ->options(function () {
                    return DB::table('teacher_earnings')
                        ->selectRaw('DATE_FORMAT(earning_month, "%Y-%m") as month_key, DATE_FORMAT(earning_month, "%M %Y") as month_label, MAX(earning_month) as sort_date')
                        ->groupBy('month_key', 'month_label')
                        ->orderBy('sort_date', 'desc')
                        ->limit(24)
                        ->pluck('month_label', 'month_key')
                        ->toArray();
                })
                ->query(function (Builder $query, array $data): Builder {
                    if (! empty($data['value'])) {
                        [$year, $month] = explode('-', $data['value']);

                        return $query
                            ->whereYear('earning_month', '=', $year)
                            ->whereMonth('earning_month', '=', $month);
                    }

                    return $query;
                }),

            SelectFilter::make('status')
                ->label('الحالة')
                ->options([
                    'finalized' => 'مؤكد',
                    'disputed' => 'معترض عليه',
                    'pending' => 'معلق',
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return match ($data['value'] ?? null) {
                        'finalized' => $query->where('is_finalized', true)->where('is_disputed', false),
                        'disputed' => $query->where('is_disputed', true),
                        'pending' => $query->where('is_finalized', false)->where('is_disputed', false),
                        default => $query,
                    };
                }),

            SelectFilter::make('calculation_method')
                ->label('طريقة الحساب')
                ->options([
                    'individual_rate' => __('earnings.calculation_methods.individual_rate'),
                    'group_rate' => __('earnings.calculation_methods.group_rate'),
                    'per_session' => __('earnings.calculation_methods.per_session'),
                    'per_student' => __('earnings.calculation_methods.per_student'),
                    'fixed' => __('earnings.calculation_methods.fixed'),
                ])
                ->multiple(),

            Filter::make('amount_range')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('amount_from')
                                ->label('من')
                                ->numeric()
                                ->prefix(getCurrencySymbol()),
                            TextInput::make('amount_to')
                                ->label('إلى')
                                ->numeric()
                                ->prefix(getCurrencySymbol()),
                        ]),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['amount_from'],
                            fn (Builder $query, $amount): Builder => $query->where('amount', '>=', $amount),
                        )
                        ->when(
                            $data['amount_to'],
                            fn (Builder $query, $amount): Builder => $query->where('amount', '<=', $amount),
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    if ($data['amount_from'] ?? null) {
                        $indicators[] = Indicator::make('من: '.number_format($data['amount_from'], 2).' '.getCurrencySymbol())
                            ->removeField('amount_from');
                    }

                    if ($data['amount_to'] ?? null) {
                        $indicators[] = Indicator::make('إلى: '.number_format($data['amount_to'], 2).' '.getCurrencySymbol())
                            ->removeField('amount_to');
                    }

                    return $indicators;
                }),
        ];
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
            'index' => ListManagedTeacherEarnings::route('/'),
            'view' => ViewManagedTeacherEarning::route('/{record}'),
        ];
    }
}
