<?php

namespace App\Filament\Shared\Resources;

use App\Models\TeacherEarning;
use App\Models\QuranTeacherProfile;
use App\Models\AcademicTeacherProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Teacher Earning Resource
 *
 * Shared functionality for SuperAdmin and Supervisor panels.
 * Child classes must implement query scoping and authorization methods.
 *
 * Pattern: Shared form/table definitions with abstract methods for panel-specific behavior.
 */
abstract class BaseTeacherEarningResource extends Resource
{
    protected static ?string $model = TeacherEarning::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'أرباح المعلمين';

    protected static ?string $modelLabel = 'ربح معلم';

    protected static ?string $pluralModelLabel = 'أرباح المعلمين';

    // ========================================
    // Abstract Methods - Panel-specific implementation
    // ========================================

    /**
     * Apply panel-specific query scoping.
     * SuperAdmin: may include all earnings or filter by academy
     * Supervisor: filters by assigned teacher profiles
     */
    abstract protected static function scopeEloquentQuery(Builder $query): Builder;

    /**
     * Get panel-specific table actions.
     * SuperAdmin: view, edit, delete, restore actions + finalize/dispute
     * Supervisor: view + finalize/dispute actions only
     */
    abstract protected static function getTableActions(): array;

    /**
     * Get panel-specific bulk actions.
     * SuperAdmin: finalize, delete, restore bulk actions
     * Supervisor: finalize bulk action only
     */
    abstract protected static function getTableBulkActions(): array;

    // ========================================
    // Authorization - Override in child classes
    // ========================================

