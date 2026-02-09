<?php

namespace App\Filament\Shared\Resources;

use App\Enums\PayoutStatus;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
use App\Models\TeacherPayout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Teacher Payout Resource
 *
 * Shared functionality for SuperAdmin and Supervisor panels.
 * Child classes must implement query scoping and authorization methods.
 *
 * Pattern: Shared form/table definitions with abstract methods for panel-specific behavior.
 */
abstract class BaseTeacherPayoutResource extends Resource
{
    protected static ?string $model = TeacherPayout::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'مدفوعات المعلمين';

    protected static ?string $modelLabel = 'دفعة معلم';

    protected static ?string $pluralModelLabel = 'مدفوعات المعلمين';

    // ========================================
    // Abstract Methods - Panel-specific implementation
    // ========================================

    /**
     * Apply panel-specific query scoping.
     * SuperAdmin: may include all payouts or filter by academy
     * Supervisor: filters by assigned teacher profiles
     */
    abstract protected static function scopeEloquentQuery(Builder $query): Builder;

    /**
     * Get panel-specific table actions.
     * SuperAdmin: view, delete, restore actions + approve/reject
     * Supervisor: view + approve/reject actions only
     */
    abstract protected static function getTableActions(): array;

    /**
     * Get panel-specific bulk actions.
     * SuperAdmin: approve, delete, restore bulk actions
     * Supervisor: approve bulk action only
     */
    abstract protected static function getTableBulkActions(): array;

    // ========================================
    // Authorization - Override in child classes
    // ========================================

