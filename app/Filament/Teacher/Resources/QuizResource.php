<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\QuizResource\Pages;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuizResource extends Resource
{
    protected static ?string $model = Quiz::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'الاختبارات';

    protected static ?string $navigationLabel = 'اختباراتي';

    protected static ?string $modelLabel = 'اختبار';

    protected static ?string $pluralModelLabel = 'الاختبارات';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الاختبار')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الاختبار')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف الاختبار')
                            ->rows(3)
                            ->maxLength(1000),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('المدة (بالدقائق)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(180)
                            ->helperText('اتركه فارغاً لاختبار بدون وقت محدد'),

                        Forms\Components\TextInput::make('passing_score')
                            ->label('درجة النجاح (%)')
                            ->numeric()
                            ->default(60)
                            ->minValue(0)
                            ->maxValue(100)
                            ->required()
                            ->suffix('%'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('الأسئلة')
                    ->schema([
                        Forms\Components\Repeater::make('questions')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Forms\Components\Textarea::make('question_text')
                                    ->label('نص السؤال')
                                    ->required()
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Forms\Components\Repeater::make('options')
                                    ->label('الخيارات')
                                    ->simple(
                                        Forms\Components\TextInput::make('option')
                                            ->required()
                                            ->placeholder('أدخل نص الخيار')
                                            ->live(onBlur: true),
                                    )
                                    ->minItems(2)
                                    ->maxItems(6)
                                    ->defaultItems(4)
                                    ->columnSpanFull()
                                    ->reorderable(false)
                                    ->live(),

                                Forms\Components\Select::make('correct_option')
                                    ->label('الإجابة الصحيحة')
                                    ->options(function (Forms\Get $get): array {
                                        $options = $get('options') ?? [];
                                        $result = [];
                                        foreach ($options as $index => $option) {
                                            $text = is_array($option) ? ($option['option'] ?? $option[0] ?? '') : $option;
                                            $displayIndex = $index + 1;
                                            $result[$index] = "الخيار {$displayIndex}: " . ($text ?: '(فارغ)');
                                        }
                                        return $result ?: [0 => 'أدخل الخيارات أولاً'];
                                    })
                                    ->live()
                                    ->required()
                                    ->helperText('اختر الإجابة الصحيحة من الخيارات أعلاه'),

                                Forms\Components\Hidden::make('order')
                                    ->default(0),
                            ])
                            ->orderColumn('order')
                            ->defaultItems(1)
                            ->addActionLabel('إضافة سؤال')
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => $state['question_text'] ?? 'سؤال جديد'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label('عدد الأسئلة')
                    ->counts('questions')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('المدة')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} دقيقة" : 'غير محدد'),

                Tables\Columns\TextColumn::make('passing_score')
                    ->label('درجة النجاح')
                    ->formatStateUsing(fn ($state) => "{$state}%"),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة'),
            ])
            ->actions([
                Tables\Actions\Action::make('assign')
                    ->label('تعيين')
                    ->icon('heroicon-o-link')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('assignable_type')
                            ->label('نوع الحلقة')
                            ->options([
                                QuranCircle::class => 'حلقة جماعية',
                                QuranIndividualCircle::class => 'حلقة فردية',
                            ])
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('assignable_id')
                            ->label('الحلقة')
                            ->options(function (Forms\Get $get) {
                                $type = $get('assignable_type');
                                $teacherId = auth()->user()->quranTeacherProfile?->id;

                                if (!$type || !$teacherId) return [];

                                if ($type === QuranCircle::class) {
                                    return QuranCircle::where('quran_teacher_id', $teacherId)->pluck('name', 'id');
                                } else {
                                    return QuranIndividualCircle::where('quran_teacher_id', $teacherId)
                                        ->with('student')
                                        ->get()
                                        ->mapWithKeys(fn ($c) => [$c->id => $c->student?->first_name . ' ' . $c->student?->last_name]);
                                }
                            })
                            ->required()
                            ->searchable(),

                        Forms\Components\Toggle::make('is_visible')
                            ->label('مرئي للطلاب')
                            ->default(true),

                        Forms\Components\TextInput::make('max_attempts')
                            ->label('عدد المحاولات')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(10),

                        Forms\Components\DateTimePicker::make('available_from')
                            ->label('متاح من')
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('Y-m-d H:i')
                            ->placeholder('اتركه فارغاً للإتاحة فوراً'),

                        Forms\Components\DateTimePicker::make('available_until')
                            ->label('متاح حتى')
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('Y-m-d H:i')
                            ->placeholder('اتركه فارغاً للإتاحة دائماً')
                            ->after('available_from'),
                    ])
                    ->action(function (Quiz $record, array $data) {
                        QuizAssignment::create([
                            'quiz_id' => $record->id,
                            'assignable_type' => $data['assignable_type'],
                            'assignable_id' => $data['assignable_id'],
                            'is_visible' => $data['is_visible'],
                            'max_attempts' => $data['max_attempts'],
                            'available_from' => $data['available_from'] ?? null,
                            'available_until' => $data['available_until'] ?? null,
                        ]);
                    }),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuizzes::route('/'),
            'create' => Pages\CreateQuiz::route('/create'),
            'edit' => Pages\EditQuiz::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()
            ->where('academy_id', $tenant?->id);
    }
}
