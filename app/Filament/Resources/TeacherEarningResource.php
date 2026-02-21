<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherEarningResource\Pages;
use App\Filament\Resources\TeacherEarningResource\Pages\ListTeacherEarnings;
use App\Filament\Resources\TeacherEarningResource\Pages\ViewTeacherEarning;
use App\Filament\Shared\Resources\BaseTeacherEarningResource;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
use DB;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Teacher Earning Resource for SuperAdmin Panel
 *
 * Full CRUD access with finalize/dispute workflow and soft delete support.
 * Extends BaseTeacherEarningResource for shared form/table definitions.
 */
class TeacherEarningResource extends BaseTeacherEarningResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static string|\UnitEnum|null $navigationGroup = 'إعدادات المعلمين';

    protected static ?int $navigationSort = 2;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * SuperAdmin sees all earnings, including soft-deleted ones.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        // Include soft-deleted records for admin management
        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    /**
     * Full table actions for SuperAdmin with finalize/dispute workflow.
     */
    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->label('عرض'),
                static::getFinalizeAction(),
                static::getDisputeAction(),
                static::getResolveDisputeAction(),
                DeleteAction::make()->label('حذف'),
                RestoreAction::make()->label(__('filament.actions.restore')),
                ForceDeleteAction::make()->label(__('filament.actions.force_delete')),
            ]),
        ];
    }

    /**
     * Full bulk actions for SuperAdmin.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                static::getFinalizeBulkAction(),
                DeleteBulkAction::make(),
                RestoreBulkAction::make()
                    ->label(__('filament.actions.restore_selected')),
                ForceDeleteBulkAction::make()
                    ->label(__('filament.actions.force_delete_selected')),
            ]),
        ];
    }

    // ========================================
    // Authorization Overrides
    // ========================================

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        return true;
    }

    // ========================================
    // Navigation Badge
    // ========================================

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::unpaid()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    // ========================================
    // Academy Column (not inherited from BaseResource)
    // ========================================

    protected static function getAcademyColumn(): TextColumn
    {
        return TextColumn::make('academy.name')
            ->label('الأكاديمية')
            ->sortable()
            ->searchable()
            ->visible(fn () => Filament::getTenant() === null)
            ->placeholder('غير محدد');
    }

    // ========================================
    // Table Columns Override (with Academy column)
    // ========================================

    protected static function getTableColumns(): array
    {
        return [
            // Academy column with automatic visibility logic
            static::getAcademyColumn(),

            TextColumn::make('teacher.user.name')
                ->label('المعلم')
                ->searchable()
                ->sortable(),

            TextColumn::make('amount')
                ->label('المبلغ')
                ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR'))
                ->sortable(),

            TextColumn::make('teacher_type')
                ->label('نوع المعلم')
                ->formatStateUsing(fn ($state) => static::formatTeacherTypeShort($state))
                ->badge()
                ->color(fn ($state) => static::getTeacherTypeColor($state))
                ->toggleable(),

            TextColumn::make('calculation_method')
                ->label('طريقة الحساب')
                ->formatStateUsing(fn ($record) => $record->calculation_method_label)
                ->badge()
                ->color('gray')
                ->toggleable(),

            TextColumn::make('earning_month')
                ->label('الشهر')
                ->date('M Y')
                ->sortable()
                ->toggleable(),

            IconColumn::make('is_finalized')
                ->label('مؤكد')
                ->boolean()
                ->toggleable(),

            IconColumn::make('is_disputed')
                ->label('معترض')
                ->boolean()
                ->trueColor('danger')
                ->falseColor('gray')
                ->toggleable(),

            TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime('Y-m-d H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    // ========================================
    // Table Override with Filters Layout
    // ========================================

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->deferFilters(false)
            ->deferColumnManager(false);
    }

    // ========================================
    // Table Filters Override (Full-width above table)
    // ========================================

    protected static function getTableFilters(): array
    {
        return [
            SelectFilter::make('teacher')
                ->label('المعلم')
                ->options(function () {
                    // Get all teachers with composite keys (type_id format)
                    $options = [];

                    $quranTeachers = QuranTeacherProfile::with('user')->get();
                    foreach ($quranTeachers as $teacher) {
                        $key = 'quran_'.$teacher->id;
                        $options[$key] = $teacher->user->name.' (قرآن)';
                    }

                    $academicTeachers = AcademicTeacherProfile::with('user')->get();
                    foreach ($academicTeachers as $teacher) {
                        $key = 'academic_'.$teacher->id;
                        $options[$key] = $teacher->user->name.' (أكاديمي)';
                    }

                    return $options;
                })
                ->searchable()
                ->multiple()
                ->query(function (Builder $query, array $data): Builder {
                    if (! empty($data['values'])) {
                        // Parse composite keys and build query
                        $quranIds = [];
                        $academicIds = [];

                        foreach ($data['values'] as $value) {
                            [$type, $id] = explode('_', $value);
                            if ($type === 'quran') {
                                $quranIds[] = $id;
                            } elseif ($type === 'academic') {
                                $academicIds[] = $id;
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
                    // Get last 24 months of earnings using GROUP BY
                    $months = DB::table('teacher_earnings')
                        ->selectRaw('DATE_FORMAT(earning_month, "%Y-%m") as month_key, DATE_FORMAT(earning_month, "%M %Y") as month_label, MAX(earning_month) as sort_date')
                        ->groupBy('month_key', 'month_label')
                        ->orderBy('sort_date', 'desc')
                        ->limit(24)
                        ->pluck('month_label', 'month_key')
                        ->toArray();

                    return $months;
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
    // Status Form Fields Override (Editable for SuperAdmin)
    // ========================================

    protected static function getStatusFormFields(): array
    {
        return [
            Toggle::make('is_finalized')
                ->label('تم التأكيد')
                ->helperText('هل تم تأكيد هذا الربح؟'),

            Toggle::make('is_disputed')
                ->label('معترض عليه')
                ->helperText('هل يوجد اعتراض على هذا الربح؟'),

            Textarea::make('dispute_notes')
                ->label('ملاحظات الاعتراض')
                ->rows(3)
                ->maxLength(2000)
                ->helperText('الحد الأقصى 2000 حرف')
                ->visible(fn ($get) => $get('is_disputed')),
        ];
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => ListTeacherEarnings::route('/'),
            'view' => ViewTeacherEarning::route('/{record}'),
        ];
    }
}
