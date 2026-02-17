<?php
namespace App\Filament\Academy\Resources;
use App\Enums\UserType;
use App\Filament\Academy\Resources\QuranTeacherProfileResource\Pages;
use App\Filament\Shared\Resources\Profiles\BaseQuranTeacherProfileResource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class QuranTeacherProfileResource extends BaseQuranTeacherProfileResource {
    protected static function scopeEloquentQuery(Builder $query): Builder {
        return $query->where('academy_id', Auth::user()->academy_id);
    }
    protected static function getTableActions(): array {
        return [Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make()];
    }
    protected static function getTableBulkActions(): array { return []; }
    protected static function getTableColumns(): array {
        return array_merge([Tables\Columns\ImageColumn::make('avatar')->label('الصورة')->circular()
            ->defaultImageUrl(fn($record) => config('services.ui_avatars.base_url').'?name='.urlencode($record->full_name ?? 'N/A').'&background=059669&color=fff')],
            parent::getTableColumns());
    }
    public static function canViewAny(): bool {
        return auth()->user()?->hasRole(UserType::ADMIN->value) && auth()->user()?->academy_id !== null;
    }
    public static function canDelete($record): bool { return false; }
    public static function getPages(): array {
        return ['index' => Pages\ListQuranTeacherProfiles::route('/'),
            'create' => Pages\CreateQuranTeacherProfile::route('/create'),
            'view' => Pages\ViewQuranTeacherProfile::route('/{record}'),
            'edit' => Pages\EditQuranTeacherProfile::route('/{record}/edit')];
    }
}
