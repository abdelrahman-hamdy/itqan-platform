<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherEarningResource\Pages;
use App\Filament\Shared\Resources\BaseTeacherEarningResource;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
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

    protected static ?string $navigationGroup = 'إعدادات المعلمين';

    protected static ?int $navigationSort = 2;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * SuperAdmin sees all earnings, including soft-deleted ones.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
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
            Tables\Actions\ViewAction::make(),
            static::getFinalizeAction(),
            static::getDisputeAction(),
            static::getResolveDisputeAction(),
            Tables\Actions\DeleteAction::make(),
            Tables\Actions\RestoreAction::make()
                ->label(__('filament.actions.restore')),
            Tables\Actions\ForceDeleteAction::make()
                ->label(__('filament.actions.force_delete')),
        ];
    }

    /**
     * Full bulk actions for SuperAdmin.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                static::getFinalizeBulkAction(),
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make()
                    ->label(__('filament.actions.restore_selected')),
                Tables\Actions\ForceDeleteBulkAction::make()
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
    // Table Columns Override (with Academy column)
    // ========================================

    protected static function getTableColumns(): array
    {
        return [
            // Academy column for multi-tenant context
            TextColumn::make('academy.name')
                ->label('الأكاديمية')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('teacher.user.name')
                ->label('المعلم')
                ->searchable()
                ->sortable(),

            TextColumn::make('teacher_type')
                ->label('نوع المعلم')
                ->formatStateUsing(fn ($state) => static::formatTeacherTypeShort($state))
                ->badge()
                ->color(fn ($state) => static::getTeacherTypeColor($state)),

            TextColumn::make('amount')
                ->label('المبلغ')
                ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR'))
                ->sortable(),

            TextColumn::make('calculation_method')
                ->label('طريقة الحساب')
                ->formatStateUsing(fn ($record) => $record->calculation_method_label)
                ->badge()
                ->color('gray'),

            TextColumn::make('earning_month')
                ->label('الشهر')
                ->date('M Y')
                ->sortable(),

            Tables\Columns\IconColumn::make('is_finalized')
                ->label('مؤكد')
                ->boolean(),

            Tables\Columns\IconColumn::make('is_disputed')
                ->label('معترض')
                ->boolean()
                ->trueColor('danger')
                ->falseColor('gray'),

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

    public static function table(Tables\Table $table): Tables\Table
    {
        return parent::table($table)
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(3);
    }

    // ========================================
    // Table Filters Override (Full-width above table)
    // ========================================

    protected static function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('teacher_id')
                ->label('المعلم')
                ->relationship('teacher.user', 'name')
                ->searchable()
                ->preload()
                ->multiple(),

            Tables\Filters\SelectFilter::make('teacher_type')
                ->label('نوع المعلم')
                ->options([
                    QuranTeacherProfile::class => 'معلم قرآن',
                    AcademicTeacherProfile::class => 'معلم أكاديمي',
                ])
                ->multiple(),

            Tables\Filters\SelectFilter::make('earning_month')
                ->label('الشهر')
                ->options(function () {
                    // Get last 12 months of earnings
                    $months = \DB::table('teacher_earnings')
                        ->selectRaw('DATE_FORMAT(earning_month, "%Y-%m") as month_key, DATE_FORMAT(earning_month, "%M %Y") as month_label')
                        ->distinct()
                        ->orderBy('earning_month', 'desc')
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

            Tables\Filters\SelectFilter::make('status')
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

            Tables\Filters\SelectFilter::make('calculation_method')
                ->label('طريقة الحساب')
                ->options([
                    'individual_rate' => __('earnings.calculation_methods.individual_rate'),
                    'group_rate' => __('earnings.calculation_methods.group_rate'),
                    'per_session' => __('earnings.calculation_methods.per_session'),
                    'per_student' => __('earnings.calculation_methods.per_student'),
                    'fixed' => __('earnings.calculation_methods.fixed'),
                ])
                ->multiple(),

            Tables\Filters\Filter::make('amount_range')
                ->form([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('amount_from')
                                ->label('من')
                                ->numeric()
                                ->prefix(getCurrencySymbol()),
                            Forms\Components\TextInput::make('amount_to')
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
                        $indicators[] = Tables\Filters\Indicator::make('من: '.number_format($data['amount_from'], 2).' '.getCurrencySymbol())
                            ->removeField('amount_from');
                    }

                    if ($data['amount_to'] ?? null) {
                        $indicators[] = Tables\Filters\Indicator::make('إلى: '.number_format($data['amount_to'], 2).' '.getCurrencySymbol())
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
            Forms\Components\Toggle::make('is_finalized')
                ->label('تم التأكيد')
                ->helperText('هل تم تأكيد هذا الربح؟'),

            Forms\Components\Toggle::make('is_disputed')
                ->label('معترض عليه')
                ->helperText('هل يوجد اعتراض على هذا الربح؟'),

            Forms\Components\Textarea::make('dispute_notes')
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
            'index' => Pages\ListTeacherEarnings::route('/'),
            'view' => Pages\ViewTeacherEarning::route('/{record}'),
        ];
    }
}
