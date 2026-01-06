<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuizResource\Pages;
use App\Filament\Resources\QuizResource\RelationManagers;
use App\Models\Academy;
use App\Models\Quiz;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuizResource extends BaseResource
{
    protected static ?string $model = Quiz::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'إدارة الاختبارات';

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

    public static function form(Form $form): Form
    {
        $currentAcademy = AcademyContextService::getCurrentAcademy();

        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الاختبار')
                    ->schema([
                        Forms\Components\Select::make('academy_id')
                            ->label('الأكاديمية')
                            ->options(Academy::pluck('name', 'id'))
                            ->default($currentAcademy?->id)
                            ->disabled($currentAcademy !== null)
                            ->required(),

                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الاختبار')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('أدخل عنوان الاختبار'),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف الاختبار')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('أدخل وصف الاختبار (اختياري)'),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('المدة (بالدقائق)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(180)
                            ->placeholder('اتركه فارغاً لاختبار بدون وقت محدد')
                            ->helperText('اتركه فارغاً إذا كان الاختبار غير محدد بوقت'),

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
                            ->default(true)
                            ->helperText('الاختبارات غير النشطة لا يمكن تعيينها')
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
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

                        Forms\Components\Toggle::make('randomize_questions')
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('passing_score')
                    ->label('درجة النجاح')
                    ->formatStateUsing(fn ($state) => "{$state}%")
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),

                Tables\Columns\IconColumn::make('randomize_questions')
                    ->label('عشوائي')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrows-right-left')
                    ->falseIcon('heroicon-o-bars-3')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('assignments_count')
                    ->label('عدد التعيينات')
                    ->counts('assignments'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ReplicateAction::make()
                    ->label('نسخ')
                    ->beforeReplicaSaved(function (Quiz $replica): void {
                        $replica->title = $replica->title.' (نسخة)';
                        $replica->is_active = false; // Start as inactive
                    })
                    ->afterReplicaSaved(function (Quiz $original, Quiz $replica): void {
                        // Copy questions to the replica
                        foreach ($original->questions as $question) {
                            $newQuestion = $question->replicate(['quiz_id']);
                            $newQuestion->quiz_id = $replica->id;
                            $newQuestion->save();
                        }
                    })
                    ->successNotificationTitle('تم نسخ الاختبار مع أسئلته بنجاح'),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuizzes::route('/'),
            'create' => Pages\CreateQuiz::route('/create'),
            'view' => Pages\ViewQuiz::route('/{record}'),
            'edit' => Pages\EditQuiz::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
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
