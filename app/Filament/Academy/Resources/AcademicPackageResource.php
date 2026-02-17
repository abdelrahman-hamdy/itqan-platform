<?php
namespace App\Filament\Academy\Resources;
use App\Enums\UserType;
use App\Filament\Academy\Resources\AcademicPackageResource\Pages;
use App\Filament\Shared\Resources\BasePackageResource;
use App\Models\AcademicPackage;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AcademicPackageResource extends BasePackageResource {
    protected static ?string $model = AcademicPackage::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'باقات أكاديمية';
    protected static ?string $modelLabel = 'باقة أكاديمية';
    protected static ?string $pluralModelLabel = 'باقات أكاديمية';
    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static function scopeEloquentQuery(Builder $query): Builder {
        return $query->where('academy_id', Auth::user()->academy_id);
    }
    protected static function getTableActions(): array {
        return [Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make()];
    }
    protected static function getTableBulkActions(): array { return []; }
    public static function canViewAny(): bool {
        return auth()->user()?->hasRole(UserType::ADMIN->value) && auth()->user()?->academy_id !== null;
    }
    public static function canDelete($record): bool { return false; }
    public static function getPages(): array {
        return ['index' => Pages\ListAcademicPackages::route('/'), 'create' => Pages\CreateAcademicPackage::route('/create'),
            'view' => Pages\ViewAcademicPackage::route('/{record}'), 'edit' => Pages\EditAcademicPackage::route('/{record}/edit')];
    }
}
