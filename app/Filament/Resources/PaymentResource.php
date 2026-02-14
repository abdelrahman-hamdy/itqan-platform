<?php

namespace App\Filament\Resources;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'المدفوعات';

    protected static ?string $modelLabel = 'دفعة';

    protected static ?string $pluralModelLabel = 'المدفوعات';

    protected static ?string $navigationGroup = 'المالية';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الدفعة')
                    ->schema([
                        Forms\Components\Select::make('academy_id')
                            ->relationship('academy', 'name')
                            ->label('الأكاديمية')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('المستخدم')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('subscription_id')
                            ->relationship('subscription', 'id')
                            ->label('الاشتراك')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('payment_code')
                            ->label('رمز الدفعة')
                            ->maxLength(255)
                            ->default(fn () => 'PAY-'.uniqid())
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(2),

                Forms\Components\Section::make('تفاصيل المبلغ')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('المبلغ')
                            ->required()
                            ->numeric()
                            ->prefix(getCurrencySymbol())
                            ->live(onBlur: true),

                        Forms\Components\TextInput::make('currency')
                            ->label('العملة')
                            ->required()
                            ->maxLength(3)
                            ->default(getCurrencyCode()),

                        Forms\Components\TextInput::make('discount_amount')
                            ->label('مبلغ الخصم')
                            ->numeric()
                            ->prefix(getCurrencySymbol())
                            ->default(0),

                        Forms\Components\TextInput::make('discount_code')
                            ->label('كود الخصم')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('tax_percentage')
                            ->label('نسبة الضريبة (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(15),

                        Forms\Components\TextInput::make('tax_amount')
                            ->label('مبلغ الضريبة')
                            ->numeric()
                            ->prefix(getCurrencySymbol())
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('fees')
                            ->label('الرسوم')
                            ->numeric()
                            ->prefix(getCurrencySymbol())
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('net_amount')
                            ->label('المبلغ الصافي')
                            ->numeric()
                            ->prefix(getCurrencySymbol())
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(2),

                Forms\Components\Section::make('طريقة الدفع')
                    ->schema([
                        Forms\Components\Select::make('payment_method')
                            ->label(__('filament.payment_method'))
                            ->required()
                            ->options(PaymentMethod::options())
                            ->searchable(),

                        Forms\Components\Select::make('payment_gateway')
                            ->label('بوابة الدفع')
                            ->options([
                                'paymob' => 'Paymob',
                                'easykash' => 'EasyKash',
                                'moyasar' => 'Moyasar',
                                'tap' => 'Tap Payments',
                                'payfort' => 'Payfort',
                                'hyperpay' => 'HyperPay',
                                'paytabs' => 'PayTabs',
                                'manual' => 'يدوي',
                            ])
                            ->searchable()
                            ->default('paymob'),

                        Forms\Components\Select::make('payment_type')
                            ->label('نوع الدفعة')
                            ->required()
                            ->options([
                                'subscription' => 'اشتراك',
                                'course' => 'كورس',
                                'session' => 'جلسة',
                                'service' => 'خدمة',
                                'other' => 'أخرى',
                            ]),
                    ])->columns(3),

                Forms\Components\Section::make('حالة الدفع')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label(__('filament.status'))
                            ->required()
                            ->options(PaymentStatus::options())
                            ->default(PaymentStatus::PENDING->value),

                        Forms\Components\Select::make('payment_status')
                            ->label(__('filament.payment_status'))
                            ->required()
                            ->options(PaymentStatus::options())
                            ->default(PaymentStatus::PENDING->value),

                        Forms\Components\DateTimePicker::make('payment_date')
                            ->label('تاريخ الدفع')
                            ->default(now()),
                    ])->columns(3),

                Forms\Components\Section::make('معلومات البوابة')
                    ->schema([
                        Forms\Components\TextInput::make('gateway_transaction_id')
                            ->label('معرف المعاملة')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('gateway_payment_id')
                            ->label('معرف الدفع')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('gateway_status')
                            ->label('حالة البوابة')
                            ->maxLength(255),
                    ])->columns(3)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('الإيصال')
                    ->schema([
                        Forms\Components\TextInput::make('receipt_number')
                            ->label('رقم الإيصال')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('receipt_url')
                            ->label('رابط الإيصال')
                            ->url()
                            ->maxLength(255),
                    ])->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('ملاحظات إضافية')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3),

                        Forms\Components\Textarea::make('failure_reason')
                            ->label('سبب الفشل')
                            ->rows(3)
                            ->visible(fn ($record) => $record?->is_failed ?? false),
                    ])->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_code')
                    ->label('رمز الدفعة')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money(fn ($record) => $record->currency ?? config('currencies.default', 'SAR'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label(__('filament.payment_method'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof PaymentMethod ? $state->label() : (PaymentMethod::tryFrom($state)?->label() ?? $state))
                    ->color(fn ($state) => $state instanceof PaymentMethod ? $state->color() : (PaymentMethod::tryFrom($state)?->color() ?? 'gray')),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('filament.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof PaymentStatus ? $state->label() : (PaymentStatus::tryFrom($state)?->label() ?? $state))
                    ->color(fn ($state) => $state instanceof PaymentStatus ? $state->color() : (PaymentStatus::tryFrom($state)?->color() ?? 'gray')),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('تاريخ الدفع')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('receipt_number')
                    ->label('رقم الإيصال')
                    ->searchable()
                    ->toggleable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament.status'))
                    ->options(PaymentStatus::options())
                    ->multiple(),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label(__('filament.payment_method'))
                    ->options(PaymentMethod::options())
                    ->multiple(),

                Tables\Filters\SelectFilter::make('academy_id')
                    ->label(__('filament.academy'))
                    ->relationship('academy', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('payment_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('filament.filters.from_date')),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('filament.filters.to_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = __('filament.filters.from_date').': '.$data['from'];
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = __('filament.filters.to_date').': '.$data['until'];
                        }

                        return $indicators;
                    }),

                Tables\Filters\TrashedFilter::make()
                    ->label(__('filament.filters.trashed')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),

                Tables\Actions\EditAction::make()
                    ->label('تعديل'),

                Tables\Actions\Action::make('mark_completed')
                    ->label('تأكيد الدفع')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Payment $record) => $record->status === PaymentStatus::PENDING->value)
                    ->action(function (Payment $record) {
                        $record->markAsCompleted();
                        Notification::make()
                            ->success()
                            ->title('تم تأكيد الدفع')
                            ->body('تم تأكيد الدفع وتفعيل الاشتراك')
                            ->send();
                    }),

                Tables\Actions\Action::make('generate_receipt')
                    ->label('إنشاء إيصال')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->visible(fn (Payment $record) => $record->is_successful)
                    ->action(function (Payment $record) {
                        $receiptUrl = $record->generateReceipt();
                        Notification::make()
                            ->success()
                            ->title('تم إنشاء الإيصال')
                            ->body('تم إنشاء الإيصال بنجاح')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label('استعادة المحدد'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'academy'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
