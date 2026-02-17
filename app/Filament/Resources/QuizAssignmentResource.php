<?php

namespace App\Filament\Resources;

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
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\QuizAssignmentResource\Pages\ListQuizAssignments;
use App\Filament\Resources\QuizAssignmentResource\Pages\CreateQuizAssignment;
use App\Filament\Resources\QuizAssignmentResource\Pages\EditQuizAssignment;
use App\Enums\QuizAssignableType;
use App\Filament\Resources\QuizAssignmentResource\Pages;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuizAssignmentResource extends BaseResource
{
    protected static ?string $model = QuizAssignment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-plus';

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة الاختبارات';

    protected static ?string $navigationLabel = 'تعيين الاختبارات';

    protected static ?string $modelLabel = 'تعيين اختبار';

    protected static ?string $pluralModelLabel = 'تعيينات الاختبارات';

    public static function form(Schema $schema): Schema
    {
        $currentAcademy = AcademyContextService::getCurrentAcademy();

        return $schema
            ->components([
                Section::make('تعيين الاختبار')
                    ->schema([
                        Select::make('quiz_id')
                            ->label('الاختبار')
                            ->options(function () use ($currentAcademy) {
                                $query = Quiz::active();
                                if ($currentAcademy) {
                                    $query->where('academy_id', $currentAcademy->id);
                                }

                                return $query->pluck('title', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('assignable_type')
                            ->label('نوع الجهة')
                            ->options(QuizAssignableType::options())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('assignable_id', null)),

                        Select::make('assignable_id')
                            ->label('الجهة')
                            ->searchable()
                            ->preload()
                            ->options(function (Get $get) use ($currentAcademy) {
                                $typeValue = $get('assignable_type');
                                if (! $typeValue) {
                                    return [];
                                }

                                $enumType = QuizAssignableType::tryFrom($typeValue);
                                if (! $enumType) {
                                    return [];
                                }

                                $modelClass = $enumType->modelClass();
                                $query = $modelClass::query();

                                // Apply academy filter
                                if ($currentAcademy) {
                                    $query->where('academy_id', $currentAcademy->id);
                                }

                                // Return all results (limited for performance)
                                return match ($enumType) {
                                    QuizAssignableType::QURAN_CIRCLE => $query->limit(200)->pluck('name', 'id'),
                                    QuizAssignableType::QURAN_INDIVIDUAL_CIRCLE => $query->with('student')
                                        ->limit(200)->get()->mapWithKeys(fn ($c) => [$c->id => ($c->student?->first_name ?? '').' '.($c->student?->last_name ?? '')]),
                                    QuizAssignableType::ACADEMIC_INDIVIDUAL_LESSON => $query->with('student')
                                        ->limit(200)->get()->mapWithKeys(fn ($l) => [$l->id => ($l->name ?? '').' - '.($l->student?->first_name ?? '').' '.($l->student?->last_name ?? '')]),
                                    QuizAssignableType::INTERACTIVE_COURSE => $query->limit(200)->pluck('title', 'id'),
                                    QuizAssignableType::RECORDED_COURSE => $query->limit(200)->pluck('title', 'id'),
                                };
                            })
                            ->getSearchResultsUsing(function (string $search, Get $get) use ($currentAcademy) {
                                $typeValue = $get('assignable_type');
                                if (! $typeValue) {
                                    return [];
                                }

                                $enumType = QuizAssignableType::tryFrom($typeValue);
                                if (! $enumType) {
                                    return [];
                                }

                                $modelClass = $enumType->modelClass();
                                $query = $modelClass::query();

                                // Apply academy filter
                                if ($currentAcademy) {
                                    $query->where('academy_id', $currentAcademy->id);
                                }

                                // Apply search and limit results
                                return match ($enumType) {
                                    QuizAssignableType::QURAN_CIRCLE => $query->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'),
                                    QuizAssignableType::QURAN_INDIVIDUAL_CIRCLE => $query->with('student')
                                        ->whereHas('student', fn ($q) => $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                                        ->limit(50)->get()->mapWithKeys(fn ($c) => [$c->id => ($c->student?->first_name ?? '').' '.($c->student?->last_name ?? '')]),
                                    QuizAssignableType::ACADEMIC_INDIVIDUAL_LESSON => $query->with('student')
                                        ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                                            ->orWhereHas('student', fn ($sq) => $sq->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")))
                                        ->limit(50)->get()->mapWithKeys(fn ($l) => [$l->id => ($l->name ?? '').' - '.($l->student?->first_name ?? '').' '.($l->student?->last_name ?? '')]),
                                    QuizAssignableType::INTERACTIVE_COURSE => $query->where('title', 'like', "%{$search}%")->limit(50)->pluck('title', 'id'),
                                    QuizAssignableType::RECORDED_COURSE => $query->where('title', 'like', "%{$search}%")->limit(50)->pluck('title', 'id'),
                                };
                            })
                            ->getOptionLabelUsing(function ($value, Get $get) {
                                $typeValue = $get('assignable_type');
                                if (! $typeValue || ! $value) {
                                    return null;
                                }

                                $enumType = QuizAssignableType::tryFrom($typeValue);
                                if (! $enumType) {
                                    return null;
                                }

                                $modelClass = $enumType->modelClass();
                                $model = $modelClass::find($value);

                                if (! $model) {
                                    return null;
                                }

                                return match ($enumType) {
                                    QuizAssignableType::QURAN_CIRCLE => $model->name,
                                    QuizAssignableType::QURAN_INDIVIDUAL_CIRCLE => ($model->student?->first_name ?? '').' '.($model->student?->last_name ?? ''),
                                    QuizAssignableType::ACADEMIC_INDIVIDUAL_LESSON => ($model->name ?? '').' - '.($model->student?->first_name ?? '').' '.($model->student?->last_name ?? ''),
                                    QuizAssignableType::INTERACTIVE_COURSE => $model->title,
                                    QuizAssignableType::RECORDED_COURSE => $model->title,
                                };
                            })
                            ->required()
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
                TextColumn::make('quiz.title')
                    ->label('الاختبار')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('assignable_type')
                    ->label('نوع الجهة')
                    ->formatStateUsing(fn ($state) => QuizAssignableType::tryFrom($state)?->label() ?? $state)
                    ->icon(fn ($state) => QuizAssignableType::tryFrom($state)?->icon())
                    ->color(fn ($state) => QuizAssignableType::tryFrom($state)?->color()),

                TextColumn::make('assignable')
                    ->label('الجهة')
                    ->formatStateUsing(function ($record) {
                        $assignable = $record->assignable;
                        if (! $assignable) {
                            return '-';
                        }

                        return $assignable->title ?? $assignable->name ?? $assignable->id;
                    }),

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
            ])
            ->filters([
                SelectFilter::make('assignable_type')
                    ->label('نوع الجهة')
                    ->options(QuizAssignableType::options()),

                TernaryFilter::make('is_visible')
                    ->label('الحالة')
                    ->trueLabel('مرئي')
                    ->falseLabel('مخفي'),
            ])
            ->deferFilters(false)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListQuizAssignments::route('/'),
            'create' => CreateQuizAssignment::route('/create'),
            'edit' => EditQuizAssignment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['quiz', 'assignable']);

        $currentAcademy = AcademyContextService::getCurrentAcademy();
        if ($currentAcademy) {
            $query->whereHas('quiz', function ($q) use ($currentAcademy) {
                $q->where('academy_id', $currentAcademy->id);
            });
        }

        return $query;
    }
}
