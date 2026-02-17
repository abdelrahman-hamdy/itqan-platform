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
use App\Filament\Resources\QuranTeacherProfileResource\Pages\ListQuranTeacherProfiles;
use App\Filament\Resources\QuranTeacherProfileResource\Pages\CreateQuranTeacherProfile;
use App\Filament\Resources\QuranTeacherProfileResource\Pages\ViewQuranTeacherProfile;
use App\Filament\Resources\QuranTeacherProfileResource\Pages\EditQuranTeacherProfile;
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
                ->defaultImageUrl(fn($record) => config('services.ui_avatars.base_url').'?name='.urlencode($record->full_name ?? 'N/A').'&background=059669&color=fff')],
            parent::getTableColumns());
    }
    public static function getWidgets(): array {
        return [
            QuranTeacherProfileResource\Widgets\QuranTeachersStatsWidget::class,
        ];
    }
    public static function getPages(): array {
        return ['index' => ListQuranTeacherProfiles::route('/'),
            'create' => CreateQuranTeacherProfile::route('/create'),
            'view' => ViewQuranTeacherProfile::route('/{record}'),
            'edit' => EditQuranTeacherProfile::route('/{record}/edit')];
    }
}
