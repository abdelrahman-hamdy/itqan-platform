<?php

namespace App\Filament\Resources;

use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use App\Filament\Resources\PaymentResource\Pages\ListPayments;
use App\Filament\Resources\PaymentResource\Pages\CreatePayment;
use App\Filament\Resources\PaymentResource\Pages\ViewPayment;
use App\Filament\Resources\PaymentResource\Pages\EditPayment;
use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Shared\Resources\Financial\BasePaymentResource;
use App\Models\Academy;
use App\Models\Payment;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends BasePaymentResource
{
    protected static ?string $model = Payment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'المدفوعات';

    protected static ?string $modelLabel = 'دفعة';

    protected static ?string $pluralModelLabel = 'المدفوعات';

    protected static string | \UnitEnum | null $navigationGroup = 'المالية';

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
            ViewAction::make()
                ->label('عرض'),
            EditAction::make()
                ->label('تعديل'),
            static::getMarkCompletedAction(),
            static::getGenerateInvoiceAction(),
            static::getDownloadInvoiceAction(),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->label('حذف المحدد'),
            ]),
        ];
    }

    protected static function getAcademyFormField(): ?Select
    {
        return Select::make('academy_id')
            ->relationship('academy', 'name')
            ->label('الأكاديمية')
            ->required()
            ->searchable()
            ->preload();
    }

    // ========================================
    // Form - Uses Parent Sections
    // ========================================

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
            'index' => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'view' => ViewPayment::route('/{record}'),
            'edit' => EditPayment::route('/{record}/edit'),
        ];
    }

    // Eloquent query is handled by parent (BasePaymentResource)
}
