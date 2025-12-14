<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\TeacherEarningsResource\Pages;
use App\Models\TeacherEarning;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Teacher Earnings Resource for Teacher Panel
 *
 * Allows teachers to view their earnings (read-only).
 */
class TeacherEarningsResource extends BaseTeacherResource
{
    protected static ?string $model = TeacherEarning::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'أرباحي';

    protected static ?string $modelLabel = 'أرباح';

    protected static ?string $pluralModelLabel = 'أرباحي';

    protected static ?string $navigationGroup = 'المالية';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('تفاصيل الأرباح')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('المبلغ')
                            ->disabled(),
                        Forms\Components\TextInput::make('calculation_method')
                            ->label('طريقة الحساب')
                            ->disabled(),
                        Forms\Components\DatePicker::make('earning_month')
                            ->label('شهر الأرباح')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('session_completed_at')
                            ->label('تاريخ الجلسة')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('earning_month')
                    ->label('الشهر')
                    ->date('Y-m')
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('SAR')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('SAR')
                            ->label('الإجمالي'),
                    ]),

                TextColumn::make('calculation_method')
                    ->label('طريقة الحساب')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'individual_rate' => 'جلسة فردية',
                        'group_rate' => 'جلسة جماعية',
                        'per_session' => 'حسب الجلسة',
                        'per_student' => 'حسب الطالب',
                        'fixed' => 'مبلغ ثابت',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'individual_rate' => 'primary',
                        'group_rate' => 'success',
                        'per_session' => 'info',
                        'per_student' => 'warning',
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
            ])
            ->defaultSort('earning_month', 'desc')
            ->filters([
                Tables\Filters\Filter::make('current_month')
                    ->label('الشهر الحالي')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('earning_month', now()->month)
                        ->whereYear('earning_month', now()->year)),

                Tables\Filters\Filter::make('last_month')
                    ->label('الشهر الماضي')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('earning_month', now()->subMonth()->month)
                        ->whereYear('earning_month', now()->subMonth()->year)),

                Tables\Filters\SelectFilter::make('is_finalized')
                    ->label('حالة التأكيد')
                    ->options([
                        '1' => 'مؤكد',
                        '0' => 'قيد المراجعة',
                    ]),

                Tables\Filters\SelectFilter::make('calculation_method')
                    ->label('طريقة الحساب')
                    ->options([
                        'individual_rate' => 'جلسة فردية',
                        'group_rate' => 'جلسة جماعية',
                        'per_session' => 'حسب الجلسة',
                        'per_student' => 'حسب الطالب',
                        'fixed' => 'مبلغ ثابت',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
            ])
            ->bulkActions([
                // No bulk actions for teachers
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الأرباح')
                    ->schema([
                        Infolists\Components\TextEntry::make('amount')
                            ->label('المبلغ')
                            ->money('SAR'),
                        Infolists\Components\TextEntry::make('calculation_method')
                            ->label('طريقة الحساب')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'individual_rate' => 'جلسة فردية',
                                'group_rate' => 'جلسة جماعية',
                                'per_session' => 'حسب الجلسة',
                                'per_student' => 'حسب الطالب',
                                'fixed' => 'مبلغ ثابت',
                                default => $state,
                            }),
                        Infolists\Components\TextEntry::make('earning_month')
                            ->label('شهر الأرباح')
                            ->date('Y-m'),
                        Infolists\Components\TextEntry::make('session_completed_at')
                            ->label('تاريخ الجلسة')
                            ->dateTime('Y-m-d H:i'),
                    ])->columns(2),

                Infolists\Components\Section::make('حالة الدفع')
                    ->schema([
                        Infolists\Components\IconEntry::make('is_finalized')
                            ->label('مؤكد')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('is_disputed')
                            ->label('متنازع')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('payout.reference_number')
                            ->label('رقم الدفعة')
                            ->placeholder('لم تصرف بعد'),
                        Infolists\Components\TextEntry::make('dispute_notes')
                            ->label('ملاحظات النزاع')
                            ->visible(fn ($record) => $record->is_disputed),
                    ])->columns(2),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();

        // Filter earnings for the current teacher
        if ($user->quranTeacherProfile) {
            $query->where('teacher_type', 'App\\Models\\QuranTeacherProfile')
                  ->where('teacher_id', $user->quranTeacherProfile->id);
        }

        return $query->with(['payout', 'session']);
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
        ];
    }
}
