<?php

namespace App\Filament\Shared\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Resources\BaseResource;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base Quiz Resource
 *
 * Shared functionality for Teacher and AcademicTeacher panels.
 * Child classes must implement assignable type configuration and getPages().
 */
abstract class BaseQuizResource extends BaseResource
{
    protected static ?string $model = Quiz::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string | \UnitEnum | null $navigationGroup = 'الاختبارات';

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الاختبار')
                    ->schema([
                        TextInput::make('title')
                            ->label('عنوان الاختبار')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label('وصف الاختبار')
                            ->rows(3)
                            ->maxLength(1000),

                        TextInput::make('duration_minutes')
                            ->label('المدة (بالدقائق)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(180)
                            ->helperText('اتركه فارغاً لاختبار بدون وقت محدد'),

                        TextInput::make('passing_score')
                            ->label('درجة النجاح (%)')
                            ->numeric()
                            ->default(60)
                            ->minValue(10)
                            ->maxValue(90)
                            ->required()
                            ->suffix('%')
                            ->helperText('يجب أن تكون درجة النجاح بين 10% و 90%'),

                        Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true),

                        Toggle::make('randomize_questions')
                            ->label('ترتيب عشوائي للأسئلة')
                            ->helperText('عند التفعيل، ستظهر الأسئلة بترتيب مختلف لكل طالب')
                            ->default(false),
                    ])
                    ->columns(2),

                Section::make('الأسئلة')
                    ->schema([
                        Repeater::make('questions')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Textarea::make('question_text')
                                    ->label('نص السؤال')
                                    ->required()
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Repeater::make('options')
                                    ->label('الخيارات')
                                    ->simple(
                                        TextInput::make('option')
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

                                Select::make('correct_option')
                                    ->label('الإجابة الصحيحة')
                                    ->options(function (Get $get): array {
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

                                Hidden::make('order')
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
                static::getAcademyColumn(),

                TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('questions_count')
                    ->label('عدد الأسئلة')
                    ->counts('questions')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('duration_minutes')
                    ->label('المدة')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} دقيقة" : 'غير محدد')
                    ->toggleable(),

                TextColumn::make('passing_score')
                    ->label('درجة النجاح')
                    ->formatStateUsing(fn ($state) => "{$state}%")
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),

                IconColumn::make('randomize_questions')
                    ->label('عشوائي')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrows-right-left')
                    ->falseIcon('heroicon-o-bars-3')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                TextColumn::make('assignments_count')
                    ->label('عدد التعيينات')
                    ->counts('assignments')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordActions([
                static::getAssignAction(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Get the assign action with panel-specific configuration.
     */
    protected static function getAssignAction(): Action
    {
        return Action::make('assign')
            ->label('تعيين')
            ->icon('heroicon-o-link')
            ->color('success')
            ->schema([
                Select::make('assignable_type')
                    ->label(static::getAssignableTypeLabel())
                    ->options(static::getAssignableTypes())
                    ->required()
                    ->live(),

                Select::make('assignable_id')
                    ->label(static::getAssignableTargetLabel())
                    ->options(fn (Get $get) => static::getAssignableOptions($get('assignable_type')))
                    ->required()
                    ->searchable()
                    ->preload(),

                Toggle::make('is_visible')
                    ->label('مرئي للطلاب')
                    ->default(true),

                TextInput::make('max_attempts')
                    ->label('عدد المحاولات')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->maxValue(10),

                DateTimePicker::make('available_from')
                    ->label('متاح من')
                    ->native(false)
                    ->seconds(false)
                    ->displayFormat('Y-m-d H:i')
                    ->placeholder('اتركه فارغاً للإتاحة فوراً'),

                DateTimePicker::make('available_until')
                    ->label('متاح حتى')
                    ->native(false)
                    ->seconds(false)
                    ->displayFormat('Y-m-d H:i')
                    ->placeholder('اتركه فارغاً للإتاحة دائماً')
                    ->after('available_from'),
            ])
            ->action(function (Quiz $record, array $data) {
                $teacherId = auth()->id();
                $assignableType = $data['assignable_type'];
                $assignableId = (int) $data['assignable_id'];

                // Verify the assignable belongs to the current teacher before creating assignment
                $ownershipVerified = match (true) {
                    $assignableType === QuranCircle::class,
                    $assignableType === \App\Enums\QuizAssignableType::QURAN_CIRCLE->value =>
                        QuranCircle::where('id', $assignableId)
                            ->where('quran_teacher_id', $teacherId)
                            ->exists(),
                    $assignableType === QuranIndividualCircle::class,
                    $assignableType === \App\Enums\QuizAssignableType::QURAN_INDIVIDUAL_CIRCLE->value =>
                        QuranIndividualCircle::where('id', $assignableId)
                            ->where('quran_teacher_id', $teacherId)
                            ->exists(),
                    default => false,
                };

                if (! $ownershipVerified) {
                    Notification::make()
                        ->title(__('filament-panels::pages/auth/login.messages.failed'))
                        ->danger()
                        ->send();

                    return;
                }

                QuizAssignment::create([
                    'quiz_id' => $record->id,
                    'assignable_type' => $assignableType,
                    'assignable_id' => $assignableId,
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
        $academyId = $tenant?->id ?? \App\Services\AcademyContextService::getCurrentAcademyId();

        if (! $academyId) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
            ->where('academy_id', $academyId);
    }
}
