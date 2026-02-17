<?php

namespace App\Filament\Resources\QuizResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'أسئلة الاختبار';

    protected static ?string $modelLabel = 'سؤال';

    protected static ?string $pluralModelLabel = 'الأسئلة';

    /**
     * Normalize question data by converting UUID keys to integer indices.
     */
    protected function normalizeQuestionData(array $data): array
    {
        $options = $data['options'] ?? [];
        $selectedKey = $data['correct_option'] ?? null;

        // Build new options array with sequential integer keys
        // and track which index corresponds to the selected key
        $normalizedOptions = [];
        $correctIndex = 0;
        $counter = 0;

        foreach ($options as $key => $value) {
            $optionText = is_array($value) ? ($value['option'] ?? $value[0] ?? '') : $value;
            $normalizedOptions[] = $optionText;

            if ((string) $key === (string) $selectedKey) {
                $correctIndex = $counter;
            }
            $counter++;
        }

        $data['options'] = $normalizedOptions;
        $data['correct_option'] = $correctIndex;

        return $data;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('question_text')
                    ->label('نص السؤال')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                Repeater::make('options')
                    ->label('الخيارات')
                    ->simple(
                        TextInput::make('option')
                            ->required()
                            ->placeholder('أدخل نص الخيار')
                            ->live(onBlur: true),
                    )
                    ->minItems(2)
                    ->maxItems(6)
                    ->defaultItems(4)
                    ->columnSpanFull()
                    ->reorderable(false)
                    ->live()
                    ->afterStateHydrated(function (Repeater $component, $state) {
                        // Convert indexed array from DB to keyed array for form
                        if (is_array($state) && ! empty($state)) {
                            $keyed = [];
                            foreach (array_values($state) as $i => $value) {
                                $keyed[$i] = $value;
                            }
                            $component->state($keyed);
                        }
                    }),

                Select::make('correct_option')
                    ->label('الإجابة الصحيحة')
                    ->options(function (Get $get): array {
                        $options = $get('options') ?? [];
                        $result = [];
                        $counter = 0;
                        foreach ($options as $option) {
                            $text = is_array($option) ? ($option['option'] ?? $option[0] ?? '') : $option;
                            $displayIndex = $counter + 1;
                            $result[$counter] = "الخيار {$displayIndex}: ".($text ?: '(فارغ)');
                            $counter++;
                        }

                        return $result ?: [0 => 'أدخل الخيارات أولاً'];
                    })
                    ->live()
                    ->required()
                    ->helperText('اختر الإجابة الصحيحة من الخيارات أعلاه'),

                TextInput::make('order')
                    ->label('الترتيب')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question_text')
            ->reorderable('order')
            ->columns([
                TextColumn::make('order')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('question_text')
                    ->label('السؤال')
                    ->limit(50)
                    ->wrap()
                    ->searchable(),

                TextColumn::make('options')
                    ->label('عدد الخيارات')
                    ->formatStateUsing(function ($state) {
                        $options = is_string($state) ? json_decode($state, true) : $state;

                        return count($options ?? []).' خيارات';
                    }),

                TextColumn::make('correct_option')
                    ->label('الإجابة الصحيحة')
                    ->formatStateUsing(fn ($state) => 'الخيار '.((int) $state + 1)),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        return $this->normalizeQuestionData($data);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        return $this->normalizeQuestionData($data);
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order');
    }
}
