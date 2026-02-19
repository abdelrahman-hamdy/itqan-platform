<?php

namespace App\Filament\Academy\Resources;

use App\Enums\UserType;
use App\Filament\Academy\Resources\AcademicPackageResource\Pages\CreateAcademicPackage;
use App\Filament\Academy\Resources\AcademicPackageResource\Pages\EditAcademicPackage;
use App\Filament\Academy\Resources\AcademicPackageResource\Pages\ListAcademicPackages;
use App\Filament\Academy\Resources\AcademicPackageResource\Pages\ViewAcademicPackage;
use App\Filament\Shared\Resources\BasePackageResource;
use App\Models\AcademicPackage;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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
            'index' => ListAcademicPackages::route('/'),
            'create' => CreateAcademicPackage::route('/create'),
            'view' => ViewAcademicPackage::route('/{record}'),
            'edit' => EditAcademicPackage::route('/{record}/edit'),
        ];
    }
}
