<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentProfileResource\Pages;
use App\Filament\Resources\StudentProfileResource\RelationManagers;
use App\Models\StudentProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentProfileResource extends Resource
{
    protected static ?string $model = StudentProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\TextInput::make('student_code')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('grade_level_id')
                    ->relationship('gradeLevel', 'name'),
                Forms\Components\DatePicker::make('birth_date'),
                Forms\Components\TextInput::make('gender'),
                Forms\Components\TextInput::make('nationality')
                    ->maxLength(50),
                Forms\Components\TextInput::make('parent_id')
                    ->numeric(),
                Forms\Components\Textarea::make('address')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('emergency_contact')
                    ->maxLength(20),
                Forms\Components\DatePicker::make('enrollment_date'),
                Forms\Components\DatePicker::make('graduation_date'),
                Forms\Components\TextInput::make('academic_status')
                    ->required(),
                Forms\Components\Textarea::make('notes')
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
                Tables\Columns\TextColumn::make('student_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gradeLevel.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('birth_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gender'),
                Tables\Columns\TextColumn::make('nationality')
                    ->searchable(),
                Tables\Columns\TextColumn::make('parent_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('emergency_contact')
                    ->searchable(),
                Tables\Columns\TextColumn::make('enrollment_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('graduation_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('academic_status'),
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
            'index' => Pages\ListStudentProfiles::route('/'),
            'create' => Pages\CreateStudentProfile::route('/create'),
            'edit' => Pages\EditStudentProfile::route('/{record}/edit'),
        ];
    }
}
