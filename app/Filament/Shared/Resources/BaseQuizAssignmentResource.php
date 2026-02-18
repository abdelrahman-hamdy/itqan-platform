<?php

namespace App\Filament\Shared\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use App\Filament\Resources\BaseResource;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use Filament\Facades\Filament;
use Filament\Forms;
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

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-plus';

    protected static string | \UnitEnum | null $navigationGroup = 'الاختبارات';

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

    public static function form(Schema $schema): Schema
    {
        $tenant = Filament::getTenant();

        return $schema
            ->components([
                Section::make('تعيين الاختبار')
                    ->schema([
                        Select::make('quiz_id')
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

                        Select::make('assignable_type')
                            ->label(static::getAssignableTypeLabel())
                            ->options(static::getAssignableTypes())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('assignable_id', null)),

                        Select::make('assignable_id')
                            ->label(static::getAssignableTargetLabel())
                            ->options(fn (Get $get) => static::getAssignableOptions($get('assignable_type')))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (Get $get) => ! $get('assignable_type')),
                    ])
                    ->columns(2),

                Section::make('إعدادات التوفر')
                    ->schema([
                        Toggle::make('is_visible')
                            ->label('مرئي للطلاب')
                            ->default(true)
                            ->helperText('إخفاء الاختبار عن الطلاب مؤقتاً'),

                        TextInput::make('max_attempts')
                            ->label('عدد المحاولات المسموحة')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(10)
                            ->required(),

                        DateTimePicker::make('available_from')
                            ->label('متاح من')
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('Y-m-d H:i')
                            ->placeholder('اتركه فارغاً للإتاحة فوراً')
                            ->helperText('تاريخ ووقت بدء إتاحة الاختبار للطلاب'),

                        DateTimePicker::make('available_until')
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

                TextColumn::make('quiz.title')
                    ->label('الاختبار')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('assignable_type')
                    ->label(static::getAssignableTypeLabel())
                    ->formatStateUsing(fn ($state) => static::getAssignableTypes()[$state] ?? $state),

                TextColumn::make('assignable')
                    ->label(static::getAssignableTargetLabel())
                    ->formatStateUsing(fn ($record) => static::formatAssignableName($record)),

                IconColumn::make('is_visible')
                    ->label('مرئي')
                    ->boolean(),

                TextColumn::make('max_attempts')
                    ->label('المحاولات')
                    ->sortable(),

                TextColumn::make('attempts_count')
                    ->label('عدد التقديمات')
                    ->counts('attempts'),

                TextColumn::make('available_from')
                    ->label('متاح من')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('فوري'),

                TextColumn::make('available_until')
                    ->label('متاح حتى')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('دائم'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('assignable_type')
                    ->label(static::getAssignableTypeLabel())
                    ->options(static::getAssignableTypes()),

                TernaryFilter::make('is_visible')
                    ->label('الحالة')
                    ->trueLabel('مرئي')
                    ->falseLabel('مخفي'),
            ])
            ->deferFilters(false)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('عرض'),
                    EditAction::make()->label('تعديل'),
                    DeleteAction::make()->label('حذف'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
