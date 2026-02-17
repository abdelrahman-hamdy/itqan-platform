<?php

namespace App\Filament\Academy\Resources;

use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use App\Filament\Academy\Resources\PaymentResource\Pages\ListPayments;
use App\Filament\Academy\Resources\PaymentResource\Pages\CreatePayment;
use App\Filament\Academy\Resources\PaymentResource\Pages\ViewPayment;
use App\Filament\Academy\Resources\PaymentResource\Pages\EditPayment;
use App\Filament\Academy\Resources\PaymentResource\Pages;
use App\Filament\Shared\Resources\Financial\BasePaymentResource;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PaymentResource extends BasePaymentResource
{
    protected static string | \UnitEnum | null $navigationGroup = 'المالية';

    protected static ?int $navigationSort = 1;

    // ========================================
    // Abstract Method Implementations
    // ========================================

    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        // Academy panel sees only own academy's payments
        $academyId = Auth::user()->academy_id;

        return $query->where('academy_id', $academyId);
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
        // Academy admins cannot bulk delete payments
        return [];
    }

    protected static function getAcademyFormField(): ?Select
    {
        // No academy field needed - auto-scoped to current academy
        return null;
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

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'view' => ViewPayment::route('/{record}'),
            'edit' => EditPayment::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }
}
