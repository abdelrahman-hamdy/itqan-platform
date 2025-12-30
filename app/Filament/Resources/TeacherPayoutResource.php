<?php

namespace App\Filament\Resources;

use App\Enums\PayoutStatus;
use App\Filament\Resources\TeacherPayoutResource\Pages;
use App\Models\TeacherPayout;
use App\Models\QuranTeacherProfile;
use App\Models\AcademicTeacherProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TeacherPayoutResource extends BaseResource
{
    protected static ?string $model = TeacherPayout::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'مدفوعات المعلمين';

    protected static ?string $modelLabel = 'دفعة معلم';

    protected static ?string $pluralModelLabel = 'مدفوعات المعلمين';

    protected static ?string $navigationGroup = 'إعدادات المعلمين';

    protected static ?int $navigationSort = 3;

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
     * Get the navigation badge showing pending payouts count
     */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::pending()->count();
        return $count > 0 ? (string) $count : null;
    }

    /**
     * Get the navigation badge color
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الدفعة')
                    ->schema([
                        Forms\Components\TextInput::make('payout_code')
                            ->label('رقم الدفعة')
                            ->disabled(),

                        Forms\Components\TextInput::make('teacher_type')
                            ->label('نوع المعلم')
                            ->formatStateUsing(fn ($state) => match($state) {
                                QuranTeacherProfile::class => 'معلم قرآن',
                                AcademicTeacherProfile::class => 'معلم أكاديمي',
                                default => $state,
                            })
                            ->disabled(),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('المبلغ الإجمالي')
                            ->numeric()
                            ->prefix('ر.س')
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
                        Forms\Components\Select::make('approved_by')
                            ->label('تمت الموافقة بواسطة')
                            ->relationship('approver', 'name')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('تاريخ الموافقة')
                            ->disabled(),

                        Forms\Components\Textarea::make('approval_notes')
                            ->label('ملاحظات الموافقة')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record?->approved_at !== null),

                Forms\Components\Section::make('تفاصيل الدفع')
                    ->schema([
                        Forms\Components\Select::make('paid_by')
                            ->label('تم الدفع بواسطة')
                            ->relationship('payer', 'name')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('تاريخ الدفع')
                            ->disabled(),

                        Forms\Components\TextInput::make('payment_method')
                            ->label('طريقة الدفع')
                            ->disabled(),

                        Forms\Components\TextInput::make('payment_reference')
                            ->label('رقم المرجع')
                            ->disabled(),

                        Forms\Components\Textarea::make('payment_notes')
                            ->label('ملاحظات الدفع')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record?->paid_at !== null),

                Forms\Components\Section::make('تفاصيل الرفض')
                    ->schema([
                        Forms\Components\Select::make('rejected_by')
                            ->label('تم الرفض بواسطة')
                            ->relationship('rejector', 'name')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('rejected_at')
                            ->label('تاريخ الرفض')
                            ->disabled(),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('سبب الرفض')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record?->rejected_at !== null),

                Forms\Components\Section::make('تفاصيل الأرباح')
                    ->schema([
                        Forms\Components\KeyValue::make('breakdown')
                            ->label('تفصيل الأرباح')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getAcademyColumn(),

                Tables\Columns\TextColumn::make('payout_code')
                    ->label('رقم الدفعة')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

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

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('المبلغ')
                    ->money('SAR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sessions_count')
                    ->label('الجلسات')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payout_month')
                    ->label('الشهر')
                    ->formatStateUsing(fn ($record) => $record->month_name)
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color())
                    ->icon(fn ($state) => $state->icon()),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
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
                    }),

                Tables\Actions\Action::make('mark_paid')
                    ->label('تم الدفع')
                    ->icon('heroicon-o-banknotes')
                    ->color('info')
                    ->visible(fn ($record) => $record->canMarkPaid())
                    ->form([
                        Forms\Components\TextInput::make('payment_method')
                            ->label('طريقة الدفع')
                            ->required(),
                        Forms\Components\TextInput::make('payment_reference')
                            ->label('رقم المرجع'),
                        Forms\Components\Textarea::make('payment_notes')
                            ->label('ملاحظات الدفع')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => PayoutStatus::PAID,
                            'paid_by' => auth()->id(),
                            'paid_at' => now(),
                            'payment_method' => $data['payment_method'],
                            'payment_reference' => $data['payment_reference'] ?? null,
                            'payment_notes' => $data['payment_notes'] ?? null,
                        ]);

                        // Mark all related earnings as finalized
                        $record->earnings()->update(['is_finalized' => true]);
                    }),

                Tables\Actions\Action::make('reject')
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
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->label(__('filament.actions.restore')),
                Tables\Actions\ForceDeleteAction::make()
                    ->label(__('filament.actions.force_delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_selected')
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
            'index' => Pages\ListTeacherPayouts::route('/'),
            'view' => Pages\ViewTeacherPayout::route('/{record}'),
        ];
    }
}
