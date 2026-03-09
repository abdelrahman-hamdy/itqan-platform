<?php

namespace App\Filament\Shared\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use App\Filament\Resources\BaseResource;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base Quiz Assignment Resource
 *
 * Shared functionality for Teacher and AcademicTeacher panels.
 * Child classes must implement assignable type configuration, query filtering, and getPages().
 */
abstract class BaseQuizAssignmentResource extends BaseResource
{
    protected static ?string $model = QuizAssignment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-plus';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('teacher.quizzes.nav_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('teacher.quiz_assignments.nav_label');
    }

    public static function getModelLabel(): string
    {
        return __('teacher.quiz_assignments.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('teacher.quiz_assignments.model_label_plural');
    }

    // Disable automatic tenant scoping - we filter by teacher in getEloquentQuery()
    protected static bool $isScopedToTenant = false;

    /**
     * Get the assignable types with their labels.
     *
     * @return array<string, string> [ModelClass => Label]
     */
    abstract protected static function getAssignableTypes(): array;

    /**
     * Get options for the assignable_id select based on selected type.
     *
     * @param  string|null  $type  The selected assignable_type
     * @return array<int|string, string> Options for the select
     */
    abstract protected static function getAssignableOptions(?string $type): array;

    /**
     * Get the current teacher's ID for filtering.
     */
    abstract protected static function getTeacherId(): ?int;

    /**
     * Get the IDs for query filtering based on assignable type.
     * Returns an array of [type => ids[]] pairs.
     *
     * @return array<string, array<int>> [AssignableClass => [ids]]
     */
    abstract protected static function getTeacherAssignableIds(): array;

    /**
     * Format the assignable name for display in the table.
     */
    abstract protected static function formatAssignableName($record): string;

    /**
     * Get the label for assignable type select field.
     */
    protected static function getAssignableTypeLabel(): string
    {
        return __('teacher.quizzes.assignable_type_label');
    }

    /**
     * Get the label for assignable target select field.
     */
    protected static function getAssignableTargetLabel(): string
    {
        return __('teacher.quizzes.assignable_target_label');
    }

    public static function form(Schema $schema): Schema
    {
        $tenant = Filament::getTenant();

        return $schema
            ->components([
                Section::make(__('teacher.quiz_assignments.assignment_section'))
                    ->schema([
                        Select::make('quiz_id')
                            ->label(__('teacher.quiz_assignments.quiz_select'))
                            ->options(function () use ($tenant) {
                                $query = Quiz::active();
                                if ($tenant) {
                                    $query->where('academy_id', $tenant->id);
                                }

                                return $query->pluck('title', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('assignable_type')
                            ->label(static::getAssignableTypeLabel())
                            ->options(static::getAssignableTypes())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('assignable_id', null)),

                        Select::make('assignable_id')
                            ->label(static::getAssignableTargetLabel())
                            ->options(fn (Get $get) => static::getAssignableOptions($get('assignable_type')))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (Get $get) => ! $get('assignable_type')),
                    ])
                    ->columns(2),

                Section::make(__('teacher.quiz_assignments.availability_section'))
                    ->schema([
                        Toggle::make('is_visible')
                            ->label(__('teacher.quizzes.visible_to_students'))
                            ->default(true)
                            ->helperText(__('teacher.quiz_assignments.hide_quiz_hint')),

                        TextInput::make('max_attempts')
                            ->label(__('teacher.quiz_assignments.max_attempts_allowed'))
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(10)
                            ->required(),

                        DateTimePicker::make('available_from')
                            ->label(__('teacher.quizzes.available_from_label'))
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('Y-m-d H:i')
                            ->placeholder(__('teacher.quizzes.available_from_placeholder'))
                            ->helperText(__('teacher.quiz_assignments.available_from_hint')),

                        DateTimePicker::make('available_until')
                            ->label(__('teacher.quizzes.available_until_label'))
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('Y-m-d H:i')
                            ->placeholder(__('teacher.quizzes.available_until_placeholder'))
                            ->after('available_from')
                            ->helperText(__('teacher.quiz_assignments.available_until_hint')),
                    ])
                    ->columns(2),
            ]);
    }

    protected static function getTableColumns(): array
    {
        return [
            static::getAcademyColumn(),

            TextColumn::make('quiz.title')
                ->label(__('teacher.quiz_assignments.column_quiz'))
                ->searchable()
                ->sortable(),

            TextColumn::make('assignable_type')
                ->label(static::getAssignableTypeLabel())
                ->formatStateUsing(fn ($state) => static::getAssignableTypes()[$state] ?? $state),

            TextColumn::make('assignable')
                ->label(static::getAssignableTargetLabel())
                ->formatStateUsing(fn ($record) => static::formatAssignableName($record)),

            IconColumn::make('is_visible')
                ->label(__('teacher.quiz_assignments.column_visible'))
                ->boolean(),

            TextColumn::make('max_attempts')
                ->label(__('teacher.quiz_assignments.column_attempts'))
                ->sortable(),

            TextColumn::make('attempts_count')
                ->label(__('teacher.quiz_assignments.column_submissions'))
                ->counts('attempts'),

            TextColumn::make('available_from')
                ->label(__('teacher.quiz_assignments.column_available_from'))
                ->dateTime('Y-m-d H:i')
                ->placeholder(__('teacher.quiz_assignments.placeholder_immediate')),

            TextColumn::make('available_until')
                ->label(__('teacher.quiz_assignments.column_available_until'))
                ->dateTime('Y-m-d H:i')
                ->placeholder(__('teacher.quiz_assignments.placeholder_permanent')),

            TextColumn::make('created_at')
                ->label(__('teacher.quizzes.column_created_at'))
                ->dateTime('Y-m-d')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->filters([
                SelectFilter::make('assignable_type')
                    ->label(static::getAssignableTypeLabel())
                    ->options(static::getAssignableTypes())
                    ->placeholder(__('teacher.quizzes.filter_all')),

                TernaryFilter::make('is_visible')
                    ->label(__('teacher.quiz_assignments.filter_visibility'))
                    ->placeholder(__('teacher.quizzes.filter_all'))
                    ->trueLabel(__('teacher.quiz_assignments.filter_visible'))
                    ->falseLabel(__('teacher.quiz_assignments.filter_hidden')),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label(__('teacher.quizzes.action_view')),
                    EditAction::make()->label(__('teacher.quizzes.action_edit')),
                    DeleteAction::make()->label(__('teacher.quizzes.action_delete')),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();
        $teacherId = static::getTeacherId();

        $query = parent::getEloquentQuery()->with(['quiz', 'assignable']);

        // Filter by teacher's assignables using subqueries for better performance
        if ($teacherId) {
            $assignableIds = static::getTeacherAssignableIds();

            // Guard: if teacher has no assignables, return empty result set
            if (empty($assignableIds)) {
                return $query->whereRaw('1 = 0');
            }

            $query->where(function ($q) use ($assignableIds) {
                $first = true;
                foreach ($assignableIds as $type => $ids) {
                    if ($first) {
                        $q->where(function ($subQ) use ($type, $ids) {
                            $subQ->where('assignable_type', $type)
                                ->whereIn('assignable_id', $ids);
                        });
                        $first = false;
                    } else {
                        $q->orWhere(function ($subQ) use ($type, $ids) {
                            $subQ->where('assignable_type', $type)
                                ->whereIn('assignable_id', $ids);
                        });
                    }
                }
            });
        }

        // Also filter by academy
        if ($tenant) {
            $query->whereHas('quiz', function ($q) use ($tenant) {
                $q->where('academy_id', $tenant->id);
            });
        }

        return $query;
    }
}
