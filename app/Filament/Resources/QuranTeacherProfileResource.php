<?php
namespace App\Filament\Resources;
use App\Filament\Resources\QuranTeacherProfileResource\Pages;
use App\Filament\Shared\Resources\Profiles\BaseQuranTeacherProfileResource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuranTeacherProfileResource extends BaseQuranTeacherProfileResource {
    protected static function scopeEloquentQuery(Builder $query): Builder {
        return $query->withoutGlobalScopes([SoftDeletingScope::class]);
    }
    protected static function getTableActions(): array {
        return [Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make(),
            Tables\Actions\RestoreAction::make(), Tables\Actions\ForceDeleteAction::make()];
    }
    protected static function getTableBulkActions(): array {
        return [Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make(),
            Tables\Actions\RestoreBulkAction::make(), Tables\Actions\ForceDeleteBulkAction::make()])];
    }
    protected static function getTableColumns(): array {
        return array_merge([static::getAcademyColumn()],
            [Tables\Columns\ImageColumn::make('avatar')->label('الصورة')->circular()
                ->defaultImageUrl(fn($record) => config('services.ui_avatars.base_url').'?name='.urlencode($record->full_name ?? 'N/A').'&background=059669&color=fff')],
            parent::getTableColumns());
    }
    public static function getPages(): array {
        return ['index' => Pages\ListQuranTeacherProfiles::route('/'),
            'create' => Pages\CreateQuranTeacherProfile::route('/create'),
            'view' => Pages\ViewQuranTeacherProfile::route('/{record}'),
            'edit' => Pages\EditQuranTeacherProfile::route('/{record}/edit')];
    }
}
