<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SavedPaymentMethodResource\Pages;
use App\Filament\Shared\Resources\Financial\BaseSavedPaymentMethodResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class SavedPaymentMethodResource extends BaseSavedPaymentMethodResource
{
    protected static ?string $navigationLabel = 'طرق الدفع المحفوظة';

    protected static ?string $navigationGroup = 'المالية';

    protected static ?int $navigationSort = 2;

    // ========================================
    // Abstract Method Implementations
    // ========================================

    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        // Admin panel sees all saved payment methods
        return $query;
    }

    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make()
                ->label('عرض'),
            Tables\Actions\EditAction::make()
                ->label('تعديل'),
            static::getToggleActiveAction(),
            static::getSetDefaultAction(),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('حذف المحدد'),
                Tables\Actions\RestoreBulkAction::make()
                    ->label('استعادة المحدد'),
                Tables\Actions\ForceDeleteBulkAction::make()
                    ->label('حذف نهائي'),
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
                static::getUserInfoSection(),
                static::getCardInfoSection(),
                static::getStatusSection(),
                static::getAdditionalInfoSection(),
            ]);
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSavedPaymentMethods::route('/'),
            'view' => Pages\ViewSavedPaymentMethod::route('/{record}'),
            'edit' => Pages\EditSavedPaymentMethod::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }
}
