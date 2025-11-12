<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InteractiveCourseProgressResource\Pages;
use App\Filament\Resources\InteractiveCourseProgressResource\RelationManagers;
use App\Models\InteractiveCourseProgress;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InteractiveCourseProgressResource extends Resource
{
    protected static ?string $model = InteractiveCourseProgress::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'تقدم الدورات التفاعلية';

    protected static ?string $modelLabel = 'تقدم دورة تفاعلية';

    protected static ?string $pluralModelLabel = 'تقدم الدورات التفاعلية';

    protected static ?string $navigationGroup = 'متابعة التقدم';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('academy_id')
                    ->relationship('academy', 'name')
                    ->required(),
                Forms\Components\Select::make('course_id')
                    ->relationship('course', 'title')
                    ->required(),
                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'name')
                    ->required(),
                Forms\Components\TextInput::make('total_sessions')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('sessions_attended')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('sessions_completed')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('attendance_percentage')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('homework_assigned')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('homework_submitted')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('homework_graded')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('average_homework_score')
                    ->numeric(),
                Forms\Components\TextInput::make('overall_score')
                    ->numeric(),
                Forms\Components\TextInput::make('progress_status')
                    ->required(),
                Forms\Components\TextInput::make('completion_percentage')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\DateTimePicker::make('started_at'),
                Forms\Components\DateTimePicker::make('completed_at'),
                Forms\Components\DateTimePicker::make('last_activity_at'),
                Forms\Components\TextInput::make('days_since_last_activity')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_at_risk')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('academy.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course.title')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_sessions')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sessions_attended')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sessions_completed')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendance_percentage')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('homework_assigned')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('homework_submitted')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('homework_graded')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('average_homework_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('overall_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('progress_status'),
                Tables\Columns\TextColumn::make('completion_percentage')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_activity_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('days_since_last_activity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_at_risk')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
            'index' => Pages\ListInteractiveCourseProgress::route('/'),
            'create' => Pages\CreateInteractiveCourseProgress::route('/create'),
            'edit' => Pages\EditInteractiveCourseProgress::route('/{record}/edit'),
        ];
    }
}
