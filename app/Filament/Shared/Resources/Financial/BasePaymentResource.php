<?php

namespace App\Filament\Shared\Resources\Financial;

use App\Enums\PaymentStatus;
use App\Filament\Resources\BaseResource;
use App\Models\Payment;
use App\Services\Payment\InvoiceService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

abstract class BasePaymentResource extends BaseResource
{
    protected static ?string $model = Payment::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $modelLabel = 'دفعة';
    protected static ?string $pluralModelLabel = 'المدفوعات';

    // Abstract methods for panel-specific implementation
    abstract protected static function scopeEloquentQuery(Builder $query): Builder;
    abstract protected static function getTableActions(): array;
    abstract protected static function getTableBulkActions(): array;
    abstract protected static function getAcademyFormField(): ?Forms\Components\Select;

    // Shared form sections
    protected static function getPaymentInfoSection(): Forms\Components\Section
    {
        $schema = [];

        // Add academy field if provided (SuperAdmin only)
        $academyField = static::getAcademyFormField();
        if ($academyField) {
            $schema[] = $academyField;
        }

        $schema = array_merge($schema, [
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
        ]);

        return Forms\Components\Section::make('معلومات الدفعة')
            ->schema($schema)
            ->columns(2);
    }

    protected static function getAmountDetailsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('تفاصيل المبلغ')
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
            ])->columns(2);
    }

    protected static function getPaymentStatusSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('حالة الدفع')
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
            ])->columns(3);
    }

    protected static function getGatewayInfoSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('معلومات البوابة')
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
            ->collapsed();
    }

    protected static function getNotesSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('ملاحظات إضافية')
            ->schema([
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3),

                Forms\Components\Textarea::make('failure_reason')
                    ->label('سبب الفشل')
                    ->rows(3)
                    ->visible(fn ($record) => $record?->is_failed ?? false),
            ])->collapsible()
            ->collapsed();
    }

    // Shared table columns
    protected static function getSharedTableColumns(): array
    {
        return [
            static::getAcademyColumn(),

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
        ];
    }

    // Shared filters
    protected static function getSharedFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('status')
                ->label(__('filament.status'))
                ->options(PaymentStatus::options())
                ->multiple(),

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
        ];
    }

    // Shared actions
    protected static function getMarkCompletedAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('mark_completed')
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
            });
    }

    protected static function getGenerateInvoiceAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('generate_invoice')
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
            });
    }

    protected static function getDownloadInvoiceAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('download_invoice')
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
            });
    }

    // Apply panel-specific scoping
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['user', 'academy']);

        return static::scopeEloquentQuery($query);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns(static::getSharedTableColumns())
            ->filters(static::getSharedFilters())
            ->actions(static::getTableActions())
            ->bulkActions(static::getTableBulkActions())
            ->defaultSort('created_at', 'desc');
    }
}
