<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomeworkSubmissionResource\Pages;
use App\Filament\Resources\HomeworkSubmissionResource\RelationManagers;
use App\Models\HomeworkSubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\SubscriptionStatus;

class HomeworkSubmissionResource extends Resource
{
    protected static ?string $model = HomeworkSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'الواجبات المقدمة';

    protected static ?string $modelLabel = 'واجب مقدم';

    protected static ?string $pluralModelLabel = 'الواجبات المقدمة';

    protected static ?string $navigationGroup = 'التقارير والحضور';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['student', 'academy', 'grader']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الواجب')
                    ->schema([
                        Forms\Components\Select::make('academy_id')
                            ->relationship('academy', 'name')
                            ->label('الأكاديمية')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'name')
                            ->label('الطالب')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('submitable_type')
                            ->label('نوع الواجب')
                            ->required()
                            ->maxLength(255)
                            ->disabled(),
                        Forms\Components\TextInput::make('submitable_id')
                            ->label('معرف الواجب')
                            ->required()
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('submission_code')
                            ->label('كود التسليم')
                            ->required()
                            ->maxLength(255)
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                SubscriptionStatus::PENDING->value => 'قيد المراجعة',
                                'graded' => 'تم التصحيح',
                                'returned' => 'تم الإرجاع',
                            ])
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('محتوى الواجب')
                    ->schema([
                        Forms\Components\Textarea::make('content')
                            ->label('المحتوى')
                            ->placeholder('محتوى الواجب المقدم من الطالب...')
                            ->rows(5)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('file_path')
                            ->label('ملف الواجب')
                            ->directory('homework-submissions')
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('submitted_at')
                            ->label('تاريخ التسليم')
                            ->default(now()),
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
                            ->label('تاريخ التصحيح'),
                        Forms\Components\Select::make('graded_by')
                            ->relationship('grader', 'name')
                            ->label('المصحح')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('teacher_feedback')
                            ->label('ملاحظات المعلم')
                            ->placeholder('أضف تغذية راجعة للطالب...')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(3),
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
                Tables\Columns\TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('submitable_type')
                    ->label('نوع الواجب')
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'App\\Models\\QuranSession' => 'قرآن',
                        'App\\Models\\AcademicSession' => 'أكاديمي',
                        'App\\Models\\InteractiveCourseSession' => 'تفاعلي',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        SubscriptionStatus::PENDING->value => 'warning',
                        'graded' => 'success',
                        'returned' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        SubscriptionStatus::PENDING->value => 'قيد المراجعة',
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
                Tables\Columns\TextColumn::make('grader.name')
                    ->label('المصحح')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        SubscriptionStatus::PENDING->value => 'قيد المراجعة',
                        'graded' => 'تم التصحيح',
                        'returned' => 'تم الإرجاع',
                    ]),
                Tables\Filters\SelectFilter::make('academy_id')
                    ->label('الأكاديمية')
                    ->relationship('academy', 'name')
                    ->searchable()
                    ->preload(),
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
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('submitted_at', 'desc');
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
            'index' => Pages\ListHomeworkSubmissions::route('/'),
            'create' => Pages\CreateHomeworkSubmission::route('/create'),
            'edit' => Pages\EditHomeworkSubmission::route('/{record}/edit'),
        ];
    }
}
