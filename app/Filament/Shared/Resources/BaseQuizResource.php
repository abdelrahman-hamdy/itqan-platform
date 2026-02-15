<?php

namespace App\Filament\Shared\Resources;

use App\Models\Quiz;
use App\Models\QuizAssignment;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base Quiz Resource
 *
 * Shared functionality for Teacher and AcademicTeacher panels.
 * Child classes must implement assignable type configuration and getPages().
 */
abstract class BaseQuizResource extends Resource
{
    protected static ?string $model = Quiz::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'الاختبارات';

    protected static ?string $navigationLabel = 'اختباراتي';

    protected static ?string $modelLabel = 'اختبار';

    protected static ?string $pluralModelLabel = 'الاختبارات';

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
                            ->minValue(10)
                            ->maxValue(90)
                            ->required()
                            ->suffix('%')
                            ->helperText('يجب أن تكون درجة النجاح بين 10% و 90%'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true),

                        Forms\Components\Toggle::make('randomize_questions')
                            ->label('ترتيب عشوائي للأسئلة')
                            ->helperText('عند التفعيل، ستظهر الأسئلة بترتيب مختلف لكل طالب')
                            ->default(false),
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
                                        $counter = 0;
                                        foreach ($options as $option) {
                                            $text = is_array($option) ? ($option['option'] ?? $option[0] ?? '') : $option;
                                            $displayIndex = $counter + 1;
                                            $result[$counter] = "الخيار {$displayIndex}: ".($text ?: '(فارغ)');
                                            $counter++;
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
                    ->formatStateUsing(fn ($state) => $state ? "{$state} دقيقة" : 'غير محدد')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('passing_score')
                    ->label('درجة النجاح')
                    ->formatStateUsing(fn ($state) => "{$state}%"),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),

                Tables\Columns\IconColumn::make('randomize_questions')
                    ->label('عشوائي')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrows-right-left')
                    ->falseIcon('heroicon-o-bars-3')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('assignments_count')
                    ->label('عدد التعيينات')
                    ->counts('assignments')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة'),
            ])
            ->actions([
                static::getAssignAction(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Get the assign action with panel-specific configuration.
     */
    protected static function getAssignAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('assign')
            ->label('تعيين')
            ->icon('heroicon-o-link')
            ->color('success')
            ->form([
                Forms\Components\Select::make('assignable_type')
                    ->label(static::getAssignableTypeLabel())
                    ->options(static::getAssignableTypes())
                    ->required()
                    ->live(),

                Forms\Components\Select::make('assignable_id')
                    ->label(static::getAssignableTargetLabel())
                    ->options(fn (Forms\Get $get) => static::getAssignableOptions($get('assignable_type')))
                    ->required()
                    ->searchable()
                    ->preload(),

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
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()
            ->where('academy_id', $tenant?->id);
    }
}
