<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InteractiveCourseResource\Pages;
use App\Filament\Resources\InteractiveCourseResource\Pages\CreateInteractiveCourse;
use App\Filament\Resources\InteractiveCourseResource\Pages\EditInteractiveCourse;
use App\Filament\Resources\InteractiveCourseResource\Pages\ListInteractiveCourses;
use App\Filament\Resources\InteractiveCourseResource\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Shared\Resources\BaseInteractiveCourseResource;
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
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Interactive Course Resource for SuperAdmin Panel
 *
 * Full CRUD access with soft delete support.
 * Extends BaseInteractiveCourseResource for shared form/table definitions.
 */
class InteractiveCourseResource extends BaseInteractiveCourseResource
{
    // ========================================
    // Tenant Configuration
    // ========================================

    protected static ?string $tenantOwnershipRelationshipName = 'academy';

    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationLabel = 'الدورات التفاعلية';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 3;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * SuperAdmin sees all courses, including soft-deleted ones.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        // Include soft-deleted records for admin management
        return $query->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /**
     * Get form schema for SuperAdmin.
     */
    protected static function getFormSchema(): array
    {
        return [
            static::getBasicInfoFormSection(),
            static::getSpecializationFormSection(),
            static::getCourseSettingsFormSection(),
            static::getFinancialSettingsFormSection(),
            static::getDatesSchedulingFormSection(),
            static::getContentObjectivesFormSection(),
            static::getStatusSettingsFormSection(),
        ];
    }

    /**
     * Full table actions for SuperAdmin with soft deletes.
     */
    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
                RestoreAction::make()->label(__('filament.actions.restore')),
                ForceDeleteAction::make()->label(__('filament.actions.force_delete')),
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
    // Academy Column (not inherited from BaseResource)
    // ========================================

    protected static function getAcademyColumn(): TextColumn
    {
        return TextColumn::make('academy.name')
            ->label('الأكاديمية')
            ->sortable()
            ->searchable()
            ->visible(fn () => Filament::getTenant() === null)
            ->placeholder('غير محدد');
    }

    // ========================================
    // Table Columns Override (SuperAdmin-specific)
    // ========================================

    /**
     * Table columns with academy and course type.
     */
    protected static function getTableColumns(): array
    {
        $columns = parent::getTableColumns();

        // Essential columns that should always be visible
        $essentialNames = ['course_code', 'title', 'status'];

        // Add course type column (toggleable)
        $courseTypeColumn = TextColumn::make('course_type_in_arabic')
            ->label('النوع')
            ->badge()
            ->color(fn (string $state): string => match ($state) {
                'مكثف' => 'warning',
                'تحضير للامتحانات' => 'danger',
                default => 'primary',
            })
            ->toggleable();

        // Add academy column for global view
        $academyColumn = static::getAcademyColumn();

        // Insert columns at appropriate positions, making non-essential parent columns toggleable
        $result = [];
        foreach ($columns as $column) {
            if (! in_array($column->getName(), $essentialNames)) {
                $column->toggleable();
            }

            // Add academy column after gradeLevel
            if ($column->getName() === 'gradeLevel.name') {
                $result[] = $column;
                $result[] = $academyColumn;

                continue;
            }

            // Add course type column after teacher
            if ($column->getName() === 'assignedTeacher.user.name') {
                $result[] = $column;
                $result[] = $courseTypeColumn;

                continue;
            }

            $result[] = $column;
        }

        return $result;
    }

    // ========================================
    // Table Filters Override (SuperAdmin-specific)
    // ========================================

    /**
     * Extended filters with date range and trashed.
     */
    protected static function getTableFilters(): array
    {
        return [
            ...parent::getTableFilters(),

            Filter::make('created_at_from')
                ->schema([
                    DatePicker::make('value')
                        ->label(__('filament.filters.from_date')),
                ])
                ->query(fn (Builder $query, array $data): Builder => $query->when(
                    $data['value'] ?? null,
                    fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                ))
                ->indicateUsing(fn (array $data): array => isset($data['value']) && $data['value']
                    ? ['value' => __('filament.filters.from_date').': '.$data['value']]
                    : []),

            Filter::make('created_at_until')
                ->schema([
                    DatePicker::make('value')
                        ->label(__('filament.filters.to_date')),
                ])
                ->query(fn (Builder $query, array $data): Builder => $query->when(
                    $data['value'] ?? null,
                    fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                ))
                ->indicateUsing(fn (array $data): array => isset($data['value']) && $data['value']
                    ? ['value' => __('filament.filters.to_date').': '.$data['value']]
                    : []),
        ];
    }

    // ========================================
    // Relations
    // ========================================

    public static function getRelations(): array
    {
        return [
            EnrollmentsRelationManager::class,
        ];
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => ListInteractiveCourses::route('/'),
            'create' => CreateInteractiveCourse::route('/create'),
            'edit' => EditInteractiveCourse::route('/{record}/edit'),
        ];
    }
}
