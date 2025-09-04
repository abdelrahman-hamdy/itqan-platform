<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\AcademicSessionReportResource\Pages;
use App\Filament\Teacher\Resources\AcademicSessionReportResource\RelationManagers;
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

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'Session Reports';
    
    protected static ?string $modelLabel = 'Session Report';
    
    protected static ?string $pluralModelLabel = 'Session Reports';
    
    protected static ?string $navigationGroup = 'Academic Sessions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Session Information')
                    ->schema([
                        Forms\Components\Select::make('academic_session_id')
                            ->relationship('academicSession', 'title', fn (Builder $query) => 
                                $query->whereHas('academicTeacher', fn ($q) => $q->where('user_id', auth()->id()))
                            )
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'name')
                            ->required()
                            ->searchable()
                            ->disabled(fn (?AcademicSessionReport $record) => $record !== null),
                    ])->columns(2),

                Forms\Components\Section::make('Academic Evaluation')
                    ->schema([
                        Forms\Components\TextInput::make('academic_performance_score')
                            ->label('Academic Performance (0-10)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.1)
                            ->helperText('Rate the student\'s academic performance during this session'),
                        Forms\Components\TextInput::make('engagement_score')
                            ->label('Student Engagement (0-10)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.1)
                            ->helperText('Rate how engaged the student was during the session'),
                        Forms\Components\TagsInput::make('learning_objectives_achieved')
                            ->label('Learning Objectives Achieved')
                            ->placeholder('Add objectives that were achieved in this session')
                            ->helperText('List the learning goals that the student successfully completed')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Homework Assignment')
                    ->schema([
                        Forms\Components\Textarea::make('homework_description')
                            ->label('Homework Assignment')
                            ->placeholder('Describe the homework assignment for the student...')
                            ->rows(4)
                            ->helperText('Clearly describe what the student needs to complete for next time')
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('homework_file_path')
                            ->label('Homework Materials')
                            ->directory('academic-homework')
                            ->acceptedFileTypes(['pdf', 'doc', 'docx', 'txt', 'jpg', 'png'])
                            ->maxSize(5120) // 5MB
                            ->downloadable()
                            ->openable()
                            ->helperText('Upload any materials or worksheets for the homework')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Teacher Notes & Feedback')
                    ->schema([
                        Forms\Components\Textarea::make('teacher_notes')
                            ->label('Teacher Observations')
                            ->placeholder('Record your observations about the student\'s progress, behavior, and areas for improvement...')
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('student_notes')
                            ->label('Student Self-Reflection Notes')
                            ->placeholder('Encourage student to reflect on their learning...')
                            ->rows(3)
                            ->helperText('Optional: Notes from student about their own learning')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Attendance Override (if needed)')
                    ->schema([
                        Forms\Components\Select::make('attendance_status')
                            ->label('Override Attendance Status')
                            ->options([
                                'present' => 'Present',
                                'late' => 'Late',
                                'partial' => 'Partial Attendance',
                                'absent' => 'Absent',
                            ])
                            ->helperText('Only change if the automatic attendance calculation is incorrect'),
                        Forms\Components\Toggle::make('manually_overridden')
                            ->label('Mark as Manually Overridden')
                            ->helperText('Check this if you\'re overriding the automatic attendance'),
                        Forms\Components\Textarea::make('override_reason')
                            ->label('Reason for Override')
                            ->placeholder('Explain why you\'re overriding the automatic attendance...')
                            ->visible(fn (Forms\Get $get) => $get('manually_overridden'))
                            ->columnSpanFull(),
                    ])->columns(2)
                    ->collapsed()
                    ->description('Expand this section only if you need to manually correct attendance'),
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
                Tables\Columns\TextColumn::make('academic_performance_score')
                    ->label('Performance')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (?string $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 8 => 'success',
                        $state >= 6 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ? $state . '/10' : 'Not graded'),
                Tables\Columns\TextColumn::make('engagement_score')
                    ->label('Engagement')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (?string $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 8 => 'success',
                        $state >= 6 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ? $state . '/10' : 'Not graded'),
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
                Tables\Columns\TextColumn::make('homework_description')
                    ->label('Homework')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Assigned' : 'None')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('homework_file_path')
                    ->label('Materials')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Uploaded' : 'None')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('actual_attendance_minutes')
                    ->label('Duration')
                    ->formatStateUsing(fn (string $state): string => $state . ' min')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('manually_overridden')
                    ->label('Override')
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
                Tables\Filters\Filter::make('has_homework')
                    ->label('Has Homework')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('homework_description')),
                Tables\Filters\Filter::make('graded')
                    ->label('Has Grade')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('academic_performance_score')),
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
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => 
                $query->whereHas('academicSession.academicTeacher', fn ($q) => $q->where('user_id', auth()->id()))
            );
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
