<?php

namespace App\Filament\Academy\Resources;

use App\Enums\UserType;
use App\Filament\Academy\Resources\ParentProfileResource\Pages;
use App\Filament\Academy\Resources\ParentProfileResource\RelationManagers;
use App\Filament\Shared\Resources\Profiles\BaseParentProfileResource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ParentProfileResource extends BaseParentProfileResource
{
    // ========================================
    // Panel-Specific Implementations
    // ========================================

    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        // Academy admins see only parents in their academy
        $academyId = Auth::user()->academy_id;

        return $query->where('academy_id', $academyId);
    }

    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            static::getToggleActiveAction(),
            // Academy admins cannot delete parents
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                static::getActivateBulkAction(),
                static::getDeactivateBulkAction(),
            ]),
        ];
    }

    // ========================================
    // Academy-Specific Table Customization
    // ========================================

    protected static function getTableColumns(): array
    {
        $columns = [];

        // Add avatar column (Academy-specific)
        $columns[] = Tables\Columns\ImageColumn::make('avatar')
            ->label('الصورة')
            ->circular()
            ->defaultImageUrl(fn ($record) => config('services.ui_avatars.base_url', 'https://ui-avatars.com/api/').'?name='.urlencode($record->full_name ?? 'N/A').'&background=4169E1&color=fff');

        // Add shared columns from base
        $columns[] = Tables\Columns\TextColumn::make('parent_code')
            ->label('رمز ولي الأمر')
            ->searchable()
            ->sortable()
            ->copyable();

        $columns[] = Tables\Columns\TextColumn::make('full_name')
            ->label('الاسم الكامل')
            ->searchable(['first_name', 'last_name'])
            ->sortable()
            ->weight(FontWeight::Bold);

        $columns[] = Tables\Columns\TextColumn::make('email')
            ->label('البريد الإلكتروني')
            ->searchable()
            ->sortable()
            ->copyable();

        $columns[] = Tables\Columns\TextColumn::make('phone')
            ->label('رقم الهاتف')
            ->searchable()
            ->copyable();

        $columns[] = Tables\Columns\IconColumn::make('has_students')
            ->label('مرتبط بطلاب')
            ->boolean()
            ->getStateUsing(fn ($record) => $record->students()->exists());

        $columns[] = Tables\Columns\IconColumn::make('user.active_status')
            ->label('نشط')
            ->boolean()
            ->trueIcon('heroicon-o-check-circle')
            ->falseIcon('heroicon-o-x-circle')
            ->trueColor('success')
            ->falseColor('danger')
            ->sortable();

        $columns[] = Tables\Columns\TextColumn::make('created_at')
            ->label(__('filament.created_at'))
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);

        return $columns;
    }

    // ========================================
    // Academy-Specific Authorization
    // ========================================

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Only academy admins can view
        return $user->hasRole(UserType::ADMIN->value) && $user->academy_id !== null;
    }

    public static function canView($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Verify parent belongs to admin's academy
        return $record->academy_id === $user->academy_id;
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Verify parent belongs to admin's academy
        return $record->academy_id === $user->academy_id;
    }

    public static function canDelete($record): bool
    {
        // Academy admins cannot delete parents
        return false;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Academy admins can create parents
        return $user->hasRole(UserType::ADMIN->value) && $user->academy_id !== null;
    }

    // ========================================
    // Relation Managers
    // ========================================

    public static function getRelations(): array
    {
        return [
            RelationManagers\StudentsRelationManager::class,
        ];
    }

    // ========================================
    // Resource Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParentProfiles::route('/'),
            'create' => Pages\CreateParentProfile::route('/create'),
            'view' => Pages\ViewParentProfile::route('/{record}'),
            'edit' => Pages\EditParentProfile::route('/{record}/edit'),
        ];
    }
}
