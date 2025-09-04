<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicSessionReportResource\Pages;
use App\Filament\Resources\AcademicSessionReportResource\RelationManagers;
use App\Models\AcademicSessionReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicSessionReportResource extends Resource
{
    protected static ?string $model = AcademicSessionReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationLabel = 'Academic Session Reports';
    
    protected static ?string $modelLabel = 'Academic Session Report';
    
    protected static ?string $pluralModelLabel = 'Academic Session Reports';
    
    protected static ?string $navigationGroup = 'Academic Sessions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Session Information')
                    ->schema([
                        Forms\Components\Select::make('academic_session_id')
                            ->relationship('academicSession', 'title')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('teacher_id')
                            ->relationship('teacher', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('academy_id')
                            ->relationship('academy', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Section::make('Academic Performance')
                    ->schema([
                        Forms\Components\TextInput::make('academic_performance_score')
                            ->label('Academic Performance (0-10)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.1),
                        Forms\Components\TextInput::make('engagement_score')
                            ->label('Engagement Score (0-10)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.1),
                        Forms\Components\TagsInput::make('learning_objectives_achieved')
                            ->label('Learning Objectives Achieved')
                            ->placeholder('Add learning objectives')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Homework Management')
                    ->schema([
                        Forms\Components\Textarea::make('homework_description')
                            ->label('Homework Assignment')
                            ->placeholder('Describe the homework assignment...')
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('homework_file_path')
                            ->label('Homework File')
                            ->directory('academic-homework')
                            ->acceptedFileTypes(['pdf', 'doc', 'docx', 'txt', 'jpg', 'png'])
                            ->maxSize(5120) // 5MB
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Notes & Feedback')
                    ->schema([
                        Forms\Components\Textarea::make('teacher_notes')
                            ->label('Teacher Notes')
                            ->placeholder('Teacher observations and notes...')
                            ->rows(3),
                        Forms\Components\Textarea::make('student_notes')
                            ->label('Student Notes')
                            ->placeholder('Student self-reflection notes...')
                            ->rows(3),
                    ])->columns(2),

                Forms\Components\Section::make('Attendance Details')
                    ->schema([
                        Forms\Components\DateTimePicker::make('meeting_enter_time')
                            ->label('Meeting Join Time'),
                        Forms\Components\DateTimePicker::make('meeting_leave_time')
                            ->label('Meeting Leave Time'),
                        Forms\Components\TextInput::make('actual_attendance_minutes')
                            ->label('Attendance Minutes')
                            ->numeric()
                            ->default(0)
                            ->suffix('minutes'),
                        Forms\Components\Toggle::make('is_late')
                            ->label('Student Was Late'),
                        Forms\Components\TextInput::make('late_minutes')
                            ->label('Late Minutes')
                            ->numeric()
                            ->default(0)
                            ->suffix('minutes')
                            ->visible(fn (Forms\Get $get) => $get('is_late')),
                        Forms\Components\Select::make('attendance_status')
                            ->label('Attendance Status')
                            ->options([
                                'present' => 'Present',
                                'late' => 'Late',
                                'partial' => 'Partial',
                                'absent' => 'Absent',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('attendance_percentage')
                            ->label('Attendance Percentage')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(0),
                    ])->columns(3),

                Forms\Components\Section::make('System Information')
                    ->schema([
                        Forms\Components\DateTimePicker::make('evaluated_at')
                            ->label('Evaluated At'),
                        Forms\Components\Toggle::make('is_auto_calculated')
                            ->label('Auto Calculated')
                            ->default(true),
                        Forms\Components\Toggle::make('manually_overridden')
                            ->label('Manually Overridden'),
                        Forms\Components\Textarea::make('override_reason')
                            ->label('Override Reason')
                            ->visible(fn (Forms\Get $get) => $get('manually_overridden'))
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('academicSession.title')
                    ->label('Session')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Teacher')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('academy.name')
                    ->label('Academy')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('academic_performance_score')
                    ->label('Performance')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state >= 8 => 'success',
                        $state >= 6 => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('engagement_score')
                    ->label('Engagement')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state >= 8 => 'success',
                        $state >= 6 => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('attendance_status')
                    ->label('Attendance')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'present' => 'success',
                        'late' => 'warning',
                        'partial' => 'info',
                        'absent' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('attendance_percentage')
                    ->label('Attendance %')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => $state . '%'),
                Tables\Columns\TextColumn::make('actual_attendance_minutes')
                    ->label('Duration (min)')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_late')
                    ->label('Late')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('homework_description')
                    ->label('Has Homework')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Yes' : 'No')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'success' : 'gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('homework_file_path')
                    ->label('Homework File')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Uploaded' : 'None')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'success' : 'gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('meeting_enter_time')
                    ->label('Join Time')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('meeting_leave_time')
                    ->label('Leave Time')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('manually_overridden')
                    ->label('Manual Override')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('evaluated_at')
                    ->label('Evaluated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('attendance_status')
                    ->label('Attendance Status')
                    ->options([
                        'present' => 'Present',
                        'late' => 'Late',
                        'partial' => 'Partial',
                        'absent' => 'Absent',
                    ]),
                Tables\Filters\SelectFilter::make('academy_id')
                    ->label('Academy')
                    ->relationship('academy', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('has_homework')
                    ->label('Has Homework')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('homework_description')),
                Tables\Filters\Filter::make('has_homework_file')
                    ->label('Has Homework File')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('homework_file_path')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListAcademicSessionReports::route('/'),
            'create' => Pages\CreateAcademicSessionReport::route('/create'),
            'edit' => Pages\EditAcademicSessionReport::route('/{record}/edit'),
        ];
    }
}
