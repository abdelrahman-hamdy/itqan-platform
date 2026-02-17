<?php

namespace App\Filament\Resources;

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
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            static::getToggleActiveAction(),
            Tables\Actions\DeleteAction::make(),
            Tables\Actions\RestoreAction::make()
                ->label(__('filament.actions.restore')),
            Tables\Actions\ForceDeleteAction::make()
                ->label(__('filament.actions.force_delete')),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                static::getActivateBulkAction(),
                static::getDeactivateBulkAction(),
                Tables\Actions\RestoreBulkAction::make()
                    ->label(__('filament.actions.restore_selected')),
                Tables\Actions\ForceDeleteBulkAction::make()
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
