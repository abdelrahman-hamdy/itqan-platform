<?php

namespace App\Filament\Resources\QuizResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'أسئلة الاختبار';

    protected static ?string $modelLabel = 'سؤال';

    protected static ?string $pluralModelLabel = 'الأسئلة';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('question_text')
                    ->label('نص السؤال')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Repeater::make('options')
                    ->label('الخيارات')
                    ->simple(
                        Forms\Components\TextInput::make('option')
                            ->required()
                            ->placeholder('أدخل نص الخيار')
                            ->live(onBlur: true),
                    )
                    ->minItems(2)
                    ->maxItems(6)
                    ->defaultItems(4)
                    ->columnSpanFull()
                    ->reorderable(false)
                    ->live(),

                Forms\Components\Select::make('correct_option')
                    ->label('الإجابة الصحيحة')
                    ->options(function (Forms\Get $get): array {
                        $options = $get('options') ?? [];
                        $result = [];
                        foreach ($options as $index => $option) {
                            $text = is_array($option) ? ($option['option'] ?? $option[0] ?? '') : $option;
                            $displayIndex = $index + 1;
                            $result[$index] = "الخيار {$displayIndex}: " . ($text ?: '(فارغ)');
                        }
                        return $result ?: [0 => 'أدخل الخيارات أولاً'];
                    })
                    ->live()
                    ->required()
                    ->helperText('اختر الإجابة الصحيحة من الخيارات أعلاه'),

                Forms\Components\TextInput::make('order')
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
                Tables\Columns\TextColumn::make('order')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('question_text')
                    ->label('السؤال')
                    ->limit(50)
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('options')
                    ->label('عدد الخيارات')
                    ->formatStateUsing(fn ($state) => count($state ?? []) . ' خيارات'),

                Tables\Columns\TextColumn::make('correct_option')
                    ->label('الإجابة الصحيحة')
                    ->formatStateUsing(fn ($state) => 'الخيار ' . ($state + 1)),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order');
    }
}
