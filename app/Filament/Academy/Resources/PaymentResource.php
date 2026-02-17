<?php

namespace App\Filament\Academy\Resources;

use App\Filament\Academy\Resources\PaymentResource\Pages;
use App\Filament\Shared\Resources\Financial\BasePaymentResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PaymentResource extends BasePaymentResource
{
    protected static ?string $navigationGroup = 'المالية';

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
        // Academy admins cannot bulk delete payments
        return [];
    }

    protected static function getAcademyFormField(): ?Forms\Components\Select
    {
        // No academy field needed - auto-scoped to current academy
        return null;
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

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }
}
