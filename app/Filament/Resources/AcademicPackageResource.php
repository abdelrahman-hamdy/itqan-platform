<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicPackageResource\Pages\CreateAcademicPackage;
use App\Filament\Resources\AcademicPackageResource\Pages\EditAcademicPackage;
use App\Filament\Resources\AcademicPackageResource\Pages\ListAcademicPackages;
use App\Filament\Resources\AcademicPackageResource\Pages\ViewAcademicPackage;
use App\Filament\Shared\Resources\BasePackageResource;
use App\Models\AcademicPackage;
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

class AcademicPackageResource extends BasePackageResource
{
    protected static ?string $model = AcademicPackage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'باقات أكاديمية';

    protected static ?string $modelLabel = 'باقة أكاديمية';

    protected static ?string $pluralModelLabel = 'باقات أكاديمية';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة التعليم الأكاديمي';

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

    protected static function getTableColumns(): array
    {
        return array_merge([static::getAcademyColumn()], parent::getTableColumns());
    }

    protected static function getTableFilters(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return ['index' => ListAcademicPackages::route('/'), 'create' => CreateAcademicPackage::route('/create'),
            'view' => ViewAcademicPackage::route('/{record}'), 'edit' => EditAcademicPackage::route('/{record}/edit')];
    }
}
