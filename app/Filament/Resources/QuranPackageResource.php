<?php
namespace App\Filament\Resources;
use App\Filament\Resources\QuranPackageResource\Pages;
use App\Filament\Shared\Resources\BasePackageResource;
use App\Models\QuranPackage;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuranPackageResource extends BasePackageResource {
    protected static ?string $model = QuranPackage::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'باقات القرآن';
    protected static ?string $modelLabel = 'باقة قرآن';
    protected static ?string $pluralModelLabel = 'باقات القرآن';
    protected static ?string $navigationGroup = 'إدارة القرآن';

    protected static function scopeEloquentQuery(Builder $query): Builder {
        return $query->withoutGlobalScopes([SoftDeletingScope::class]);
    }
    protected static function getTableActions(): array {
        return [Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(), Tables\Actions\RestoreAction::make(), Tables\Actions\ForceDeleteAction::make()];
    }
    protected static function getTableBulkActions(): array {
        return [Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make(),
            Tables\Actions\RestoreBulkAction::make(), Tables\Actions\ForceDeleteBulkAction::make()])];
    }
    protected static function getTableColumns(): array {
        return array_merge([static::getAcademyColumn()], parent::getTableColumns());
    }
    public static function getPages(): array {
        return ['index' => Pages\ListQuranPackages::route('/'), 'create' => Pages\CreateQuranPackage::route('/create'),
            'view' => Pages\ViewQuranPackage::route('/{record}'), 'edit' => Pages\EditQuranPackage::route('/{record}/edit')];
    }
}