    /**
     * Can create new earnings.
     * Default: false - earnings are auto-generated from sessions.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Can edit earnings.
     * Default: false - override in child class (e.g., SuperAdmin).
     */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /**
     * Can delete earnings.
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
                Forms\Components\Section::make('معلومات الربح')
                    ->schema([
                        Forms\Components\TextInput::make('teacher.user.name')
                            ->label('المعلم')
                            ->disabled(),

                        Forms\Components\TextInput::make('teacher_type')
                            ->label('نوع المعلم')
                            ->formatStateUsing(fn ($state) => static::formatTeacherType($state))
                            ->disabled(),

                        Forms\Components\TextInput::make('amount')
                            ->label('المبلغ')
                            ->numeric()
                            ->prefix('ر.س')
                            ->disabled(),

                        Forms\Components\TextInput::make('calculation_method')
                            ->label('طريقة الحساب')
                            ->formatStateUsing(fn ($record) => $record->calculation_method_label ?? '-')
                            ->disabled(),

                        Forms\Components\DatePicker::make('earning_month')
                            ->label('شهر الربح')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('session_completed_at')
                            ->label('تاريخ إكمال الجلسة')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('calculated_at')
                            ->label('تاريخ الحساب')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('تفاصيل الحساب')
                    ->schema([
                        Forms\Components\Placeholder::make('rate_snapshot_display')
                            ->label('الأسعار المستخدمة')
                            ->content(function ($record) {
                                if (empty($record?->rate_snapshot)) {
                                    return '-';
                                }

                                $labels = [
                                    'individual_rate' => 'سعر الجلسة الفردية',
                                    'group_rate' => 'سعر الجلسة الجماعية',
                                    'per_session' => 'سعر الجلسة',
                                    'per_student' => 'سعر لكل طالب',
                                    'hourly_rate' => 'سعر الساعة',
                                ];

                                $lines = [];
                                foreach ($record->rate_snapshot as $key => $value) {
                                    $label = $labels[$key] ?? $key;
                                    $lines[] = "{$label}: " . number_format($value, 2) . ' ر.س';
                                }

                                return implode(' | ', $lines) ?: '-';
                            }),

                        Forms\Components\Placeholder::make('calculation_metadata_display')
                            ->label('تفاصيل الجلسة')
                            ->content(function ($record) {
                                if (empty($record?->calculation_metadata)) {
                                    return '-';
                                }

                                $labels = [
                                    'session_type' => 'نوع الجلسة',
                                    'duration_minutes' => 'المدة (دقيقة)',
                                    'subject' => 'المادة',
                                    'students_count' => 'عدد الطلاب',
                                    'circle_type' => 'نوع الحلقة',
                                ];

                                $sessionTypes = [
                                    'individual' => 'فردية',
                                    'group' => 'جماعية',
                                    'trial' => 'تجريبية',
                                    'circle' => 'حلقة',
                                ];

                                $lines = [];
                                foreach ($record->calculation_metadata as $key => $value) {
                                    $label = $labels[$key] ?? $key;

                                    if ($key === 'session_type' && isset($sessionTypes[$value])) {
                                        $value = $sessionTypes[$value];
                                    }

                                    $lines[] = "{$label}: {$value}";
                                }

                                return implode(' | ', $lines) ?: '-';
                            }),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('الحالة')
                    ->schema(static::getStatusFormFields())
                    ->columns(2),
            ]);
    }

    /**
     * Get status form fields - can be overridden for editable vs read-only.
     */
    protected static function getStatusFormFields(): array
    {
        return [
            Forms\Components\Toggle::make('is_finalized')
                ->label('تم التأكيد')
                ->helperText('هل تم تأكيد هذا الربح؟')
                ->disabled(),

            Forms\Components\Toggle::make('is_disputed')
                ->label('معترض عليه')
                ->helperText('هل يوجد اعتراض على هذا الربح؟')
                ->disabled(),

            Forms\Components\Textarea::make('dispute_notes')
                ->label('ملاحظات الاعتراض')
                ->rows(3)
                ->disabled()
                ->visible(fn ($get) => $get('is_disputed')),
        ];
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
                ->money('SAR')
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

    /**
     * Get the table filters - shared across panels.
     */
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
                ->query(fn (Builder $query) => $query->whereNull('payout_id')),
        ];
    }

    // ========================================
    // Common Table Actions (can be used by child classes)
    // ========================================

    /**
     * Get finalize action for earnings.
     */
    protected static function getFinalizeAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('finalize')
            ->label('تأكيد')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn ($record) => !$record->is_finalized && !$record->is_disputed)
            ->requiresConfirmation()
            ->modalHeading('تأكيد الربح')
            ->modalDescription('هل أنت متأكد من تأكيد هذا الربح؟')
            ->action(fn ($record) => $record->update(['is_finalized' => true]));
    }

    /**
     * Get dispute action for earnings.
     */
    protected static function getDisputeAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('dispute')
            ->label('اعتراض')
            ->icon('heroicon-o-exclamation-triangle')
            ->color('warning')
            ->visible(fn ($record) => !$record->is_disputed)
            ->form([
                Forms\Components\Textarea::make('dispute_notes')
                    ->label('سبب الاعتراض')
                    ->required()
                    ->maxLength(1000)
                    ->rows(3),
            ])
            ->action(fn ($record, array $data) => $record->update([
                'is_disputed' => true,
                'dispute_notes' => $data['dispute_notes'],
            ]));
    }

    /**
     * Get resolve dispute action for earnings.
     */
    protected static function getResolveDisputeAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('resolve_dispute')
            ->label('حل الاعتراض')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->visible(fn ($record) => $record->is_disputed)
            ->requiresConfirmation()
            ->modalHeading('حل الاعتراض')
            ->modalDescription('هل أنت متأكد من حل هذا الاعتراض وتأكيد الربح؟')
            ->modalSubmitActionLabel('نعم، حل الاعتراض')
            ->form([
                Forms\Components\Placeholder::make('current_dispute_notes')
                    ->label('سبب الاعتراض الحالي')
                    ->content(fn ($record) => $record->dispute_notes ?? '-'),
                Forms\Components\Textarea::make('resolution_notes')
                    ->label('ملاحظات الحل')
                    ->helperText('أضف ملاحظات توضح كيف تم حل الاعتراض (الحد الأقصى 500 حرف)')
                    ->maxLength(500)
                    ->rows(3),
            ])
            ->action(function ($record, array $data) {
                $resolutionNote = $data['resolution_notes'] ?? '';
                $previousNotes = $record->dispute_notes ?? '';

                // Append resolution notes to dispute_notes for audit trail
                $updatedNotes = $previousNotes;
                if ($resolutionNote) {
                    $updatedNotes .= "\n\n--- تم الحل بتاريخ " . now()->format('Y-m-d H:i') . " ---\n" . $resolutionNote;
                }

                // Truncate to prevent database overflow (max 2000 characters)
                $updatedNotes = mb_substr($updatedNotes, 0, 2000);

                $record->update([
                    'is_disputed' => false,
                    'is_finalized' => true,
                    'dispute_notes' => $updatedNotes,
                ]);
            });
    }

    /**
     * Get finalize bulk action.
     */
    protected static function getFinalizeBulkAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('finalize_selected')
            ->label('تأكيد المحدد')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->action(function ($records) {
                $records->each(fn ($record) => $record->update(['is_finalized' => true]));
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
            ->with(['teacher.user', 'session', 'payout']);

        return static::scopeEloquentQuery($query);
    }

    public static function getRelations(): array
    {
        return [];
    }
}
