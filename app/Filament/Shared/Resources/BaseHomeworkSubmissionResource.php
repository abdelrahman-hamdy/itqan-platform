<?php

namespace App\Filament\Shared\Resources;

use App\Models\HomeworkSubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\SubscriptionStatus;

/**
 * Base Homework Submission Resource
 *
 * Shared functionality for Teacher and AcademicTeacher panels.
 * Child classes must implement getSubmitableTypes() and getPages().
 */
abstract class BaseHomeworkSubmissionResource extends Resource
{
    protected static ?string $model = HomeworkSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'الواجبات المقدمة';

    protected static ?string $modelLabel = 'واجب مقدم';

    protected static ?string $pluralModelLabel = 'الواجبات المقدمة';

    protected static ?string $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 3;

    /**
     * Get the submitable types this resource should filter by.
     * Override in child classes.
     *
     * @return array<string> Array of model class names (e.g., ['App\\Models\\QuranSession'])
     */
    abstract protected static function getSubmitableTypes(): array;

    /**
     * Whether to show the submitable_type column/field/filter.
     * Override to true in resources that handle multiple types.
     */
    protected static function showSubmitableTypeInfo(): bool
    {
        return false;
    }

    /**
     * Get submitable type display labels.
     * Override to provide custom labels.
     */
    protected static function getSubmitableTypeLabels(): array
    {
        return [
            'App\\Models\\QuranSession' => 'قرآن',
            'App\\Models\\AcademicSession' => 'أكاديمي',
            'App\\Models\\InteractiveCourseSession' => 'تفاعلي',
        ];
    }

    /**
     * Filter query by submitable types and eager load relationships to prevent N+1 queries.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'student',
                'submitable',
                'grader',
                'academy',
            ]);

        $types = static::getSubmitableTypes();

        if (count($types) === 1) {
            $query->where('submitable_type', $types[0]);
        } else {
            $query->whereIn('submitable_type', $types);
        }

        return $query;
    }

    /**
     * Teachers can grade homework but not create submissions.
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
                    ->schema(static::getInfoSectionSchema())
                    ->columns(static::showSubmitableTypeInfo() ? 2 : 3),

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

    /**
     * Get the info section schema.
     * Includes submitable_type field only if showSubmitableTypeInfo() returns true.
     */
    protected static function getInfoSectionSchema(): array
    {
        $schema = [
            Forms\Components\Placeholder::make('student_name')
                ->label('الطالب')
                ->content(fn ($record) => $record?->student?->name ?? '-'),

            Forms\Components\TextInput::make('submission_code')
                ->label('كود التسليم')
                ->disabled()
                ->dehydrated(false),
        ];

        if (static::showSubmitableTypeInfo()) {
            $labels = static::getSubmitableTypeLabels();
            $schema[] = Forms\Components\TextInput::make('submitable_type')
                ->label('نوع الواجب')
                ->disabled()
                ->dehydrated(false)
                ->formatStateUsing(fn (?string $state): string => $labels[$state] ?? $state ?? '-');
        }

        $schema[] = Forms\Components\Select::make('status')
            ->label('الحالة')
            ->options([
                SubscriptionStatus::PENDING->value => 'قيد المراجعة',
                'graded' => 'تم التصحيح',
                'returned' => 'تم الإرجاع',
            ])
            ->required();

        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters())
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

    /**
     * Get table columns.
     * Includes submitable_type column only if showSubmitableTypeInfo() returns true.
     */
    protected static function getTableColumns(): array
    {
        $columns = [
            Tables\Columns\TextColumn::make('submission_code')
                ->label('كود التسليم')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('student.name')
                ->label('الطالب')
                ->searchable()
                ->sortable(),
        ];

        if (static::showSubmitableTypeInfo()) {
            $labels = static::getSubmitableTypeLabels();
            $columns[] = Tables\Columns\TextColumn::make('submitable_type')
                ->label('نوع الواجب')
                ->searchable()
                ->formatStateUsing(fn (string $state): string => $labels[$state] ?? $state);
        }

        $columns = array_merge($columns, [
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
        ]);

        return $columns;
    }

    /**
     * Get table filters.
     * Includes submitable_type filter only if showSubmitableTypeInfo() returns true.
     */
    protected static function getTableFilters(): array
    {
        $filters = [
            Tables\Filters\SelectFilter::make('status')
                ->label('الحالة')
                ->options([
                    SubscriptionStatus::PENDING->value => 'قيد المراجعة',
                    'graded' => 'تم التصحيح',
                    'returned' => 'تم الإرجاع',
                ]),
        ];

        if (static::showSubmitableTypeInfo()) {
            $types = static::getSubmitableTypes();
            $labels = static::getSubmitableTypeLabels();
            $options = [];
            foreach ($types as $type) {
                $options[$type] = $labels[$type] ?? class_basename($type);
            }

            $filters[] = Tables\Filters\SelectFilter::make('submitable_type')
                ->label('نوع الواجب')
                ->options($options);
        }

        $filters = array_merge($filters, [
            Tables\Filters\Filter::make('has_file')
                ->label('به ملف مرفق')
                ->query(fn (Builder $query): Builder => $query->whereNotNull('file_path')),

            Tables\Filters\Filter::make('graded')
                ->label('تم التصحيح')
                ->query(fn (Builder $query): Builder => $query->whereNotNull('grade')),
        ]);

        return $filters;
    }

    public static function getRelations(): array
    {
        return [];
    }
}
