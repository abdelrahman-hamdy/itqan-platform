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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use App\Filament\Resources\ParentProfileResource\RelationManagers\StudentsRelationManager;
use App\Filament\Resources\ParentProfileResource\Pages\ListParentProfiles;
use App\Filament\Resources\ParentProfileResource\Pages\CreateParentProfile;
use App\Filament\Resources\ParentProfileResource\Pages\ViewParentProfile;
use App\Filament\Resources\ParentProfileResource\Pages\EditParentProfile;
use App\Filament\Resources\ParentProfileResource\Pages;
use App\Filament\Resources\ParentProfileResource\RelationManagers;
use App\Filament\Shared\Resources\Profiles\BaseParentProfileResource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ParentProfileResource extends BaseParentProfileResource
{
    // Override academy relationship path
    protected static function getAcademyRelationshipPath(): string
    {
        return 'user'; // ParentProfile -> User -> academy_id
    }

    // ========================================
    // Panel-Specific Implementations
    // ========================================

    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        // Admin sees all academies with soft delete scope removed
        return $query->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    protected static function getTableActions(): array
    {
        return [
            ViewAction::make(),
            EditAction::make(),
            static::getToggleActiveAction(),
            DeleteAction::make(),
            RestoreAction::make()
                ->label(__('filament.actions.restore')),
            ForceDeleteAction::make()
                ->label(__('filament.actions.force_delete')),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
                static::getActivateBulkAction(),
                static::getDeactivateBulkAction(),
                RestoreBulkAction::make()
                    ->label(__('filament.actions.restore_selected')),
                ForceDeleteBulkAction::make()
                    ->label(__('filament.actions.force_delete_selected')),
            ]),
        ];
    }

    // ========================================
    // Admin-Specific Table Customization
    // ========================================

    protected static function getTableColumns(): array
    {
        $columns = [];

        // Add academy column first (when viewing all academies)
        $columns[] = static::getAcademyColumn();

        // Add avatar column (Admin-specific)
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