    /**
     * Can create new payouts.
     * Default: false - payouts are auto-generated.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Can edit payouts.
     * Default: false - payouts are managed through actions.
     */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /**
     * Can delete payouts.
     * Default: false - override in child class (e.g., SuperAdmin).
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    // ========================================
    // Shared Form Definition
    // ========================================

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الدفعة')
                    ->schema([
                        Forms\Components\TextInput::make('payout_code')
                            ->label('رقم الدفعة')
                            ->disabled(),

                        Forms\Components\TextInput::make('teacher.user.name')
                            ->label('المعلم')
                            ->disabled(),

                        Forms\Components\TextInput::make('teacher_type')
                            ->label('نوع المعلم')
                            ->formatStateUsing(fn ($state) => static::formatTeacherType($state))
                            ->disabled(),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('المبلغ الإجمالي')
                            ->numeric()
                            ->prefix(getCurrencySymbol())
                            ->disabled(),

                        Forms\Components\TextInput::make('sessions_count')
                            ->label('عدد الجلسات')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\DatePicker::make('payout_month')
                            ->label('شهر الدفع')
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(PayoutStatus::options())
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('تفاصيل الموافقة')
                    ->schema([
                        Forms\Components\Placeholder::make('approver_name')
                            ->label('تمت الموافقة بواسطة')
                            ->content(fn ($record) => $record?->approver?->name ?? '-'),

                        Forms\Components\Placeholder::make('approved_at_display')
                            ->label('تاريخ الموافقة')
                            ->content(fn ($record) => $record?->approved_at?->format('Y-m-d H:i') ?? '-'),

                        Forms\Components\Placeholder::make('approval_notes_display')
                            ->label('ملاحظات الموافقة')
                            ->content(fn ($record) => $record?->approval_notes ?? '-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record?->approved_at !== null),

                Forms\Components\Section::make('تفاصيل الرفض')
                    ->schema([
                        Forms\Components\Placeholder::make('rejector_name')
                            ->label('تم الرفض بواسطة')
                            ->content(fn ($record) => $record?->rejector?->name ?? '-'),

                        Forms\Components\Placeholder::make('rejected_at_display')
                            ->label('تاريخ الرفض')
                            ->content(fn ($record) => $record?->rejected_at?->format('Y-m-d H:i') ?? '-'),

                        Forms\Components\Placeholder::make('rejection_reason_display')
                            ->label('سبب الرفض')
                            ->content(fn ($record) => $record?->rejection_reason ?? '-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record?->rejected_at !== null),

                Forms\Components\Section::make('تفاصيل الأرباح')
                    ->schema([
                        Forms\Components\Placeholder::make('breakdown_display')
                            ->label('تفصيل الأرباح')
                            ->content(fn ($record) => $record?->formatted_breakdown ?? '-'),
                    ]),
            ]);
    }

    // ========================================
    // Shared Table Definition
    // ========================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->defaultSort('created_at', 'desc')
            ->filters(static::getTableFilters())
            ->actions(static::getTableActions())
            ->bulkActions(static::getTableBulkActions());
    }

    /**
     * Get the table columns - shared across panels.
     */
    protected static function getTableColumns(): array
    {
        return [
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
                ->formatStateUsing(fn ($record) => $record->month_name ?? $record->payout_month?->format('Y-m'))
                ->sortable(),

            TextColumn::make('status')
                ->label('الحالة')
                ->badge()
                ->formatStateUsing(fn ($state) => $state instanceof PayoutStatus ? $state->label() : (PayoutStatus::tryFrom($state)?->label() ?? $state))
                ->color(fn ($state) => $state instanceof PayoutStatus ? $state->color() : (PayoutStatus::tryFrom($state)?->color() ?? 'gray'))
                ->icon(fn ($state) => $state instanceof PayoutStatus ? $state->icon() : (PayoutStatus::tryFrom($state)?->icon() ?? null)),

            TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime('Y-m-d H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * Get the table filters - shared across panels.
     */
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
        ];
    }

    // ========================================
    // Common Table Actions (can be used by child classes)
    // ========================================

    /**
     * Get approve action for payouts.
     */
    protected static function getApproveAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('approve')
            ->label('موافقة')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn ($record) => $record->canApprove())
            ->form([
                Forms\Components\Textarea::make('approval_notes')
                    ->label('ملاحظات الموافقة')
                    ->rows(2),
            ])
            ->action(function ($record, array $data) {
                $record->update([
                    'status' => PayoutStatus::APPROVED,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'approval_notes' => $data['approval_notes'] ?? null,
                ]);

                // Mark all related earnings as finalized
                $record->earnings()->update(['is_finalized' => true]);
            });
    }

    /**
     * Get reject action for payouts.
     */
    protected static function getRejectAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('reject')
            ->label('رفض')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn ($record) => $record->canReject())
            ->form([
                Forms\Components\Textarea::make('rejection_reason')
                    ->label('سبب الرفض')
                    ->required()
                    ->rows(3),
            ])
            ->action(function ($record, array $data) {
                $record->update([
                    'status' => PayoutStatus::REJECTED,
                    'rejected_by' => auth()->id(),
                    'rejected_at' => now(),
                    'rejection_reason' => $data['rejection_reason'],
                ]);
            });
    }

    /**
     * Get approve bulk action.
     */
    protected static function getApproveBulkAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('approve_selected')
            ->label('موافقة المحدد')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->action(function ($records) {
                $records->each(function ($record) {
                    if ($record->canApprove()) {
                        $record->update([
                            'status' => PayoutStatus::APPROVED,
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                    }
                });
            });
    }

    // ========================================
    // Utility Methods
    // ========================================

    /**
     * Format teacher type for display (full label).
     */
    protected static function formatTeacherType(?string $type): string
    {
        return match ($type) {
            QuranTeacherProfile::class => 'معلم قرآن',
            AcademicTeacherProfile::class => 'معلم أكاديمي',
            default => $type ?? '-',
        };
    }

    /**
     * Format teacher type for table display (short label).
     */
    protected static function formatTeacherTypeShort(?string $type): string
    {
        return match ($type) {
            QuranTeacherProfile::class => 'قرآن',
            AcademicTeacherProfile::class => 'أكاديمي',
            default => $type ?? '-',
        };
    }

    /**
     * Get color for teacher type badge.
     */
    protected static function getTeacherTypeColor(?string $type): string
    {
        return match ($type) {
            QuranTeacherProfile::class => 'success',
            AcademicTeacherProfile::class => 'info',
            default => 'gray',
        };
    }

    // ========================================
    // Eloquent Query
    // ========================================

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['teacher.user']);

        return static::scopeEloquentQuery($query);
    }

    public static function getRelations(): array
    {
        return [];
    }
}
