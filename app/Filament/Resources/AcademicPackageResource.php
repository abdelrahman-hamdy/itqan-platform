<?php
namespace App\Filament\Resources;
use App\Filament\Resources\AcademicPackageResource\Pages;
use App\Filament\Shared\Resources\BasePackageResource;
use App\Models\AcademicPackage;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicPackageResource extends BasePackageResource {
    protected static ?string $model = AcademicPackage::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'باقات أكاديمية';
    protected static ?string $modelLabel = 'باقة أكاديمية';
    protected static ?string $pluralModelLabel = 'باقات أكاديمية';
    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';

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
        return ['index' => Pages\ListAcademicPackages::route('/'), 'create' => Pages\CreateAcademicPackage::route('/create'),
            'view' => Pages\ViewAcademicPackage::route('/{record}'), 'edit' => Pages\EditAcademicPackage::route('/{record}/edit')];
    }
}
