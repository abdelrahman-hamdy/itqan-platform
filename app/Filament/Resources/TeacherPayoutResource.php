<?php

namespace App\Filament\Resources;

use App\Enums\PayoutStatus;
use App\Filament\Resources\TeacherPayoutResource\Pages;
use App\Filament\Shared\Resources\BaseTeacherPayoutResource;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Teacher Payout Resource for SuperAdmin Panel
 *
 * Full access with approval workflow and soft delete support.
 * Extends BaseTeacherPayoutResource for shared form/table definitions.
 */
class TeacherPayoutResource extends BaseTeacherPayoutResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationGroup = 'إعدادات المعلمين';

    protected static ?int $navigationSort = 3;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * SuperAdmin sees all payouts, including soft-deleted ones.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    /**
     * Full table actions for SuperAdmin with approval workflow.
     */
    protected static function getTableActions(): array
    {
        return [
            static::getApproveAction(),
            static::getRejectAction(),
            Tables\Actions\ViewAction::make(),
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
                static::getApproveBulkAction(),
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

    public static function canDelete(Model $record): bool
    {
        return true;
    }

    // ========================================
    // Navigation Badge
    // ========================================

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::pending()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
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

            TextColumn::make('payout_code')
                ->label('رقم الدفعة')
                ->searchable()
                ->copyable()
                ->sortable(),

            TextColumn::make('teacher.user.name')
                ->label('المعلم')
                ->searchable()
                ->sortable(),

            TextColumn::make('teacher_type')
                ->label('نوع المعلم')
                ->formatStateUsing(fn ($state) => static::formatTeacherTypeShort($state))
                ->badge()
                ->color(fn ($state) => static::getTeacherTypeColor($state)),

            TextColumn::make('total_amount')
                ->label('المبلغ')
                ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR'))
                ->sortable(),

            TextColumn::make('sessions_count')
                ->label('الجلسات')
                ->sortable(),

            TextColumn::make('payout_month')
                ->label('الشهر')
                ->formatStateUsing(fn ($record) => $record->month_name)
                ->sortable(),

            TextColumn::make('status')
                ->label('الحالة')
                ->badge()
                ->formatStateUsing(fn ($state) => $state->label())
                ->color(fn ($state) => $state->color())
                ->icon(fn ($state) => $state->icon()),

            TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime('Y-m-d H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    // ========================================
    // Table Filters Override (with month picker and trashed)
    // ========================================

    protected static function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('status')
                ->label('الحالة')
                ->options(PayoutStatus::options()),

            Tables\Filters\SelectFilter::make('teacher_type')
                ->label('نوع المعلم')
                ->options([
                    QuranTeacherProfile::class => 'معلم قرآن',
                    AcademicTeacherProfile::class => 'معلم أكاديمي',
                ]),

            Tables\Filters\Filter::make('payout_month')
                ->form([
                    Forms\Components\DatePicker::make('month')
                        ->label('الشهر')
                        ->displayFormat('M Y'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when(
                        $data['month'],
                        fn (Builder $query, $date): Builder => $query
                            ->whereYear('payout_month', '=', \Carbon\Carbon::parse($date)->year)
                            ->whereMonth('payout_month', '=', \Carbon\Carbon::parse($date)->month)
                    );
                }),

            Tables\Filters\TrashedFilter::make()
                ->label(__('filament.filters.trashed')),
        ];
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeacherPayouts::route('/'),
            'view' => Pages\ViewTeacherPayout::route('/{record}'),
        ];
    }
}
