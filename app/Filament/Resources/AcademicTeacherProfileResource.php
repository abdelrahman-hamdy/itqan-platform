<?php
namespace App\Filament\Resources;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Tables\Columns\ImageColumn;
use App\Filament\Resources\AcademicTeacherProfileResource\Pages\ListAcademicTeacherProfiles;
use App\Filament\Resources\AcademicTeacherProfileResource\Pages\CreateAcademicTeacherProfile;
use App\Filament\Resources\AcademicTeacherProfileResource\Pages\ViewAcademicTeacherProfile;
use App\Filament\Resources\AcademicTeacherProfileResource\Pages\EditAcademicTeacherProfile;
use App\Filament\Resources\AcademicTeacherProfileResource\Pages;
use App\Filament\Shared\Resources\Profiles\BaseAcademicTeacherProfileResource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicTeacherProfileResource extends BaseAcademicTeacherProfileResource {
    protected static function scopeEloquentQuery(Builder $query): Builder {
        return $query->withoutGlobalScopes([SoftDeletingScope::class]);
    }
    protected static function getTableActions(): array {
        return [ViewAction::make(), EditAction::make(), DeleteAction::make(),
            RestoreAction::make(), ForceDeleteAction::make()];
    }
    protected static function getTableBulkActions(): array {
        return [BulkActionGroup::make([DeleteBulkAction::make(),
            RestoreBulkAction::make(), ForceDeleteBulkAction::make()])];
    }
    protected static function getTableColumns(): array {
        return array_merge([static::getAcademyColumn()], 
            [ImageColumn::make('avatar')->label('الصورة')->circular()
                ->defaultImageUrl(fn($record) => config('services.ui_avatars.base_url').'?name='.urlencode($record->full_name ?? 'N/A').'&background=4169E1&color=fff')],
            parent::getTableColumns());
    }
    public static function getPages(): array {
        return ['index' => ListAcademicTeacherProfiles::route('/'),
            'create' => CreateAcademicTeacherProfile::route('/create'),
            'view' => ViewAcademicTeacherProfile::route('/{record}'),
            'edit' => EditAcademicTeacherProfile::route('/{record}/edit')];
    }
}
