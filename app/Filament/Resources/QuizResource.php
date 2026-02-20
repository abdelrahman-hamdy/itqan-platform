<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuizResource\Pages\CreateQuiz;
use App\Filament\Resources\QuizResource\Pages\EditQuiz;
use App\Filament\Resources\QuizResource\Pages\ListQuizzes;
use App\Filament\Resources\QuizResource\Pages\ViewQuiz;
use App\Filament\Resources\QuizResource\RelationManagers\QuestionsRelationManager;
use App\Models\Academy;
use App\Models\Quiz;
use App\Services\AcademyContextService;
use Closure;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuizResource extends BaseResource
{
    protected static ?string $model = Quiz::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الاختبارات';

    protected static ?string $navigationLabel = 'بنك الاختبارات';

    protected static ?string $modelLabel = 'اختبار';

    protected static ?string $pluralModelLabel = 'الاختبارات';

    /**
     * Navigation badge showing quizzes without questions (warning)
     */
    public static function getNavigationBadge(): ?string
    {
        $currentAcademy = AcademyContextService::getCurrentAcademy();
        $query = Quiz::query()->doesntHave('questions');

        if ($currentAcademy) {
            $query->where('academy_id', $currentAcademy->id);
        }

        $count = $query->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * Badge color - warning for quizzes without questions
     */
    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    /**
     * Badge tooltip
     */
    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'اختبارات بدون أسئلة';
    }

    public static function form(Schema $schema): Schema
    {
        $currentAcademy = AcademyContextService::getCurrentAcademy();

        return $schema
            ->components([
                Section::make('معلومات الاختبار')
                    ->schema([
                        Select::make('academy_id')
                            ->label('الأكاديمية')
                            ->options(Academy::pluck('name', 'id'))
                            ->default($currentAcademy?->id)
                            ->disabled($currentAcademy !== null)
                            ->required(),

                        TextInput::make('title')
                            ->label('عنوان الاختبار')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('أدخل عنوان الاختبار'),

                        Textarea::make('description')
                            ->label('وصف الاختبار')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('أدخل وصف الاختبار (اختياري)'),

                        TextInput::make('duration_minutes')
                            ->label('المدة (بالدقائق)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(180)
                            ->placeholder('اتركه فارغاً لاختبار بدون وقت محدد')
                            ->helperText('اتركه فارغاً إذا كان الاختبار غير محدد بوقت'),

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
                            ->default(true)
                            ->helperText('الاختبارات غير النشطة لا يمكن تعيينها')
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, Closure $fail) {
                                        // Only validate when activating
                                        if (! $value) {
                                            return;
                                        }

                                        // Check if editing an existing quiz
                                        $record = request()->route('record');
                                        if ($record) {
                                            $quiz = Quiz::find($record);
                                            if ($quiz && $quiz->questions()->count() === 0) {
                                                $fail('لا يمكن تفعيل اختبار بدون أسئلة. أضف أسئلة أولاً.');
                                            }
                                        }
                                    };
                                },
                            ]),

                        Toggle::make('randomize_questions')
                            ->label('ترتيب عشوائي للأسئلة')
                            ->helperText('عند التفعيل، ستظهر الأسئلة بترتيب مختلف لكل طالب')
                            ->default(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('passing_score')
                    ->label('درجة النجاح')
                    ->formatStateUsing(fn ($state) => "{$state}%")
                    ->sortable()
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
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('عرض'),
                    EditAction::make()->label('تعديل'),
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
        return [
            QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuizzes::route('/'),
            'create' => CreateQuiz::route('/create'),
            'view' => ViewQuiz::route('/{record}'),
            'edit' => EditQuiz::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Include soft-deleted records for admin management
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        $currentAcademy = AcademyContextService::getCurrentAcademy();
        if ($currentAcademy) {
            $query->where('academy_id', $currentAcademy->id);
        }

        return $query;
    }
}
