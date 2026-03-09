<?php

namespace App\Filament\Shared\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Resources\BaseResource;
use App\Models\AcademicIndividualLesson;
use App\Models\InteractiveCourse;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base Quiz Resource
 *
 * Shared functionality for Teacher and AcademicTeacher panels.
 * Child classes must implement assignable type configuration and getPages().
 */
abstract class BaseQuizResource extends BaseResource
{
    protected static ?string $model = Quiz::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function getNavigationGroup(): ?string
    {
        return __('teacher.quizzes.nav_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('teacher.quizzes.nav_my_quizzes');
    }

    public static function getModelLabel(): string
    {
        return __('teacher.quizzes.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('teacher.quizzes.model_label_plural');
    }

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
        return $schema
            ->components([
                Section::make(__('teacher.quizzes.quiz_info'))
                    ->schema([
                        TextInput::make('title')
                            ->label(__('teacher.quizzes.field_title'))
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label(__('teacher.quizzes.field_description'))
                            ->rows(3)
                            ->maxLength(1000),

                        TextInput::make('duration_minutes')
                            ->label(__('teacher.quizzes.duration_minutes_label'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(180)
                            ->helperText(__('teacher.quizzes.duration_helper')),

                        TextInput::make('passing_score')
                            ->label(__('teacher.quizzes.passing_score_percent'))
                            ->numeric()
                            ->default(60)
                            ->minValue(10)
                            ->maxValue(90)
                            ->required()
                            ->suffix('%')
                            ->helperText(__('teacher.quizzes.passing_score_helper')),

                        Toggle::make('is_active')
                            ->label(__('teacher.quizzes.active_label'))
                            ->default(true),

                        Toggle::make('randomize_questions')
                            ->label(__('teacher.quizzes.randomize_label_form'))
                            ->helperText(__('teacher.quizzes.randomize_helper'))
                            ->default(false),
                    ])
                    ->columns(2),

                Section::make(__('teacher.quizzes.questions_section'))
                    ->schema([
                        Repeater::make('questions')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Textarea::make('question_text')
                                    ->label(__('teacher.quizzes.question_text_label'))
                                    ->required()
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Repeater::make('options')
                                    ->label(__('teacher.quizzes.options_label'))
                                    ->simple(
                                        TextInput::make('option')
                                            ->required()
                                            ->placeholder(__('teacher.quizzes.option_input_placeholder'))
                                            ->live(onBlur: true),
                                    )
                                    ->minItems(2)
                                    ->maxItems(6)
                                    ->defaultItems(4)
                                    ->columnSpanFull()
                                    ->reorderable(false)
                                    ->live(),

                                Select::make('correct_option')
                                    ->label(__('teacher.quizzes.correct_answer_label'))
                                    ->options(function (Get $get): array {
                                        $options = $get('options') ?? [];
                                        $result = [];
                                        $counter = 0;
                                        foreach ($options as $option) {
                                            $text = is_array($option) ? ($option['option'] ?? $option[0] ?? '') : $option;
                                            $displayIndex = $counter + 1;
                                            $result[$counter] = __('teacher.quizzes.option_prefix') . " {$displayIndex}: ".($text ?: __('teacher.quizzes.option_empty'));
                                            $counter++;
                                        }

                                        return $result ?: [0 => __('teacher.quizzes.enter_options_first')];
                                    })
                                    ->live()
                                    ->required()
                                    ->helperText(__('teacher.quizzes.correct_answer_helper')),

                                Hidden::make('order')
                                    ->default(0),
                            ])
                            ->orderColumn('order')
                            ->defaultItems(1)
                            ->addActionLabel(__('teacher.quizzes.add_question_action'))
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => $state['question_text'] ?? __('teacher.quizzes.new_question')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getAcademyColumn(),

                TextColumn::make('title')
                    ->label(__('teacher.quizzes.column_title'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('questions_count')
                    ->label(__('teacher.quizzes.column_questions_count'))
                    ->counts('questions')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('duration_minutes')
                    ->label(__('teacher.quizzes.column_duration'))
                    ->formatStateUsing(fn ($state) => $state ? __('teacher.quizzes.duration_x_minutes', ['count' => $state]) : __('teacher.quizzes.duration_not_set'))
                    ->toggleable(),

                TextColumn::make('passing_score')
                    ->label(__('teacher.quizzes.column_passing_score'))
                    ->formatStateUsing(fn ($state) => "{$state}%")
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label(__('teacher.quizzes.column_active'))
                    ->boolean(),

                IconColumn::make('randomize_questions')
                    ->label(__('teacher.quizzes.column_randomize'))
                    ->boolean()
                    ->trueIcon('heroicon-o-arrows-right-left')
                    ->falseIcon('heroicon-o-bars-3')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                TextColumn::make('assignments_count')
                    ->label(__('teacher.quizzes.column_assignments_count'))
                    ->counts('assignments')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label(__('teacher.quizzes.column_created_at'))
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('teacher.quizzes.filter_status'))
                    ->placeholder(__('teacher.quizzes.filter_all'))
                    ->trueLabel(__('teacher.quizzes.active'))
                    ->falseLabel(__('teacher.quizzes.inactive')),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordActions([
                static::getAssignAction(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Get the assign action with panel-specific configuration.
     */
    protected static function getAssignAction(): Action
    {
        return Action::make('assign')
            ->label(__('teacher.quizzes.action_assign'))
            ->icon('heroicon-o-link')
            ->color('success')
            ->schema([
                Select::make('assignable_type')
                    ->label(static::getAssignableTypeLabel())
                    ->options(static::getAssignableTypes())
                    ->required()
                    ->live(),

                Select::make('assignable_id')
                    ->label(static::getAssignableTargetLabel())
                    ->options(fn (Get $get) => static::getAssignableOptions($get('assignable_type')))
                    ->required()
                    ->searchable()
                    ->preload(),

                Toggle::make('is_visible')
                    ->label(__('teacher.quizzes.visible_to_students'))
                    ->default(true),

                TextInput::make('max_attempts')
                    ->label(__('teacher.quizzes.max_attempts_field'))
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->maxValue(10),

                DateTimePicker::make('available_from')
                    ->label(__('teacher.quizzes.available_from_label'))
                    ->native(false)
                    ->seconds(false)
                    ->displayFormat('Y-m-d H:i')
                    ->placeholder(__('teacher.quizzes.available_from_placeholder')),

                DateTimePicker::make('available_until')
                    ->label(__('teacher.quizzes.available_until_label'))
                    ->native(false)
                    ->seconds(false)
                    ->displayFormat('Y-m-d H:i')
                    ->placeholder(__('teacher.quizzes.available_until_placeholder'))
                    ->after('available_from'),
            ])
            ->action(function (Quiz $record, array $data) {
                $teacherId = auth()->id();
                $assignableType = $data['assignable_type'];
                $assignableId = (int) $data['assignable_id'];

                // Verify the assignable belongs to the current teacher before creating assignment
                $academicTeacherProfileId = auth()->user()->academicTeacherProfile?->id;

                $ownershipVerified = match (true) {
                    $assignableType === QuranCircle::class,
                    $assignableType === \App\Enums\QuizAssignableType::QURAN_CIRCLE->value =>
                        QuranCircle::where('id', $assignableId)
                            ->where('quran_teacher_id', $teacherId)
                            ->exists(),
                    $assignableType === QuranIndividualCircle::class,
                    $assignableType === \App\Enums\QuizAssignableType::QURAN_INDIVIDUAL_CIRCLE->value =>
                        QuranIndividualCircle::where('id', $assignableId)
                            ->where('quran_teacher_id', $teacherId)
                            ->exists(),
                    $assignableType === AcademicIndividualLesson::class,
                    $assignableType === \App\Enums\QuizAssignableType::ACADEMIC_INDIVIDUAL_LESSON->value =>
                        $academicTeacherProfileId !== null && AcademicIndividualLesson::where('id', $assignableId)
                            ->where('academic_teacher_id', $academicTeacherProfileId)
                            ->exists(),
                    $assignableType === InteractiveCourse::class,
                    $assignableType === \App\Enums\QuizAssignableType::INTERACTIVE_COURSE->value =>
                        $academicTeacherProfileId !== null && InteractiveCourse::where('id', $assignableId)
                            ->where('assigned_teacher_id', $academicTeacherProfileId)
                            ->exists(),
                    default => false,
                };

                if (! $ownershipVerified) {
                    Notification::make()
                        ->title(__('filament-panels::pages/auth/login.messages.failed'))
                        ->danger()
                        ->send();

                    return;
                }

                QuizAssignment::create([
                    'quiz_id' => $record->id,
                    'assignable_type' => $assignableType,
                    'assignable_id' => $assignableId,
                    'is_visible' => $data['is_visible'],
                    'max_attempts' => $data['max_attempts'],
                    'available_from' => $data['available_from'] ?? null,
                    'available_until' => $data['available_until'] ?? null,
                ]);
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();
        $academyId = $tenant?->id ?? \App\Services\AcademyContextService::getCurrentAcademyId();

        if (! $academyId) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
            ->where('academy_id', $academyId);
    }
}
