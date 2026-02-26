<?php

namespace App\Filament\Academy\Resources;

use App\Filament\Academy\Resources\SavedPaymentMethodResource\Pages;
use App\Filament\Academy\Resources\SavedPaymentMethodResource\Pages\EditSavedPaymentMethod;
use App\Filament\Academy\Resources\SavedPaymentMethodResource\Pages\ListSavedPaymentMethods;
use App\Filament\Academy\Resources\SavedPaymentMethodResource\Pages\ViewSavedPaymentMethod;
use App\Filament\Shared\Resources\Financial\BaseSavedPaymentMethodResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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
        // Academy panel sees only own academy's saved payment methods
        $academyId = Auth::user()->academy_id;

        return $query->where('academy_id', $academyId);
    }

    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
                static::getToggleActiveAction(),
                static::getSetDefaultAction(),
            ]),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        // Academy admins cannot bulk delete saved payment methods
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
                static::getUserInfoSection(),
                static::getCardInfoSection(),
                static::getStatusSection(),
                static::getAdditionalInfoSection(),
            ]);
    }

    // ========================================
    // Authorization
    // ========================================

    public static function canEdit($record): bool
    {
        $academyId = auth()->user()?->academy_id;

        return $academyId !== null && $record->academy_id === $academyId;
    }

    public static function canView($record): bool
    {
        $academyId = auth()->user()?->academy_id;

        return $academyId !== null && $record->academy_id === $academyId;
    }

    public static function canDelete($record): bool
    {
        $academyId = auth()->user()?->academy_id;

        return $academyId !== null && $record->academy_id === $academyId;
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
