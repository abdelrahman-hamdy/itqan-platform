<?php

namespace App\Filament\Academy\Resources;

use App\Filament\Academy\Resources\SavedPaymentMethodResource\Pages;
use App\Filament\Shared\Resources\Financial\BaseSavedPaymentMethodResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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
        // Academy panel sees only own academy's saved payment methods
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
            static::getToggleActiveAction(),
            static::getSetDefaultAction(),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        // Academy admins cannot bulk delete saved payment methods
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
