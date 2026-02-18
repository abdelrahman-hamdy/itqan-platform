<?php

namespace App\Filament\Academy\Resources;

use App\Enums\UserType;
use App\Filament\Academy\Resources\ParentProfileResource\Pages;
use App\Filament\Academy\Resources\ParentProfileResource\Pages\CreateParentProfile;
use App\Filament\Academy\Resources\ParentProfileResource\Pages\EditParentProfile;
use App\Filament\Academy\Resources\ParentProfileResource\Pages\ListParentProfiles;
use App\Filament\Academy\Resources\ParentProfileResource\Pages\ViewParentProfile;
use App\Filament\Academy\Resources\ParentProfileResource\RelationManagers\StudentsRelationManager;
use App\Filament\Shared\Resources\Profiles\BaseParentProfileResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
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
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
                static::getToggleActiveAction(),
                DeleteAction::make()
                    ->label('حذف'),
            ]),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                static::getActivateBulkAction(),
                static::getDeactivateBulkAction(),
                DeleteBulkAction::make(),
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
        $columns[] = ImageColumn::make('avatar')
            ->label('الصورة')
            ->circular()
            ->defaultImageUrl(fn ($record) => config('services.ui_avatars.base_url', 'https://ui-avatars.com/api/').'?name='.urlencode($record->full_name ?? 'N/A').'&background=4169E1&color=fff');

        // Add shared columns from base
        $columns[] = TextColumn::make('parent_code')
            ->label('رمز ولي الأمر')
            ->searchable()
            ->sortable()
            ->copyable();

        $columns[] = TextColumn::make('full_name')
            ->label('الاسم الكامل')
            ->searchable(['first_name', 'last_name'])
            ->sortable()
            ->weight(FontWeight::Bold);

        $columns[] = TextColumn::make('email')
            ->label('البريد الإلكتروني')
            ->searchable()
            ->sortable()
            ->copyable();

        $columns[] = TextColumn::make('phone')
            ->label('رقم الهاتف')
            ->searchable()
            ->copyable();

        $columns[] = IconColumn::make('has_students')
            ->label('مرتبط بطلاب')
            ->boolean()
            ->getStateUsing(fn ($record) => $record->students()->exists());

        $columns[] = IconColumn::make('user.active_status')
            ->label('نشط')
            ->boolean()
            ->trueIcon('heroicon-o-check-circle')
            ->falseIcon('heroicon-o-x-circle')
            ->trueColor('success')
            ->falseColor('danger')
            ->sortable();

        $columns[] = TextColumn::make('created_at')
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
        return true;
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
            StudentsRelationManager::class,
        ];
    }

    // ========================================
    // Resource Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => ListParentProfiles::route('/'),
            'create' => CreateParentProfile::route('/create'),
            'view' => ViewParentProfile::route('/{record}'),
            'edit' => EditParentProfile::route('/{record}/edit'),
        ];
    }
}
