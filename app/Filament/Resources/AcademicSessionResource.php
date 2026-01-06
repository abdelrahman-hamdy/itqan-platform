<?php

namespace App\Filament\Resources;

use App\Enums\SessionStatus;
use App\Filament\Resources\AcademicSessionResource\Pages;
use App\Filament\Shared\Resources\BaseAcademicSessionResource;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Academic Session Resource for SuperAdmin Panel
 *
 * Full CRUD access with soft delete support.
 * Extends BaseAcademicSessionResource for shared form/table definitions.
 */
class AcademicSessionResource extends BaseAcademicSessionResource
{
    // ========================================
    // Tenant Configuration
    // ========================================

    protected static ?string $tenantOwnershipRelationshipName = 'academy';

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }

    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationLabel = 'الجلسات الأكاديمية';

    protected static ?string $navigationGroup = 'إدارة التعليم الأكاديمي';

    protected static ?int $navigationSort = 2;

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
     * Session info section with teacher and student selection.
     */
    protected static function getSessionInfoFormSection(): Section
    {
        return Section::make('معلومات الجلسة')
            ->schema([
                // Hidden academy_id - auto-set from context
                Forms\Components\Hidden::make('academy_id')
                    ->default(fn () => auth()->user()->academy_id),

                Forms\Components\TextInput::make('session_code')
                    ->label('رمز الجلسة')
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\Select::make('status')
                    ->label('حالة الجلسة')
                    ->options(SessionStatus::options())
                    ->default(SessionStatus::SCHEDULED->value)
                    ->required(),

                Forms\Components\Hidden::make('session_type')
                    ->default('individual'),

                Forms\Components\Select::make('academic_teacher_id')
                    ->relationship('academicTeacher', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->user ? trim(($record->user->first_name ?? '').' '.($record->user->last_name ?? '')) ?: 'معلم #'.$record->id : 'معلم #'.$record->id)
                    ->label('المعلم')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->disabled(fn ($record) => $record !== null)
                    ->dehydrated(),

                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => trim(($record->first_name ?? '').' '.($record->last_name ?? '')) ?: 'طالب #'.$record->id)
                    ->label('الطالب')
                    ->searchable()
                    ->preload()
                    ->disabled(fn ($record) => $record !== null)
                    ->dehydrated(),

                Forms\Components\Hidden::make('academic_subscription_id'),
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
     * Notes section for SuperAdmin.
     */
    protected static function getAdditionalFormSections(): array
    {
        return [
            Section::make('ملاحظات')
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
                ]),
        ];
    }

    // ========================================
    // Table Filters Override (SuperAdmin-specific)
    // ========================================

    /**
     * Extended filters with teacher, student, individual lesson, date range, and trashed.
     */
    protected static function getTableFilters(): array
    {
        return [
            ...parent::getTableFilters(),

            Tables\Filters\SelectFilter::make('academic_teacher_id')
                ->label(__('filament.teacher'))
                ->relationship('academicTeacher.user', 'name')
                ->searchable()
                ->preload(),

            Tables\Filters\SelectFilter::make('student_id')
                ->label(__('filament.student'))
                ->relationship('student', 'name')
                ->searchable()
                ->preload(),

            Tables\Filters\SelectFilter::make('academic_individual_lesson_id')
                ->label('الدرس الفردي')
                ->relationship('academicIndividualLesson', 'name')
                ->searchable()
                ->preload(),

            Filter::make('scheduled_at')
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
                            fn (Builder $query, $date): Builder => $query->whereDate('scheduled_at', '>=', $date),
                        )
                        ->when(
                            $data['until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('scheduled_at', '<=', $date),
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
    // Table Columns Override (SuperAdmin-specific)
    // ========================================

    /**
     * Table columns with teacher column.
     */
    protected static function getTableColumns(): array
    {
        $columns = parent::getTableColumns();

        // Insert teacher column after title
        $teacherColumn = Tables\Columns\TextColumn::make('academicTeacher.user.id')
            ->label('المعلم')
            ->formatStateUsing(fn ($record) => $record->academicTeacher?->user
                    ? trim(($record->academicTeacher->user->first_name ?? '').' '.($record->academicTeacher->user->last_name ?? '')) ?: 'معلم #'.$record->academicTeacher->id
                    : 'معلم #'.($record->academic_teacher_id ?? '-')
            )
            ->searchable();

        // Find position after title column and insert
        $result = [];
        foreach ($columns as $column) {
            $result[] = $column;
            if ($column->getName() === 'title') {
                $result[] = $teacherColumn;
            }
        }

        return $result;
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicSessions::route('/'),
            'create' => Pages\CreateAcademicSession::route('/create'),
            'view' => Pages\ViewAcademicSession::route('/{record}'),
            'edit' => Pages\EditAcademicSession::route('/{record}/edit'),
        ];
    }
}
