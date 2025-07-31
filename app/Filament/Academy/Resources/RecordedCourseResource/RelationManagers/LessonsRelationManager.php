<?php

namespace App\Filament\Academy\Resources\RecordedCourseResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\CourseSection;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    protected static ?string $title = 'دروس الدورة';

    protected static ?string $modelLabel = 'درس';

    protected static ?string $pluralModelLabel = 'الدروس';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الدرس')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('عنوان الدرس')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('أدخل عنوان الدرس'),

                                Forms\Components\TextInput::make('title_en')
                                    ->label('Lesson Title (English)')
                                    ->maxLength(255)
                                    ->placeholder('Enter lesson title in English'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('course_section_id')
                                    ->label('القسم')
                                    ->options(function () {
                                        return CourseSection::where('recorded_course_id', $this->getOwnerRecord()->id)
                                            ->pluck('title', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->placeholder('اختر القسم'),

                                Forms\Components\TextInput::make('lesson_code')
                                    ->label('رمز الدرس')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(50)
                                    ->placeholder('مثال: LESSON001')
                                    ->helperText('رمز فريد للدرس'),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف الدرس')
                            ->rows(3)
                            ->placeholder('وصف مختصر لمحتوى الدرس'),

                        Forms\Components\Textarea::make('description_en')
                            ->label('Lesson Description (English)')
                            ->rows(3)
                            ->placeholder('Brief description of lesson content'),
                    ]),

                Forms\Components\Section::make('محتوى الفيديو')
                    ->schema([
                        Forms\Components\TextInput::make('video_url')
                            ->label('رابط الفيديو')
                            ->required()
                            ->url()
                            ->placeholder('https://example.com/video.mp4')
                            ->helperText('رابط مباشر للفيديو'),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('video_duration_seconds')
                                    ->label('مدة الفيديو (ثانية)')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->placeholder('3600'),

                                Forms\Components\TextInput::make('video_size_mb')
                                    ->label('حجم الفيديو (ميجا)')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->placeholder('150.5'),

                                Forms\Components\Select::make('video_quality')
                                    ->label('جودة الفيديو')
                                    ->options([
                                        '480p' => '480p',
                                        '720p' => '720p',
                                        '1080p' => '1080p',
                                        '4K' => '4K',
                                    ])
                                    ->default('720p')
                                    ->required(),
                            ]),

                        Forms\Components\RichEditor::make('transcript')
                            ->label('نص الفيديو (Transcript)')
                            ->placeholder('النص المكتوب لمحتوى الفيديو')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('إعدادات الدرس')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('order')
                                    ->label('الترتيب')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(1),

                                Forms\Components\Select::make('lesson_type')
                                    ->label('نوع الدرس')
                                    ->options([
                                        'video' => 'فيديو',
                                        'quiz' => 'اختبار',
                                        'assignment' => 'مهمة',
                                        'reading' => 'قراءة',
                                    ])
                                    ->default('video')
                                    ->required(),

                                Forms\Components\Select::make('difficulty_level')
                                    ->label('مستوى الصعوبة')
                                    ->options([
                                        'very_easy' => 'سهل جداً',
                                        'easy' => 'سهل',
                                        'medium' => 'متوسط',
                                        'hard' => 'صعب',
                                        'very_hard' => 'صعب جداً',
                                    ])
                                    ->default('medium')
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('estimated_study_time_minutes')
                                    ->label('وقت الدراسة المقدر (دقيقة)')
                                    ->numeric()
                                    ->default(30)
                                    ->minValue(0)
                                    ->helperText('الوقت المقدر لإكمال الدرس'),

                                Forms\Components\TagsInput::make('learning_objectives')
                                    ->label('أهداف التعلم')
                                    ->placeholder('أضف هدف تعليمي')
                                    ->helperText('اضغط Enter لإضافة هدف جديد'),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('is_published')
                                    ->label('منشور')
                                    ->default(true)
                                    ->helperText('الدرس مرئي للطلاب'),

                                Forms\Components\Toggle::make('is_free_preview')
                                    ->label('معاينة مجانية')
                                    ->default(false)
                                    ->helperText('يمكن مشاهدته بدون اشتراك'),

                                Forms\Components\Toggle::make('is_downloadable')
                                    ->label('قابل للتحميل')
                                    ->default(false)
                                    ->helperText('السماح بتحميل الفيديو'),
                            ]),
                    ]),

                Forms\Components\Section::make('محتوى إضافي')
                    ->schema([
                        Forms\Components\RichEditor::make('notes')
                            ->label('ملاحظات إضافية')
                            ->placeholder('ملاحظات مهمة للدرس')
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('attachments')
                            ->label('المرفقات')
                            ->keyLabel('اسم الملف')
                            ->valueLabel('رابط الملف')
                            ->addActionLabel('إضافة مرفق')
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('assignment_requirements')
                            ->label('متطلبات المهمة')
                            ->keyLabel('المتطلب')
                            ->valueLabel('الوصف')
                            ->addActionLabel('إضافة متطلب')
                            ->columnSpanFull()
                            ->visible(fn (Forms\Get $get): bool => $get('lesson_type') === 'assignment'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->label('الترتيب')
                    ->sortable()
                    ->width(80),

                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان الدرس')
                    ->searchable()
                    ->limit(50)
                    ->sortable(),

                Tables\Columns\TextColumn::make('section.title')
                    ->label('القسم')
                    ->sortable()
                    ->limit(30),

                Tables\Columns\BadgeColumn::make('lesson_type')
                    ->label('النوع')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'video' => 'فيديو',
                        'quiz' => 'اختبار',
                        'assignment' => 'مهمة',
                        'reading' => 'قراءة',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'video',
                        'warning' => 'quiz',
                        'success' => 'assignment',
                        'info' => 'reading',
                    ]),

                Tables\Columns\TextColumn::make('video_duration_seconds')
                    ->label('المدة')
                    ->formatStateUsing(fn (int $state): string => 
                        $state >= 3600 
                            ? floor($state / 3600) . 'س ' . floor(($state % 3600) / 60) . 'د'
                            : floor($state / 60) . 'د ' . ($state % 60) . 'ث'
                    )
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('difficulty_level')
                    ->label('الصعوبة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'very_easy' => 'سهل جداً',
                        'easy' => 'سهل',
                        'medium' => 'متوسط',
                        'hard' => 'صعب',
                        'very_hard' => 'صعب جداً',
                        default => $state,
                    })
                    ->colors([
                        'success' => ['very_easy', 'easy'],
                        'warning' => 'medium',
                        'danger' => ['hard', 'very_hard'],
                    ]),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('منشور')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\IconColumn::make('is_free_preview')
                    ->label('مجاني')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash'),

                Tables\Columns\TextColumn::make('view_count')
                    ->label('المشاهدات')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_section_id')
                    ->label('القسم')
                    ->relationship('section', 'title')
                    ->multiple(),

                Tables\Filters\SelectFilter::make('lesson_type')
                    ->label('نوع الدرس')
                    ->options([
                        'video' => 'فيديو',
                        'quiz' => 'اختبار',
                        'assignment' => 'مهمة',
                        'reading' => 'قراءة',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('difficulty_level')
                    ->label('مستوى الصعوبة')
                    ->options([
                        'very_easy' => 'سهل جداً',
                        'easy' => 'سهل',
                        'medium' => 'متوسط',
                        'hard' => 'صعب',
                        'very_hard' => 'صعب جداً',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('منشور')
                    ->placeholder('الكل')
                    ->trueLabel('منشور')
                    ->falseLabel('غير منشور'),

                Tables\Filters\TernaryFilter::make('is_free_preview')
                    ->label('معاينة مجانية')
                    ->placeholder('الكل')
                    ->trueLabel('مجاني')
                    ->falseLabel('مدفوع'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة درس جديد')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['recorded_course_id'] = $this->getOwnerRecord()->id;
                        $data['created_by'] = auth()->id();
                        $data['updated_by'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['updated_by'] = auth()->id();
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->paginationPageOptions([10, 25, 50]);
    }
}