<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuizAssignmentResource\Pages;
use App\Helpers\AcademyHelper;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSubscription;
use App\Models\InteractiveCourse;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\RecordedCourse;
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

    protected static ?string $navigationGroup = 'إدارة الاختبارات';

    protected static ?string $navigationLabel = 'تعيين الاختبارات';

    protected static ?string $modelLabel = 'تعيين اختبار';

    protected static ?string $pluralModelLabel = 'تعيينات الاختبارات';

    protected static function getAssignableTypes(): array
    {
        return [
            QuranCircle::class => 'حلقة قرآن جماعية',
            QuranIndividualCircle::class => 'حلقة قرآن فردية',
            AcademicSubscription::class => 'اشتراك أكاديمي (درس خاص)',
            AcademicIndividualLesson::class => 'حصة أكاديمية فردية',
            InteractiveCourse::class => 'دورة تفاعلية',
            RecordedCourse::class => 'دورة مسجلة',
        ];
    }

    public static function form(Form $form): Form
    {
        $currentAcademy = AcademyHelper::getCurrentAcademy();

        return $form
            ->schema([
                Forms\Components\Section::make('تعيين الاختبار')
                    ->schema([
                        Forms\Components\Select::make('quiz_id')
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

                        Forms\Components\Select::make('assignable_type')
                            ->label('نوع الجهة')
                            ->options(self::getAssignableTypes())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('assignable_id', null)),

                        Forms\Components\Select::make('assignable_id')
                            ->label('الجهة')
                            ->options(function (Get $get) use ($currentAcademy) {
                                $type = $get('assignable_type');
                                if (!$type) {
                                    return [];
                                }

                                $query = $type::query();

                                // Apply academy filter based on model
                                if ($currentAcademy) {
                                    if (in_array($type, [QuranCircle::class, QuranIndividualCircle::class, AcademicIndividualLesson::class, AcademicSubscription::class, RecordedCourse::class, InteractiveCourse::class])) {
                                        $query->where('academy_id', $currentAcademy->id);
                                    }
                                }

                                // Get appropriate name field
                                return match ($type) {
                                    QuranCircle::class => $query->pluck('name', 'id'),
                                    QuranIndividualCircle::class => $query->with('student')->get()->mapWithKeys(fn ($c) => [$c->id => $c->student?->first_name . ' ' . $c->student?->last_name]),
                                    AcademicSubscription::class => $query->with('student')->get()->mapWithKeys(fn ($s) => [$s->id => ($s->student?->first_name ?? '') . ' ' . ($s->student?->last_name ?? '') . ' - ' . ($s->subject_name ?? 'درس خاص')]),
                                    AcademicIndividualLesson::class => $query->pluck('name', 'id'),
                                    InteractiveCourse::class => $query->pluck('title', 'id'),
                                    RecordedCourse::class => $query->pluck('title', 'id'),
                                    default => [],
                                };
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
                    ->label('نوع الجهة')
                    ->formatStateUsing(fn ($state) => self::getAssignableTypes()[$state] ?? $state),

                Tables\Columns\TextColumn::make('assignable')
                    ->label('الجهة')
                    ->formatStateUsing(function ($record) {
                        $assignable = $record->assignable;
                        if (!$assignable) {
                            return '-';
                        }
                        return $assignable->title ?? $assignable->name ?? $assignable->id;
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('assignable_type')
                    ->label('نوع الجهة')
                    ->options(self::getAssignableTypes()),

                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('الحالة')
                    ->trueLabel('مرئي')
                    ->falseLabel('مخفي'),
            ])
            ->actions([
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
        $query = parent::getEloquentQuery()->with(['quiz', 'assignable']);

        $currentAcademy = AcademyHelper::getCurrentAcademy();
        if ($currentAcademy) {
            $query->whereHas('quiz', function ($q) use ($currentAcademy) {
                $q->where('academy_id', $currentAcademy->id);
            });
        }

        return $query;
    }
}
