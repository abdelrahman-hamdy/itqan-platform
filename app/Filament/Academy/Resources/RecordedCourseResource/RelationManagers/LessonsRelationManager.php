<?php

namespace App\Filament\Academy\Resources\RecordedCourseResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\CourseSection;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    protected static ?string $title = 'دروس الدورة';

    protected static ?string $modelLabel = 'درس';

    protected static ?string $pluralModelLabel = 'الدروس';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الدرس')
                    ->schema([
                        TextInput::make('title')
                            ->label('عنوان الدرس')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('أدخل عنوان الدرس')
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Select::make('course_section_id')
                                    ->label('القسم')
                                    ->options(function () {
                                        return CourseSection::where('recorded_course_id', $this->getOwnerRecord()->id)
                                            ->pluck('title', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->placeholder('اختر القسم'),

                                TextInput::make('lesson_code')
                                    ->label('رمز الدرس')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(50)
                                    ->placeholder('مثال: LESSON001')
                                    ->helperText('رمز فريد للدرس'),
                            ]),

                        Textarea::make('description')
                            ->label('وصف الدرس')
                            ->rows(3)
                            ->placeholder('وصف مختصر لمحتوى الدرس')
                            ->columnSpanFull(),
                    ]),

                Section::make('محتوى الفيديو')
                    ->schema([
                        TextInput::make('video_url')
                            ->label('رابط الفيديو')
                            ->required()
                            ->url()
                            ->placeholder('https://example.com/video.mp4')
                            ->helperText('رابط مباشر للفيديو'),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('video_duration_seconds')
                                    ->label('مدة الفيديو (ثانية)')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->placeholder('3600'),

                                TextInput::make('video_size_mb')
                                    ->label('حجم الفيديو (ميجا)')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->placeholder('150.5'),

                                Select::make('video_quality')
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

                        RichEditor::make('transcript')
                            ->label('نص الفيديو (Transcript)')
                            ->placeholder('النص المكتوب لمحتوى الفيديو')
                            ->columnSpanFull(),
                    ]),

                Section::make('إعدادات الدرس')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('order')
                                    ->label('الترتيب')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(1),

                                Select::make('lesson_type')
                                    ->label('نوع الدرس')
                                    ->options([
                                        'video' => 'فيديو',
                                        'quiz' => 'اختبار',
                                        'assignment' => 'مهمة',
                                        'reading' => 'قراءة',
                                    ])
                                    ->default('video')
                                    ->required(),

                                Select::make('difficulty_level')
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

                        Grid::make(2)
                            ->schema([
                                TextInput::make('estimated_study_time_minutes')
                                    ->label('وقت الدراسة المقدر (دقيقة)')
                                    ->numeric()
                                    ->default(30)
                                    ->minValue(0)
                                    ->helperText('الوقت المقدر لإكمال الدرس'),

                                TagsInput::make('learning_objectives')
                                    ->label('أهداف التعلم')
                                    ->placeholder('أضف هدف تعليمي')
                                    ->helperText('اضغط Enter لإضافة هدف جديد'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Toggle::make('is_published')
                                    ->label('منشور')
                                    ->default(true)
                                    ->helperText('الدرس مرئي للطلاب'),

                                Toggle::make('is_free_preview')
                                    ->label('معاينة مجانية')
                                    ->default(false)
                                    ->helperText('يمكن مشاهدته بدون اشتراك'),

                                Toggle::make('is_downloadable')
                                    ->label('قابل للتحميل')
                                    ->default(false)
                                    ->helperText('السماح بتحميل الفيديو'),
                            ]),
                    ]),

                Section::make('محتوى إضافي')
                    ->schema([
                        RichEditor::make('notes')
                            ->label('ملاحظات إضافية')
                            ->placeholder('ملاحظات مهمة للدرس')
                            ->columnSpanFull(),

                        KeyValue::make('attachments')
                            ->label('المرفقات')
                            ->keyLabel('اسم الملف')
                            ->valueLabel('رابط الملف')
                            ->addActionLabel('إضافة مرفق')
                            ->columnSpanFull(),

                        KeyValue::make('assignment_requirements')
                            ->label('متطلبات المهمة')
                            ->keyLabel('المتطلب')
                            ->valueLabel('الوصف')
                            ->addActionLabel('إضافة متطلب')
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => $get('lesson_type') === 'assignment'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('order')
                    ->label('الترتيب')
                    ->sortable()
                    ->width(80),

                TextColumn::make('title')
                    ->label('عنوان الدرس')
                    ->searchable()
                    ->limit(50)
                    ->sortable(),

                TextColumn::make('section.title')
                    ->label('القسم')
                    ->sortable()
                    ->limit(30),

                TextColumn::make('lesson_type')
                    ->badge()
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

                TextColumn::make('video_duration_seconds')
                    ->label('المدة')
                    ->formatStateUsing(fn (int $state): string => $state >= 3600
                            ? floor($state / 3600).'س '.floor(($state % 3600) / 60).'د'
                            : floor($state / 60).'د '.($state % 60).'ث'
                    )
                    ->sortable(),

                TextColumn::make('difficulty_level')
                    ->badge()
                    ->label('الصعوبة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'easy' => 'سهل',
                        'medium' => 'متوسط',
                        'hard' => 'صعب',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'easy',
                        'warning' => 'medium',
                        'danger' => 'hard',
                    ]),

                IconColumn::make('is_published')
                    ->label('منشور')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                IconColumn::make('is_free_preview')
                    ->label('مجاني')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash'),

                TextColumn::make('view_count')
                    ->label('المشاهدات')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('course_section_id')
                    ->label('القسم')
                    ->relationship('section', 'title')
                    ->multiple(),

                SelectFilter::make('lesson_type')
                    ->label('نوع الدرس')
                    ->options([
                        'video' => 'فيديو',
                        'quiz' => 'اختبار',
                        'assignment' => 'مهمة',
                        'reading' => 'قراءة',
                    ])
                    ->multiple(),

                SelectFilter::make('difficulty_level')
                    ->label('مستوى الصعوبة')
                    ->options([
                        'very_easy' => 'سهل جداً',
                        'easy' => 'سهل',
                        'medium' => 'متوسط',
                        'hard' => 'صعب',
                        'very_hard' => 'صعب جداً',
                    ])
                    ->multiple(),

                TernaryFilter::make('is_published')
                    ->label('منشور')
                    ->placeholder('الكل')
                    ->trueLabel('منشور')
                    ->falseLabel('غير منشور'),

                TernaryFilter::make('is_free_preview')
                    ->label('معاينة مجانية')
                    ->placeholder('الكل')
                    ->trueLabel('مجاني')
                    ->falseLabel('مدفوع'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('إضافة درس جديد')
                    ->mutateDataUsing(function (array $data): array {
                        $data['recorded_course_id'] = $this->getOwnerRecord()->id;

                        return $data;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
                DeleteAction::make()
                    ->label('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->paginationPageOptions([10, 25, 50]);
    }
}
