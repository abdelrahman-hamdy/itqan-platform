<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InteractiveCourseResource\Pages;
use App\Filament\Resources\InteractiveCourseResource\RelationManagers;
use App\Filament\Shared\Resources\BaseInteractiveCourseResource;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Tables;
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

    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 3;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * SuperAdmin sees all courses, including soft-deleted ones.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
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
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
            Tables\Actions\RestoreAction::make()
                ->label(__('filament.actions.restore')),
            Tables\Actions\ForceDeleteAction::make()
                ->label(__('filament.actions.force_delete')),
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
    // Table Columns Override (SuperAdmin-specific)
    // ========================================

    /**
     * Table columns with academy and course type.
     */
    protected static function getTableColumns(): array
    {
        $columns = parent::getTableColumns();

        // Add course type column
        $courseTypeColumn = TextColumn::make('course_type_in_arabic')
            ->label('النوع')
            ->badge()
            ->color(fn (string $state): string => match ($state) {
                'مكثف' => 'warning',
                'تحضير للامتحانات' => 'danger',
                default => 'primary',
            });

        // Add academy column for global view
        $academyColumn = static::getAcademyColumn();
        // Insert columns at appropriate positions
        $result = [];
        foreach ($columns as $column) {
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

            Filter::make('created_at')
                ->form([
                    Forms\Components\DatePicker::make('from')
                        ->label(__('filament.filters.from_date')),
                    Forms\Components\DatePicker::make('until')
                        ->label(__('filament.filters.to_date')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];
                    if ($data['from'] ?? null) {
                        $indicators['from'] = __('filament.filters.from_date').': '.$data['from'];
                    }
                    if ($data['until'] ?? null) {
                        $indicators['until'] = __('filament.filters.to_date').': '.$data['until'];
                    }

                    return $indicators;
                }),

            Tables\Filters\TrashedFilter::make()
                ->label(__('filament.filters.trashed')),
        ];
    }

    // ========================================
    // Relations
    // ========================================

    public static function getRelations(): array
    {
        return [
            RelationManagers\EnrollmentsRelationManager::class,
        ];
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInteractiveCourses::route('/'),
            'create' => Pages\CreateInteractiveCourse::route('/create'),
            'edit' => Pages\EditInteractiveCourse::route('/{record}/edit'),
        ];
    }
}
