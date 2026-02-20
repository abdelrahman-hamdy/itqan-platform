<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InteractiveCourseSessionResource\Pages;
use App\Filament\Resources\InteractiveCourseSessionResource\Pages\CreateInteractiveCourseSession;
use App\Filament\Resources\InteractiveCourseSessionResource\Pages\EditInteractiveCourseSession;
use App\Filament\Resources\InteractiveCourseSessionResource\Pages\ListInteractiveCourseSessions;
use App\Filament\Resources\InteractiveCourseSessionResource\Pages\ViewInteractiveCourseSession;
use App\Filament\Shared\Actions\MeetingActions;
use App\Filament\Shared\Resources\BaseInteractiveCourseSessionResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

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

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 3;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * SuperAdmin sees all sessions, excluding soft-deleted ones.
     * Use the TrashedFilter to view soft-deleted sessions when needed.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Session info section with full course selection.
     */
    protected static function getSessionInfoFormSection(): Section
    {
        return Section::make('معلومات الجلسة الأساسية')
            ->schema([
                Select::make('course_id')
                    ->relationship('course', 'title')
                    ->label('الدورة')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->disabled(fn ($record) => $record !== null)
                    ->dehydrated(),

                TextInput::make('session_code')
                    ->label('رمز الجلسة')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('session_number')
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
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
                MeetingActions::viewMeeting('interactive'),
                DeleteAction::make()
                    ->label('حذف'),

                RestoreAction::make()
                    ->label(__('filament.actions.restore')),
                ForceDeleteAction::make()
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
            BulkActionGroup::make([
                DeleteBulkAction::make(),
                RestoreBulkAction::make()
                    ->label(__('filament.actions.restore_selected')),
                ForceDeleteBulkAction::make()
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
                Grid::make(2)
                    ->schema([
                        Textarea::make('session_notes')
                            ->label('ملاحظات الجلسة')
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('ملاحظات داخلية للإدارة'),

                        Textarea::make('supervisor_notes')
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
    // Table Columns Override (SuperAdmin-specific)
    // ========================================

    protected static function getTableColumns(): array
    {
        $essentialNames = ['session_code', 'status'];
        $columns = parent::getTableColumns();

        foreach ($columns as $column) {
            if (! in_array($column->getName(), $essentialNames)) {
                $column->toggleable();
            }
        }

        return $columns;
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

            SelectFilter::make('course_id')
                ->label('الدورة')
                ->relationship('course', 'title')
                ->searchable(),

        ];
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => ListInteractiveCourseSessions::route('/'),
            'create' => CreateInteractiveCourseSession::route('/create'),
            'view' => ViewInteractiveCourseSession::route('/{record}'),
            'edit' => EditInteractiveCourseSession::route('/{record}/edit'),
        ];
    }
}
