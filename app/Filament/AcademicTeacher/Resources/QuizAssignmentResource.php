<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Filament\AcademicTeacher\Resources\QuizAssignmentResource\Pages;
use App\Models\AcademicSubscription;
use App\Models\InteractiveCourse;
use App\Models\Quiz;
use App\Models\QuizAssignment;
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
            AcademicSubscription::class => 'اشتراك أكاديمي (درس خاص)',
            InteractiveCourse::class => 'دورة تفاعلية',
        ];
    }

    public static function form(Form $form): Form
    {
        $tenant = Filament::getTenant();
        $teacherId = auth()->user()->academicTeacherProfile?->id;

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
                            ->label('نوع الجهة')
                            ->options(self::getAssignableTypes())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('assignable_id', null)),

                        Forms\Components\Select::make('assignable_id')
                            ->label('الجهة')
                            ->options(function (Get $get) use ($tenant, $teacherId) {
                                $type = $get('assignable_type');
                                if (!$type || !$teacherId) {
                                    return [];
                                }

                                if ($type === AcademicSubscription::class) {
                                    return AcademicSubscription::where('teacher_id', $teacherId)
                                        ->with('student')
                                        ->get()
                                        ->mapWithKeys(fn ($s) => [
                                            $s->id => ($s->student?->first_name ?? '') . ' ' . ($s->student?->last_name ?? '') . ' - ' . ($s->subject_name ?? 'درس خاص')
                                        ]);
                                } elseif ($type === InteractiveCourse::class) {
                                    return InteractiveCourse::where('assigned_teacher_id', $teacherId)
                                        ->pluck('title', 'id');
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
                    ->label('نوع الجهة')
                    ->formatStateUsing(fn ($state) => self::getAssignableTypes()[$state] ?? $state),

                Tables\Columns\TextColumn::make('assignable')
                    ->label('الجهة')
                    ->formatStateUsing(function ($record) {
                        $assignable = $record->assignable;
                        if (!$assignable) {
                            return '-';
                        }

                        if ($record->assignable_type === AcademicSubscription::class) {
                            return ($assignable->student?->first_name ?? '') . ' ' . ($assignable->student?->last_name ?? '');
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

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
        $teacherId = auth()->user()->academicTeacherProfile?->id;

        $query = parent::getEloquentQuery()->with(['quiz', 'assignable']);

        // Filter by teacher's assignments using subqueries for better performance
        if ($teacherId) {
            // Get IDs of academic subscriptions belonging to this teacher
            $academicSubIds = AcademicSubscription::where('teacher_id', $teacherId)->pluck('id');

            // Get IDs of interactive courses assigned to this teacher
            $courseIds = InteractiveCourse::where('assigned_teacher_id', $teacherId)->pluck('id');

            $query->where(function ($q) use ($academicSubIds, $courseIds) {
                $q->where(function ($subQ) use ($academicSubIds) {
                    $subQ->where('assignable_type', AcademicSubscription::class)
                        ->whereIn('assignable_id', $academicSubIds);
                })->orWhere(function ($subQ) use ($courseIds) {
                    $subQ->where('assignable_type', InteractiveCourse::class)
                        ->whereIn('assignable_id', $courseIds);
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
