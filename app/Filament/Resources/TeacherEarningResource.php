<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherEarningResource\Pages;
use App\Models\TeacherEarning;
use App\Models\QuranTeacherProfile;
use App\Models\AcademicTeacherProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TeacherEarningResource extends BaseResource
{
    protected static ?string $model = TeacherEarning::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'أرباح المعلمين';

    protected static ?string $modelLabel = 'ربح معلم';

    protected static ?string $pluralModelLabel = 'أرباح المعلمين';

    protected static ?string $navigationGroup = 'إعدادات المعلمين';

    protected static ?int $navigationSort = 2;

    /**
     * Get the Eloquent query with soft deletes included
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * Get the navigation badge showing unpaid earnings count
     */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::unpaid()->count();
        return $count > 0 ? (string) $count : null;
    }

    /**
     * Get the navigation badge color
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الربح')
                    ->schema([
                        Forms\Components\TextInput::make('teacher_type')
                            ->label('نوع المعلم')
                            ->formatStateUsing(fn ($state) => match($state) {
                                QuranTeacherProfile::class => 'معلم قرآن',
                                AcademicTeacherProfile::class => 'معلم أكاديمي',
                                default => $state,
                            })
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

                                    // Translate session_type values
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
                    ->schema([
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
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getAcademyColumn(),

                Tables\Columns\TextColumn::make('teacher.user.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('teacher_type')
                    ->label('نوع المعلم')
                    ->formatStateUsing(fn ($state) => match($state) {
                        QuranTeacherProfile::class => 'قرآن',
                        AcademicTeacherProfile::class => 'أكاديمي',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        QuranTeacherProfile::class => 'success',
                        AcademicTeacherProfile::class => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('SAR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('calculation_method')
                    ->label('طريقة الحساب')
                    ->formatStateUsing(fn ($record) => $record->calculation_method_label)
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('earning_month')
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

                Tables\Columns\TextColumn::make('payout.payout_code')
                    ->label('رقم الصرف')
                    ->placeholder('غير مصروف')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
                    ->query(fn (Builder $query) => $query->whereNull('payout_id')
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
            ])
            ->actions([
                Tables\Actions\Action::make('finalize')
                    ->label('تأكيد')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_finalized && !$record->is_disputed)
                    ->action(fn ($record) => $record->update(['is_finalized' => true])),

                Tables\Actions\Action::make('dispute')
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
                    ])),

                Tables\Actions\Action::make('resolve_dispute')
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
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->label(__('filament.actions.restore')),
                Tables\Actions\ForceDeleteAction::make()
                    ->label(__('filament.actions.force_delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('finalize_selected')
                        ->label('تأكيد المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_finalized' => true]));
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label(__('filament.actions.restore_selected')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label(__('filament.actions.force_delete_selected')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeacherEarnings::route('/'),
            'view' => Pages\ViewTeacherEarning::route('/{record}'),
            'edit' => Pages\EditTeacherEarning::route('/{record}/edit'),
        ];
    }
}
