<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\HomeworkSubmissionResource\Pages;
use App\Models\HomeworkSubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HomeworkSubmissionResource extends BaseTeacherResource
{
    protected static ?string $model = HomeworkSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'الواجبات المقدمة';

    protected static ?string $modelLabel = 'واجب مقدم';

    protected static ?string $pluralModelLabel = 'الواجبات المقدمة';

    protected static ?string $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 3;

    /**
     * Override query to show only submissions for Quran teacher's students
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Filter by Quran session type
        $query->where('submitable_type', 'App\\Models\\QuranSession');

        return $query;
    }

    /**
     * Teachers can grade homework but not create submissions
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الواجب')
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'name')
                            ->label('الطالب')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('submission_code')
                            ->label('كود التسليم')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'pending' => 'قيد المراجعة',
                                'graded' => 'تم التصحيح',
                                'returned' => 'تم الإرجاع',
                            ])
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('محتوى الواجب')
                    ->schema([
                        Forms\Components\Textarea::make('content')
                            ->label('المحتوى')
                            ->rows(5)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('file_path')
                            ->label('ملف الواجب')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('submitted_at')
                            ->label('تاريخ التسليم')
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Forms\Components\Section::make('التقييم والتصحيح')
                    ->schema([
                        Forms\Components\TextInput::make('grade')
                            ->label('الدرجة')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('من 100'),

                        Forms\Components\DateTimePicker::make('graded_at')
                            ->label('تاريخ التصحيح')
                            ->default(now()),

                        Forms\Components\Textarea::make('teacher_feedback')
                            ->label('ملاحظات المعلم')
                            ->placeholder('أضف تغذية راجعة للطالب...')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('submission_code')
                    ->label('كود التسليم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'graded' => 'success',
                        'returned' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'قيد المراجعة',
                        'graded' => 'تم التصحيح',
                        'returned' => 'تم الإرجاع',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('grade')
                    ->label('الدرجة')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (?string $state): string => match (true) {
                        $state === null => 'gray',
                        (float) $state >= 80 => 'success',
                        (float) $state >= 60 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ? $state . '/100' : 'غير مصحح'),

                Tables\Columns\IconColumn::make('file_path')
                    ->label('ملف مرفق')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-document')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('تاريخ التسليم')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('graded_at')
                    ->label('تاريخ التصحيح')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد المراجعة',
                        'graded' => 'تم التصحيح',
                        'returned' => 'تم الإرجاع',
                    ]),

                Tables\Filters\Filter::make('has_file')
                    ->label('به ملف مرفق')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('file_path')),

                Tables\Filters\Filter::make('graded')
                    ->label('تم التصحيح')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('grade')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk actions disabled for safety
                ]),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomeworkSubmissions::route('/'),
            'view' => Pages\ViewHomeworkSubmission::route('/{record}'),
            'edit' => Pages\EditHomeworkSubmission::route('/{record}/edit'),
        ];
    }
}
