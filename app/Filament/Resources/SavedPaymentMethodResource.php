<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SavedPaymentMethodResource\Pages;
use App\Filament\Resources\SavedPaymentMethodResource\Pages\EditSavedPaymentMethod;
use App\Filament\Resources\SavedPaymentMethodResource\Pages\ListSavedPaymentMethods;
use App\Filament\Resources\SavedPaymentMethodResource\Pages\ViewSavedPaymentMethod;
use App\Filament\Shared\Resources\Financial\BaseSavedPaymentMethodResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SavedPaymentMethodResource extends BaseSavedPaymentMethodResource
{
    protected static ?string $navigationLabel = 'طرق الدفع المحفوظة';

    protected static string|\UnitEnum|null $navigationGroup = 'المالية';

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
            ActionGroup::make([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                static::getToggleActiveAction(),
                static::getSetDefaultAction(),
            ]),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->label('حذف المحدد'),
                RestoreBulkAction::make()
                    ->label('استعادة المحدد'),
                ForceDeleteBulkAction::make()
                    ->label('حذف نهائي'),
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
            'index' => ListSavedPaymentMethods::route('/'),
            'view' => ViewSavedPaymentMethod::route('/{record}'),
            'edit' => EditSavedPaymentMethod::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }
}
