<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Shared\Resources\Financial\BasePaymentResource;
use App\Models\Academy;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends BasePaymentResource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'المدفوعات';

    protected static ?string $modelLabel = 'دفعة';

    protected static ?string $pluralModelLabel = 'المدفوعات';

    protected static ?string $navigationGroup = 'المالية';

    protected static ?int $navigationSort = 1;

    // ========================================
    // Abstract Method Implementations
    // ========================================

    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        // Admin panel sees all payments (no scoping)
        return $query;
    }

    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make()
                ->label('عرض'),
            Tables\Actions\EditAction::make()
                ->label('تعديل'),
            static::getMarkCompletedAction(),
            static::getGenerateInvoiceAction(),
            static::getDownloadInvoiceAction(),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('حذف المحدد'),
            ]),
        ];
    }

    protected static function getAcademyFormField(): ?Forms\Components\Select
    {
        return Forms\Components\Select::make('academy_id')
            ->relationship('academy', 'name')
            ->label('الأكاديمية')
            ->required()
            ->searchable()
            ->preload();
    }

    // ========================================
    // Form - Uses Parent Sections
    // ========================================

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                static::getPaymentInfoSection(),
                static::getAmountDetailsSection(),
                static::getPaymentStatusSection(),
                static::getGatewayInfoSection(),
                static::getNotesSection(),
            ]);
    }

    // Table is handled by parent class (BasePaymentResource)

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

    // Eloquent query is handled by parent (BasePaymentResource)
}
