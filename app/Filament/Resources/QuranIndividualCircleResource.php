<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuranIndividualCircleResource\Pages;
use App\Filament\Resources\QuranIndividualCircleResource\RelationManagers;
use App\Models\QuranIndividualCircle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuranIndividualCircleResource extends Resource
{
    protected static ?string $model = QuranIndividualCircle::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('academy_id')
                    ->relationship('academy', 'name')
                    ->required(),
                Forms\Components\Select::make('quran_teacher_id')
                    ->relationship('quranTeacher', 'name'),
                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'name')
                    ->required(),
                Forms\Components\Select::make('subscription_id')
                    ->relationship('subscription', 'id')
                    ->required(),
                Forms\Components\TextInput::make('circle_code')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('name')
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('specialization')
                    ->required(),
                Forms\Components\TextInput::make('memorization_level')
                    ->required(),
                Forms\Components\TextInput::make('total_sessions')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('sessions_scheduled')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('sessions_completed')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('sessions_remaining')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('current_surah')
                    ->numeric(),
                Forms\Components\TextInput::make('current_verse')
                    ->numeric(),
                Forms\Components\TextInput::make('verses_memorized')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('progress_percentage')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('default_duration_minutes')
                    ->required()
                    ->numeric()
                    ->default(45),
                Forms\Components\TextInput::make('preferred_times'),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\DateTimePicker::make('started_at'),
                Forms\Components\DateTimePicker::make('completed_at'),
                Forms\Components\DateTimePicker::make('last_session_at'),
                Forms\Components\TextInput::make('meeting_link')
                    ->maxLength(255),
                Forms\Components\TextInput::make('meeting_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('meeting_password')
                    ->password()
                    ->maxLength(255),
                Forms\Components\Toggle::make('recording_enabled')
                    ->required(),
                
                Forms\Components\TextInput::make('materials_used'),
                Forms\Components\TextInput::make('learning_objectives'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('teacher_notes')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('created_by')
                    ->numeric(),
                Forms\Components\TextInput::make('updated_by')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('academy.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quranTeacher.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subscription.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('circle_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('specialization'),
                Tables\Columns\TextColumn::make('memorization_level'),
                Tables\Columns\TextColumn::make('total_sessions')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sessions_scheduled')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sessions_completed')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sessions_remaining')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_surah')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_verse')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('verses_memorized')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('progress_percentage')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('default_duration_minutes')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_session_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('meeting_link')
                    ->searchable(),
                Tables\Columns\TextColumn::make('meeting_id')
                    ->searchable(),
                Tables\Columns\IconColumn::make('recording_enabled')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranIndividualCircles::route('/'),
            'create' => Pages\CreateQuranIndividualCircle::route('/create'),
            'edit' => Pages\EditQuranIndividualCircle::route('/{record}/edit'),
        ];
    }
}
