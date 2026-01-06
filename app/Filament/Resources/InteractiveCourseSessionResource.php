<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InteractiveCourseSessionResource\Pages;
use App\Filament\Shared\Resources\BaseInteractiveCourseSessionResource;
use App\Models\InteractiveCourseSession;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Interactive Course Session Resource for SuperAdmin Panel
 *
 * Full CRUD access with soft delete support.
 * Extends BaseInteractiveCourseSessionResource for shared form/table definitions.
 */
class InteractiveCourseSessionResource extends BaseInteractiveCourseSessionResource
{
    // ========================================
    // Tenant Configuration
    // ========================================

    /**
     * Academy relationship path for BaseResource.
     * InteractiveCourseSession gets academy through course relationship.
     */
    protected static function getAcademyRelationshipPath(): string
    {
        return 'course.academy';
    }

    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 3;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * SuperAdmin sees all sessions, including soft-deleted ones.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /**
     * Session info section with full course selection.
     */
    protected static function getSessionInfoFormSection(): Section
    {
        return Section::make('معلومات الجلسة الأساسية')
            ->schema([
                Forms\Components\Select::make('course_id')
                    ->relationship('course', 'title')
                    ->label('الدورة')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->disabled(fn ($record) => $record !== null)
                    ->dehydrated(),

                Forms\Components\TextInput::make('session_code')
                    ->label('رمز الجلسة')
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\TextInput::make('session_number')
                    ->label('رقم الجلسة')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->helperText('رقم الجلسة ضمن الدورة'),
            ])->columns(2);
    }

    /**
     * Full table actions for SuperAdmin with soft deletes.
     */
    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),

                static::makeStartSessionAction(),
                static::makeCompleteSessionAction(),
                static::makeCancelSessionAction('admin'),
                static::makeJoinMeetingAction(),

                Tables\Actions\RestoreAction::make()
                    ->label(__('filament.actions.restore')),
                Tables\Actions\ForceDeleteAction::make()
                    ->label(__('filament.actions.force_delete')),
            ]),
        ];
    }

    /**
     * Full bulk actions for SuperAdmin.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make()
                    ->label(__('filament.actions.restore_selected')),
                Tables\Actions\ForceDeleteBulkAction::make()
                    ->label(__('filament.actions.force_delete_selected')),
            ]),
        ];
    }

    // ========================================
    // Additional Form Sections (SuperAdmin-specific)
    // ========================================

    /**
     * Add notes section for SuperAdmin.
     */
    protected static function getAdditionalFormSections(): array
    {
        return [
            static::getNotesFormSection(),
        ];
    }

    /**
     * Notes section - SuperAdmin only.
     */
    protected static function getNotesFormSection(): Section
    {
        return Section::make('ملاحظات')
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Textarea::make('session_notes')
                            ->label('ملاحظات الجلسة')
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('ملاحظات داخلية للإدارة'),

                        Forms\Components\Textarea::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->rows(3)
                            ->maxLength(2000)
                            ->helperText('ملاحظات مرئية للمشرف والإدارة فقط'),
                    ]),
            ])
            ->collapsible()
            ->collapsed();
    }

    // ========================================
    // Table Filters Override (SuperAdmin-specific)
    // ========================================

    /**
     * Extended filters with course and trashed.
     */
    protected static function getTableFilters(): array
    {
        return [
            ...parent::getTableFilters(),

            Tables\Filters\SelectFilter::make('course_id')
                ->label('الدورة')
                ->relationship('course', 'title')
                ->searchable(),

            Tables\Filters\TrashedFilter::make()
                ->label(__('filament.filters.trashed')),
        ];
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInteractiveCourseSessions::route('/'),
            'create' => Pages\CreateInteractiveCourseSession::route('/create'),
            'view' => Pages\ViewInteractiveCourseSession::route('/{record}'),
            'edit' => Pages\EditInteractiveCourseSession::route('/{record}/edit'),
        ];
    }
}
