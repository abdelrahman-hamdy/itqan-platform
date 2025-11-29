<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\QuizAssignmentResource\Pages;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuizAssignmentResource extends Resource
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

    protected static function getAssignableTypes(): array
    {
        return [
            QuranCircle::class => 'حلقة قرآن جماعية',
            QuranIndividualCircle::class => 'حلقة قرآن فردية',
        ];
    }

    public static function form(Form $form): Form
    {
        $tenant = Filament::getTenant();
        $teacherId = auth()->user()->quranTeacherProfile?->id;

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
                            ->label('نوع الحلقة')
                            ->options(self::getAssignableTypes())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('assignable_id', null)),

                        Forms\Components\Select::make('assignable_id')
                            ->label('الحلقة')
                            ->options(function (Get $get) use ($tenant, $teacherId) {
                                $type = $get('assignable_type');
                                if (!$type || !$teacherId) {
                                    return [];
                                }

                                if ($type === QuranCircle::class) {
                                    return QuranCircle::where('quran_teacher_id', $teacherId)
                                        ->pluck('name', 'id');
                                } elseif ($type === QuranIndividualCircle::class) {
                                    return QuranIndividualCircle::where('quran_teacher_id', $teacherId)
                                        ->with('student')
                                        ->get()
                                        ->mapWithKeys(fn ($c) => [
                                            $c->id => ($c->student?->first_name ?? '') . ' ' . ($c->student?->last_name ?? '')
                                        ]);
                                }

                                return [];
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (Get $get) => !$get('assignable_type')),
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
                Tables\Columns\TextColumn::make('quiz.title')
                    ->label('الاختبار')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('assignable_type')
                    ->label('نوع الحلقة')
                    ->formatStateUsing(fn ($state) => self::getAssignableTypes()[$state] ?? $state),

                Tables\Columns\TextColumn::make('assignable')
                    ->label('الحلقة')
                    ->formatStateUsing(function ($record) {
                        $assignable = $record->assignable;
                        if (!$assignable) {
                            return '-';
                        }

                        if ($record->assignable_type === QuranIndividualCircle::class) {
                            return ($assignable->student?->first_name ?? '') . ' ' . ($assignable->student?->last_name ?? '');
                        }

                        return $assignable->name ?? $assignable->id;
                    }),

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
                    ->label('نوع الحلقة')
                    ->options(self::getAssignableTypes()),

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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuizAssignments::route('/'),
            'create' => Pages\CreateQuizAssignment::route('/create'),
            'edit' => Pages\EditQuizAssignment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();
        $teacherId = auth()->user()->quranTeacherProfile?->id;

        $query = parent::getEloquentQuery()->with(['quiz', 'assignable']);

        // Filter by teacher's circles using subqueries for better performance
        if ($teacherId) {
            // Get IDs of Quran circles belonging to this teacher
            $circleIds = QuranCircle::where('quran_teacher_id', $teacherId)->pluck('id');

            // Get IDs of individual circles belonging to this teacher
            $individualCircleIds = QuranIndividualCircle::where('quran_teacher_id', $teacherId)->pluck('id');

            $query->where(function ($q) use ($circleIds, $individualCircleIds) {
                $q->where(function ($subQ) use ($circleIds) {
                    $subQ->where('assignable_type', QuranCircle::class)
                        ->whereIn('assignable_id', $circleIds);
                })->orWhere(function ($subQ) use ($individualCircleIds) {
                    $subQ->where('assignable_type', QuranIndividualCircle::class)
                        ->whereIn('assignable_id', $individualCircleIds);
                });
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
