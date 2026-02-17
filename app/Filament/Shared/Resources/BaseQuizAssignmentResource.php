<?php

namespace App\Filament\Shared\Resources;

use App\Filament\Resources\BaseResource;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base Quiz Assignment Resource
 *
 * Shared functionality for Teacher and AcademicTeacher panels.
 * Child classes must implement assignable type configuration, query filtering, and getPages().
 */
abstract class BaseQuizAssignmentResource extends BaseResource
{
    protected static ?string $model = QuizAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationGroup = 'الاختبارات';

    protected static ?string $navigationLabel = 'تعيينات الاختبارات';

    protected static ?string $modelLabel = 'تعيين اختبار';

    protected static ?string $pluralModelLabel = 'تعيينات الاختبارات';

    protected static ?int $navigationSort = 2;

    // Disable automatic tenant scoping - we filter by teacher in getEloquentQuery()
    protected static bool $isScopedToTenant = false;

    /**
     * Get the assignable types with their labels.
     *
     * @return array<string, string> [ModelClass => Label]
     */
    abstract protected static function getAssignableTypes(): array;

    /**
     * Get options for the assignable_id select based on selected type.
     *
     * @param  string|null  $type  The selected assignable_type
     * @return array<int|string, string> Options for the select
     */
    abstract protected static function getAssignableOptions(?string $type): array;

    /**
     * Get the current teacher's ID for filtering.
     */
    abstract protected static function getTeacherId(): ?int;

    /**
     * Get the IDs for query filtering based on assignable type.
     * Returns an array of [type => ids[]] pairs.
     *
     * @return array<string, array<int>> [AssignableClass => [ids]]
     */
    abstract protected static function getTeacherAssignableIds(): array;

    /**
     * Format the assignable name for display in the table.
     */
    abstract protected static function formatAssignableName($record): string;

    /**
     * Get the label for assignable type select field.
     */
    protected static function getAssignableTypeLabel(): string
    {
        return 'نوع الجهة';
    }

    /**
     * Get the label for assignable target select field.
     */
    protected static function getAssignableTargetLabel(): string
    {
        return 'الجهة';
    }

    public static function form(Form $form): Form
    {
        $tenant = Filament::getTenant();

        return $form
            ->schema([
                Forms\Components\Section::make('تعيين الاختبار')
                    ->schema([
                        Forms\Components\Select::make('quiz_id')
                            ->label('الاختبار')
                            ->options(function () use ($tenant) {
                                $query = Quiz::active();
                                if ($tenant) {
                                    $query->where('academy_id', $tenant->id);
                                }

                                return $query->pluck('title', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('assignable_type')
                            ->label(static::getAssignableTypeLabel())
                            ->options(static::getAssignableTypes())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('assignable_id', null)),

                        Forms\Components\Select::make('assignable_id')
                            ->label(static::getAssignableTargetLabel())
                            ->options(fn (Get $get) => static::getAssignableOptions($get('assignable_type')))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (Get $get) => ! $get('assignable_type')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('إعدادات التوفر')
                    ->schema([
                        Forms\Components\Toggle::make('is_visible')
                            ->label('مرئي للطلاب')
                            ->default(true)
                            ->helperText('إخفاء الاختبار عن الطلاب مؤقتاً'),

                        Forms\Components\TextInput::make('max_attempts')
                            ->label('عدد المحاولات المسموحة')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(10)
                            ->required(),

                        Forms\Components\DateTimePicker::make('available_from')
                            ->label('متاح من')
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('Y-m-d H:i')
                            ->placeholder('اتركه فارغاً للإتاحة فوراً')
                            ->helperText('تاريخ ووقت بدء إتاحة الاختبار للطلاب'),

                        Forms\Components\DateTimePicker::make('available_until')
                            ->label('متاح حتى')
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('Y-m-d H:i')
                            ->placeholder('اتركه فارغاً للإتاحة دائماً')
                            ->after('available_from')
                            ->helperText('تاريخ ووقت انتهاء إتاحة الاختبار'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getAcademyColumn(),

                Tables\Columns\TextColumn::make('quiz.title')
                    ->label('الاختبار')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('assignable_type')
                    ->label(static::getAssignableTypeLabel())
                    ->formatStateUsing(fn ($state) => static::getAssignableTypes()[$state] ?? $state),

                Tables\Columns\TextColumn::make('assignable')
                    ->label(static::getAssignableTargetLabel())
                    ->formatStateUsing(fn ($record) => static::formatAssignableName($record)),

                Tables\Columns\IconColumn::make('is_visible')
                    ->label('مرئي')
                    ->boolean(),

                Tables\Columns\TextColumn::make('max_attempts')
                    ->label('المحاولات')
                    ->sortable(),

                Tables\Columns\TextColumn::make('attempts_count')
                    ->label('عدد التقديمات')
                    ->counts('attempts'),

                Tables\Columns\TextColumn::make('available_from')
                    ->label('متاح من')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('فوري'),

                Tables\Columns\TextColumn::make('available_until')
                    ->label('متاح حتى')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('دائم'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('assignable_type')
                    ->label(static::getAssignableTypeLabel())
                    ->options(static::getAssignableTypes()),

                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('الحالة')
                    ->trueLabel('مرئي')
                    ->falseLabel('مخفي'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();
        $teacherId = static::getTeacherId();

        $query = parent::getEloquentQuery()->with(['quiz', 'assignable']);

        // Filter by teacher's assignables using subqueries for better performance
        if ($teacherId) {
            $assignableIds = static::getTeacherAssignableIds();

            $query->where(function ($q) use ($assignableIds) {
                $first = true;
                foreach ($assignableIds as $type => $ids) {
                    if ($first) {
                        $q->where(function ($subQ) use ($type, $ids) {
                            $subQ->where('assignable_type', $type)
                                ->whereIn('assignable_id', $ids);
                        });
                        $first = false;
                    } else {
                        $q->orWhere(function ($subQ) use ($type, $ids) {
                            $subQ->where('assignable_type', $type)
                                ->whereIn('assignable_id', $ids);
                        });
                    }
                }
            });
        }

        // Also filter by academy
        if ($tenant) {
            $query->whereHas('quiz', function ($q) use ($tenant) {
                $q->where('academy_id', $tenant->id);
            });
        }

        return $query;
    }
}
