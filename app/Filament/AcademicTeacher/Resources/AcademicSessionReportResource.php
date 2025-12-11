<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource\Pages;
use App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource\RelationManagers;
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
    
    protected static ?string $navigationLabel = 'تقرير الجلسة';
    
    protected static ?string $modelLabel = 'تقرير الجلسة';
    
    protected static ?string $pluralModelLabel = 'تقارير الجلسات';
    
    protected static ?string $navigationGroup = 'التقييمات';

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

                Forms\Components\Section::make('تقييم الواجب')
                    ->schema([
                        Forms\Components\TextInput::make('homework_degree')
                            ->label('درجة الواجب (0-10)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.5)
                            ->helperText('تقييم جودة وإنجاز الواجب المنزلي'),
                    ]),

                Forms\Components\Section::make('الملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات المعلم')
                            ->placeholder('أضف ملاحظات حول أداء الطالب...')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Attendance Override (if needed)')
                    ->schema([
                        Forms\Components\Select::make('attendance_status')
                            ->label('Override Attendance Status')
                            ->options([
                                'attended' => 'حاضر',
                                'late' => 'متأخر',
                                'leaved' => 'غادر مبكراً',
                                'absent' => 'غائب',
                            ])
                            ->helperText('Only change if the automatic attendance calculation is incorrect'),
                        Forms\Components\Toggle::make('manually_evaluated')
                            ->label('Mark as Manually Evaluated')
                            ->helperText('Check this if you\'re overriding the automatic attendance'),
                        Forms\Components\Textarea::make('override_reason')
                            ->label('Reason for Override')
                            ->placeholder('Explain why you\'re overriding the automatic attendance...')
                            ->visible(fn (Forms\Get $get) => $get('manually_evaluated'))
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
                Tables\Columns\TextColumn::make('homework_degree')
                    ->label('درجة الواجب')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (?string $state): string => match (true) {
                        $state === null => 'gray',
                        (float) $state >= 8 => 'success',
                        (float) $state >= 6 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ? $state . '/10' : 'لم يقيم'),
                Tables\Columns\TextColumn::make('attendance_status')
                    ->label('Attendance')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'attended' => 'success',
                        'late' => 'warning',
                        'leaved' => 'info',
                        'absent' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('actual_attendance_minutes')
                    ->label('Duration')
                    ->formatStateUsing(fn (string $state): string => $state . ' min')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('manually_evaluated')
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
                        'attended' => 'حاضر',
                        'late' => 'متأخر',
                        'leaved' => 'غادر مبكراً',
                        'absent' => 'غائب',
                    ]),
                Tables\Filters\Filter::make('has_homework_grade')
                    ->label('تم تقييم الواجب')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('homework_degree')),
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
            'view' => Pages\ViewAcademicSessionReport::route('/{record}'),
            'edit' => Pages\EditAcademicSessionReport::route('/{record}/edit'),
        ];
    }
}
