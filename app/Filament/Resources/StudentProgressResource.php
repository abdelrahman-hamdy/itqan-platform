<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentProgressResource\Pages;
use App\Filament\Resources\StudentProgressResource\RelationManagers;
use App\Models\StudentProgress;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentProgressResource extends Resource
{
    protected static ?string $model = StudentProgress::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'تقدم الدورات المسجلة';

    protected static ?string $modelLabel = 'تقدم دورة مسجلة';

    protected static ?string $pluralModelLabel = 'تقدم الدورات المسجلة';

    protected static ?string $navigationGroup = 'إدارة الدورات المسجلة';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\Select::make('recorded_course_id')
                    ->relationship('recordedCourse', 'title')
                    ->required(),
                Forms\Components\TextInput::make('course_section_id')
                    ->numeric(),
                Forms\Components\Select::make('lesson_id')
                    ->relationship('lesson', 'title'),
                Forms\Components\TextInput::make('progress_type')
                    ->required(),
                Forms\Components\TextInput::make('progress_percentage')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('watch_time_seconds')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('total_time_seconds')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_completed')
                    ->required(),
                Forms\Components\DateTimePicker::make('completed_at'),
                Forms\Components\DateTimePicker::make('last_accessed_at'),
                Forms\Components\TextInput::make('current_position_seconds')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('quiz_score')
                    ->numeric(),
                Forms\Components\TextInput::make('quiz_attempts')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('notes'),
                Forms\Components\DateTimePicker::make('bookmarked_at'),
                Forms\Components\TextInput::make('rating')
                    ->numeric(),
                Forms\Components\Textarea::make('review_text')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recordedCourse.title')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course_section_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lesson.title')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('progress_type'),
                Tables\Columns\TextColumn::make('progress_percentage')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('watch_time_seconds')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_time_seconds')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_completed')
                    ->boolean(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_accessed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_position_seconds')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quiz_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quiz_attempts')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bookmarked_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating')
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
            'index' => Pages\ListStudentProgress::route('/'),
            'create' => Pages\CreateStudentProgress::route('/create'),
            'edit' => Pages\EditStudentProgress::route('/{record}/edit'),
        ];
    }
}
