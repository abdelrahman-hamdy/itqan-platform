<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
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
                            ->default(fn () => 'PAY-' . uniqid())
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(2),

                Forms\Components\Section::make('تفاصيل المبلغ')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('المبلغ')
                            ->required()
                            ->numeric()
                            ->prefix('ر.س')
                            ->live(onBlur: true),

                        Forms\Components\TextInput::make('currency')
                            ->label('العملة')
                            ->required()
                            ->maxLength(3)
                            ->default('SAR'),

                        Forms\Components\TextInput::make('discount_amount')
                            ->label('مبلغ الخصم')
                            ->numeric()
                            ->prefix('ر.س')
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
                            ->prefix('ر.س')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('fees')
                            ->label('الرسوم')
                            ->numeric()
                            ->prefix('ر.س')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('net_amount')
                            ->label('المبلغ الصافي')
                            ->numeric()
                            ->prefix('ر.س')
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(2),

                Forms\Components\Section::make('طريقة الدفع')
                    ->schema([
                        Forms\Components\Select::make('payment_method')
                            ->label('طريقة الدفع')
                            ->required()
                            ->options([
                                'credit_card' => 'بطاقة ائتمان',
                                'debit_card' => 'بطاقة خصم',
                                'bank_transfer' => 'تحويل بنكي',
                                'wallet' => 'محفظة إلكترونية',
                                'cash' => 'نقداً',
                                'mada' => 'مدى',
                                'visa' => 'فيزا',
                                'mastercard' => 'ماستركارد',
                                'apple_pay' => 'Apple Pay',
                                'stc_pay' => 'STC Pay',
                            ])
                            ->searchable(),

                        Forms\Components\Select::make('payment_gateway')
                            ->label('بوابة الدفع')
                            ->options([
                                'moyasar' => 'Moyasar',
                                'tap' => 'Tap Payments',
                                'payfort' => 'Payfort',
                                'hyperpay' => 'HyperPay',
                                'paytabs' => 'PayTabs',
                                'manual' => 'يدوي',
                            ])
                            ->searchable(),

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
                            ->label('الحالة')
                            ->required()
                            ->options([
                                'pending' => 'في الانتظار',
                                'processing' => 'قيد المعالجة',
                                'completed' => 'مكتمل',
                                'failed' => 'فشل',
                                'cancelled' => 'ملغي',
                                'refunded' => 'مسترد',
                                'partially_refunded' => 'مسترد جزئياً',
                            ])
                            ->default('pending'),

                        Forms\Components\Select::make('payment_status')
                            ->label('حالة الدفع')
                            ->required()
                            ->options([
                                'pending' => 'في الانتظار',
                                'processing' => 'قيد المعالجة',
                                'paid' => 'مدفوع',
                                'failed' => 'فشل',
                                'cancelled' => 'ملغي',
                            ])
                            ->default('pending'),

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

                Forms\Components\Section::make('معلومات الاسترداد')
                    ->schema([
                        Forms\Components\TextInput::make('refund_amount')
                            ->label('مبلغ الاسترداد')
                            ->numeric()
                            ->prefix('ر.س'),

                        Forms\Components\Textarea::make('refund_reason')
                            ->label('سبب الاسترداد')
                            ->rows(3),

                        Forms\Components\TextInput::make('refund_reference')
                            ->label('مرجع الاسترداد')
                            ->maxLength(255),

                        Forms\Components\DateTimePicker::make('refunded_at')
                            ->label('تاريخ الاسترداد'),
                    ])->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => $record?->is_refunded ?? false),

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
                    ->money('SAR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('طريقة الدفع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'credit_card' => 'بطاقة ائتمان',
                        'debit_card' => 'بطاقة خصم',
                        'bank_transfer' => 'تحويل بنكي',
                        'mada' => 'مدى',
                        'stc_pay' => 'STC Pay',
                        'cash' => 'نقداً',
                        default => $state
                    })
                    ->color(fn ($state) => match($state) {
                        'credit_card', 'debit_card', 'mada' => 'info',
                        'bank_transfer' => 'warning',
                        'cash' => 'success',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'في الانتظار',
                        'processing' => 'قيد المعالجة',
                        'completed' => 'مكتمل',
                        'failed' => 'فشل',
                        'cancelled' => 'ملغي',
                        'refunded' => 'مسترد',
                        'partially_refunded' => 'مسترد جزئياً',
                        default => $state
                    })
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'failed', 'cancelled' => 'danger',
                        'refunded', 'partially_refunded' => 'gray',
                        default => 'gray'
                    }),

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
                    ->label('الحالة')
                    ->options([
                        'pending' => 'في الانتظار',
                        'processing' => 'قيد المعالجة',
                        'completed' => 'مكتمل',
                        'failed' => 'فشل',
                        'cancelled' => 'ملغي',
                        'refunded' => 'مسترد',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('طريقة الدفع')
                    ->options([
                        'credit_card' => 'بطاقة ائتمان',
                        'debit_card' => 'بطاقة خصم',
                        'bank_transfer' => 'تحويل بنكي',
                        'mada' => 'مدى',
                        'cash' => 'نقداً',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('academy_id')
                    ->label('الأكاديمية')
                    ->relationship('academy', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('payment_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('until')
                            ->label('إلى تاريخ'),
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
                    }),

                Tables\Filters\TrashedFilter::make()
                    ->label('المحذوفة'),
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
                    ->visible(fn (Payment $record) => $record->status === 'pending')
                    ->action(function (Payment $record) {
                        $record->markAsCompleted();
                        Notification::make()
                            ->success()
                            ->title('تم تأكيد الدفع')
                            ->body('تم تأكيد الدفع وتفعيل الاشتراك')
                            ->send();
                    }),

                Tables\Actions\Action::make('refund')
                    ->label('استرداد')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (Payment $record) => $record->can_refund)
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('مبلغ الاسترداد')
                            ->required()
                            ->numeric()
                            ->prefix('ر.س')
                            ->default(fn (Payment $record) => $record->refundable_amount),
                        Forms\Components\Textarea::make('reason')
                            ->label('سبب الاسترداد')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Payment $record, array $data) {
                        try {
                            $record->processRefund($data['amount'], $data['reason']);
                            Notification::make()
                                ->success()
                                ->title('تم الاسترداد')
                                ->body('تم استرداد المبلغ بنجاح')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('خطأ في الاسترداد')
                                ->body($e->getMessage())
                                ->send();
                        }
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
        // Only the index page (Coming Soon) is active until the feature is built
        return [
            'index' => Pages\ListPayments::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
