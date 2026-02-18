<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Filament\AcademicTeacher\Resources\TeacherEarningsResource\Pages;
use App\Filament\AcademicTeacher\Resources\TeacherEarningsResource\Pages\ListTeacherEarnings;
use App\Filament\AcademicTeacher\Resources\TeacherEarningsResource\Pages\ViewTeacherEarning;
use App\Filament\Shared\Resources\BaseTeacherEarningResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Teacher Earnings Resource for Academic Teacher Panel
 *
 * Allows academic teachers to view their earnings (read-only).
 * Extends BaseTeacherEarningResource for shared form/table definitions.
 */
class TeacherEarningsResource extends BaseTeacherEarningResource
{
    // ========================================
    // Navigation Configuration (Academic Teacher-specific)
    // ========================================

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'أرباحي';

    protected static ?string $modelLabel = 'أرباح';

    protected static ?string $pluralModelLabel = 'أرباحي';

    protected static string|\UnitEnum|null $navigationGroup = 'المالية';

    protected static ?int $navigationSort = 1;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * Scope query to current academic teacher's earnings only.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        $user = Auth::user();

        if ($user->academicTeacherProfile) {
            $query->where('teacher_type', 'App\\Models\\AcademicTeacherProfile')
                ->where('teacher_id', $user->academicTeacherProfile->id);
        } else {
            // No profile - return empty result
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Table actions - view only for teachers.
     */
    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
            ]),
        ];
    }

    /**
     * No bulk actions for teachers.
     */
    protected static function getTableBulkActions(): array
    {
        return [];
    }

    // ========================================
    // Table Customizations (Academic Teacher-specific)
    // ========================================

    /**
     * Custom columns for teacher view with summarizers.
     * Teachers don't need teacher name column (it's always them).
     * Includes hourly_rate method for academic teachers.
     */
    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('earning_month')
                ->label('الشهر')
                ->date('Y-m')
                ->sortable(),

            TextColumn::make('amount')
                ->label('المبلغ')
                ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR'))
                ->sortable(),

            TextColumn::make('calculation_method')
                ->label('طريقة الحساب')
                ->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'individual_rate' => 'جلسة فردية',
                    'group_rate' => 'جلسة جماعية',
                    'per_session' => 'حسب الجلسة',
                    'per_student' => 'حسب الطالب',
                    'hourly_rate' => 'بالساعة',
                    'fixed' => 'مبلغ ثابت',
                    default => $state,
                })
                ->color(fn (string $state): string => match ($state) {
                    'individual_rate' => 'primary',
                    'group_rate' => 'success',
                    'per_session' => 'info',
                    'per_student' => 'warning',
                    'hourly_rate' => 'purple',
                    'fixed' => 'gray',
                    default => 'gray',
                }),

            TextColumn::make('session_completed_at')
                ->label('تاريخ الجلسة')
                ->dateTime('Y-m-d H:i')
                ->sortable()
                ->toggleable(),

            IconColumn::make('is_finalized')
                ->label('مؤكد')
                ->boolean()
                ->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-clock')
                ->trueColor('success')
                ->falseColor('warning'),

            IconColumn::make('is_disputed')
                ->label('متنازع')
                ->boolean()
                ->trueIcon('heroicon-o-exclamation-triangle')
                ->falseIcon('heroicon-o-check')
                ->trueColor('danger')
                ->falseColor('success')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('payout.reference_number')
                ->label('رقم الدفعة')
                ->placeholder('لم تصرف بعد')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('calculated_at')
                ->label('تاريخ الحساب')
                ->dateTime('Y-m-d')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * Custom filters for teacher view - full width above table with month selector.
     * Includes hourly_rate method for academic teachers.
     */
    protected static function getTableFilters(): array
    {
        // Generate month options (last 12 months)
        $monthOptions = [];
        for ($i = 0; $i < 12; $i++) {
            $date = now()->subMonths($i);
            $monthOptions[$date->format('Y-m')] = $date->locale('ar')->translatedFormat('F Y');
        }

        return [
            SelectFilter::make('earning_month')
                ->label('الشهر')
                ->options($monthOptions)
                ->query(function (Builder $query, array $data): Builder {
                    if (! empty($data['value'])) {
                        [$year, $month] = explode('-', $data['value']);
                        $query->whereYear('earning_month', $year)
                            ->whereMonth('earning_month', $month);
                    }

                    return $query;
                })
                ->placeholder('جميع الأشهر'),

            SelectFilter::make('is_finalized')
                ->label('حالة التأكيد')
                ->options([
                    '1' => 'مؤكد',
                    '0' => 'قيد المراجعة',
                ])
                ->placeholder('الكل'),

            SelectFilter::make('calculation_method')
                ->label('طريقة الحساب')
                ->options([
                    'individual_rate' => 'جلسة فردية',
                    'group_rate' => 'جلسة جماعية',
                    'per_session' => 'حسب الجلسة',
                    'per_student' => 'حسب الطالب',
                    'hourly_rate' => 'بالساعة',
                    'fixed' => 'مبلغ ثابت',
                ])
                ->placeholder('الكل'),

            TernaryFilter::make('is_disputed')
                ->label('النزاعات')
                ->placeholder('الكل')
                ->trueLabel('متنازع عليها فقط')
                ->falseLabel('غير متنازع عليها'),
        ];
    }

    /**
     * Configure filters layout - full width above table.
     */
    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters(), layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->recordActions(static::getTableActions())
            ->toolbarActions(static::getTableBulkActions())
            ->defaultSort('session_completed_at', 'desc');
    }

    // ========================================
    // Infolist (Academic Teacher-specific view)
    // ========================================

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الأرباح')
                    ->schema([
                        TextEntry::make('amount')
                            ->label('المبلغ')
                            ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR')),
                        TextEntry::make('calculation_method')
                            ->label('طريقة الحساب')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'individual_rate' => 'جلسة فردية',
                                'group_rate' => 'جلسة جماعية',
                                'per_session' => 'حسب الجلسة',
                                'per_student' => 'حسب الطالب',
                                'hourly_rate' => 'بالساعة',
                                'fixed' => 'مبلغ ثابت',
                                default => $state,
                            }),
                        TextEntry::make('earning_month')
                            ->label('شهر الأرباح')
                            ->date('Y-m'),
                        TextEntry::make('session_completed_at')
                            ->label('تاريخ الجلسة')
                            ->dateTime('Y-m-d H:i'),
                    ])->columns(2),

                Section::make('حالة الدفع')
                    ->schema([
                        IconEntry::make('is_finalized')
                            ->label('مؤكد')
                            ->boolean(),
                        IconEntry::make('is_disputed')
                            ->label('متنازع')
                            ->boolean(),
                        TextEntry::make('payout.reference_number')
                            ->label('رقم الدفعة')
                            ->placeholder('لم تصرف بعد'),
                        TextEntry::make('dispute_notes')
                            ->label('ملاحظات النزاع')
                            ->visible(fn ($record) => $record->is_disputed),
                    ])->columns(2),
            ]);
    }

    // ========================================
    // Widgets
    // ========================================

    public static function getWidgets(): array
    {
        return [
            TeacherEarningsResource\Widgets\EarningsStatsWidget::class,
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
