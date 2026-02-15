<?php

namespace App\Filament\Resources;

use App\Enums\PaymentStatus;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Services\Payment\InvoiceService;
use Filament\Forms;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                            ->label('المستخدم')
                            ->relationship(
                                'user',
                                'first_name',
                                modifyQueryUsing: fn (Builder $query) => $query->withoutGlobalScopes(),
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                            ->searchable(['first_name', 'last_name', 'email'])
                            ->required()
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

                Forms\Components\Section::make('حالة الدفع')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label(__('filament.status'))
                            ->required()
                            ->options(PaymentStatus::options())
                            ->default(PaymentStatus::PENDING->value),

                        Forms\Components\TextInput::make('payment_gateway')
                            ->label('بوابة الدفع')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('payment_method')
                            ->label('طريقة الدفع')
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(3),

                Forms\Components\Section::make('معلومات البوابة')
                    ->schema([
                        Forms\Components\TextInput::make('gateway_transaction_id')
                            ->label('معرف المعاملة')
                            ->maxLength(255)
                            ->disabled(),

                        Forms\Components\TextInput::make('receipt_number')
                            ->label('رقم الإيصال')
                            ->maxLength(255)
                            ->disabled(),
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

                Tables\Columns\TextColumn::make('user.first_name')
                    ->label('المستخدم')
                    ->formatStateUsing(fn ($record) => $record->user?->name ?? 'مستخدم غير محدد')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('user', function (Builder $q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                    })
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

                Tables\Columns\TextColumn::make('payment_gateway')
                    ->label('بوابة الدفع')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('filament.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof PaymentStatus ? $state->label() : (PaymentStatus::tryFrom($state)?->label() ?? $state))
                    ->color(fn ($state) => $state instanceof PaymentStatus ? $state->color() : (PaymentStatus::tryFrom($state)?->color() ?? 'gray')),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('تاريخ الدفع')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->placeholder('-'),

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

                Tables\Filters\SelectFilter::make('academy_id')
                    ->label(__('filament.academy'))
                    ->relationship('academy', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('paid_at')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('paid_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('paid_at', '<=', $date),
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
                    ->visible(fn (Payment $record) => $record->status === PaymentStatus::PENDING)
                    ->action(function (Payment $record) {
                        $record->markAsCompleted();
                        Notification::make()
                            ->success()
                            ->title('تم تأكيد الدفع')
                            ->body('تم تأكيد الدفع وتفعيل الاشتراك')
                            ->send();
                    }),

                Tables\Actions\Action::make('generate_invoice')
                    ->label('إنشاء فاتورة')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->visible(fn (Payment $record) => $record->is_successful && empty($record->metadata['invoice_number']))
                    ->action(function (Payment $record) {
                        try {
                            $invoiceService = app(InvoiceService::class);
                            $result = $invoiceService->generateInvoiceWithPdf($record);
                            $invoiceNumber = $result['invoice']->invoiceNumber;
                            Notification::make()
                                ->success()
                                ->title('تم إنشاء الفاتورة')
                                ->body("رقم الفاتورة: {$invoiceNumber}")
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('خطأ في إنشاء الفاتورة')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('download_invoice')
                    ->label('تحميل الفاتورة')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (Payment $record) => ! empty($record->metadata['invoice_pdf_path']))
                    ->action(function (Payment $record) {
                        $path = $record->metadata['invoice_pdf_path'];
                        if (Storage::disk('local')->exists($path)) {
                            return Storage::disk('local')->download($path, 'invoice-'.$record->payment_code.'.pdf');
                        }

                        // PDF missing from disk, regenerate
                        try {
                            $invoiceService = app(InvoiceService::class);
                            $pdfPath = $invoiceService->getOrGeneratePdf($record);
                            if ($pdfPath && Storage::disk('local')->exists($pdfPath)) {
                                return Storage::disk('local')->download($pdfPath, 'invoice-'.$record->payment_code.'.pdf');
                            }
                        } catch (\Exception $e) {
                            // Fall through to error
                        }

                        Notification::make()
                            ->danger()
                            ->title('الفاتورة غير متوفرة')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
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
            ->with(['user', 'academy']);
    }
}
