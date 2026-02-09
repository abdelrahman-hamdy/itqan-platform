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
            static::getFinalizeAction(),
            static::getDisputeAction(),
            static::getResolveDisputeAction(),
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
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

            TextColumn::make('payout.payout_code')
                ->label('رقم الصرف')
                ->placeholder('غير مصروف')
                ->badge()
                ->color('success'),

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
            Tables\Filters\SelectFilter::make('teacher_type')
                ->label('نوع المعلم')
                ->options([
                    QuranTeacherProfile::class => 'معلم قرآن',
                    AcademicTeacherProfile::class => 'معلم أكاديمي',
                ]),

            Tables\Filters\TernaryFilter::make('is_finalized')
                ->label('الحالة')
                ->placeholder('الكل')
                ->trueLabel('مؤكد')
                ->falseLabel('غير مؤكد'),

            Tables\Filters\TernaryFilter::make('is_disputed')
                ->label('اعتراض')
                ->placeholder('الكل')
                ->trueLabel('معترض عليه')
                ->falseLabel('غير معترض'),

            Tables\Filters\Filter::make('unpaid')
                ->label('غير مصروف')
                ->query(fn (Builder $query) => $query
                    ->whereNull('payout_id')
                    ->where('is_finalized', false)
                    ->where('is_disputed', false)),

            Tables\Filters\Filter::make('earning_month')
                ->form([
                    Forms\Components\DatePicker::make('month')
                        ->label('الشهر')
                        ->displayFormat('M Y'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when(
                        $data['month'],
                        fn (Builder $query, $date): Builder => $query
                            ->whereYear('earning_month', '=', \Carbon\Carbon::parse($date)->year)
                            ->whereMonth('earning_month', '=', \Carbon\Carbon::parse($date)->month)
                    );
                }),

            Tables\Filters\TrashedFilter::make()
                ->label(__('filament.filters.trashed')),
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
            'edit' => Pages\EditTeacherEarning::route('/{record}/edit'),
        ];
    }
}
