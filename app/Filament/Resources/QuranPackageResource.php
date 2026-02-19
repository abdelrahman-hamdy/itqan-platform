<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuranPackageResource\Pages\CreateQuranPackage;
use App\Filament\Resources\QuranPackageResource\Pages\EditQuranPackage;
use App\Filament\Resources\QuranPackageResource\Pages\ListQuranPackages;
use App\Filament\Resources\QuranPackageResource\Pages\ViewQuranPackage;
use App\Filament\Shared\Resources\BasePackageResource;
use App\Models\QuranPackage;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
        // Include soft-deleted records for admin management
        return $query->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
                RestoreAction::make()->label(__('filament.actions.restore')),
                ForceDeleteAction::make()->label(__('filament.actions.force_delete')),
            ]),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [BulkActionGroup::make([DeleteBulkAction::make(),
            RestoreBulkAction::make(), ForceDeleteBulkAction::make()])];
    }

    protected static function getTableFilters(): array
    {
        return [];
    }

    protected static function getTableColumns(): array
    {
        return array_merge([static::getAcademyColumn()], parent::getTableColumns());
    }

    public static function getPages(): array
    {
        return ['index' => ListQuranPackages::route('/'), 'create' => CreateQuranPackage::route('/create'),
            'view' => ViewQuranPackage::route('/{record}'), 'edit' => EditQuranPackage::route('/{record}/edit')];
    }
}
