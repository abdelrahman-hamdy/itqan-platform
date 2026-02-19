<?php

namespace App\Filament\Academy\Resources;

use App\Enums\UserType;
use App\Filament\Academy\Resources\QuranPackageResource\Pages\CreateQuranPackage;
use App\Filament\Academy\Resources\QuranPackageResource\Pages\EditQuranPackage;
use App\Filament\Academy\Resources\QuranPackageResource\Pages\ListQuranPackages;
use App\Filament\Academy\Resources\QuranPackageResource\Pages\ViewQuranPackage;
use App\Filament\Shared\Resources\BasePackageResource;
use App\Models\QuranPackage;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class QuranPackageResource extends BasePackageResource
{
    protected static ?string $model = QuranPackage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'باقات القرآن';

    protected static ?string $modelLabel = 'باقة قرآن';

    protected static ?string $pluralModelLabel = 'باقات القرآن';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة القرآن';

    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query->where('academy_id', Auth::user()->academy_id);
    }

    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
                DeleteAction::make()
                    ->label('حذف'),
            ]),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(UserType::ADMIN->value) && auth()->user()?->academy_id !== null;
    }

    public static function canDelete($record): bool
    {
        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuranPackages::route('/'),
            'create' => CreateQuranPackage::route('/create'),
            'view' => ViewQuranPackage::route('/{record}'),
            'edit' => EditQuranPackage::route('/{record}/edit'),
        ];
    }
}
