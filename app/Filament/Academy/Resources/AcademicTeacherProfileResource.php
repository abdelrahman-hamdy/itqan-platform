<?php
namespace App\Filament\Academy\Resources;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use App\Filament\Academy\Resources\AcademicTeacherProfileResource\Pages\ListAcademicTeacherProfiles;
use App\Filament\Academy\Resources\AcademicTeacherProfileResource\Pages\CreateAcademicTeacherProfile;
use App\Filament\Academy\Resources\AcademicTeacherProfileResource\Pages\ViewAcademicTeacherProfile;
use App\Filament\Academy\Resources\AcademicTeacherProfileResource\Pages\EditAcademicTeacherProfile;
use App\Enums\UserType;
use App\Filament\Academy\Resources\AcademicTeacherProfileResource\Pages;
use App\Filament\Shared\Resources\Profiles\BaseAcademicTeacherProfileResource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AcademicTeacherProfileResource extends BaseAcademicTeacherProfileResource {
    protected static function scopeEloquentQuery(Builder $query): Builder {
        return $query->where('academy_id', Auth::user()->academy_id);
    }
    protected static function getTableActions(): array {
        return [ViewAction::make(), EditAction::make()];
    }
    protected static function getTableBulkActions(): array { return []; }
    protected static function getTableColumns(): array {
        return array_merge([ImageColumn::make('avatar')->label('الصورة')->circular()
            ->defaultImageUrl(fn($record) => config('services.ui_avatars.base_url').'?name='.urlencode($record->full_name ?? 'N/A').'&background=4169E1&color=fff')],
            parent::getTableColumns());
    }
    public static function canViewAny(): bool {
        return auth()->user()?->hasRole(UserType::ADMIN->value) && auth()->user()?->academy_id !== null;
    }
    public static function canDelete($record): bool { return false; }
    public static function getPages(): array {
        return ['index' => ListAcademicTeacherProfiles::route('/'),
            'create' => CreateAcademicTeacherProfile::route('/create'),
            'view' => ViewAcademicTeacherProfile::route('/{record}'),
            'edit' => EditAcademicTeacherProfile::route('/{record}/edit')];
    }
}
