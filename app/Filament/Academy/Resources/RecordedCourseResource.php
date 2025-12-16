<?php

namespace App\Filament\Academy\Resources;

use App\Filament\Academy\Resources\RecordedCourseResource\Pages;
use App\Filament\Academy\Resources\RecordedCourseResource\RelationManagers;
use App\Filament\Academy\Resources\RecordedCourseResource\RelationManagers\SectionsRelationManager;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\AcademicTeacherProfile;
use App\Models\RecordedCourse;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class RecordedCourseResource extends Resource
{
    protected static ?string $model = RecordedCourse::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'إدارة الدورات المسجلة';

    protected static ?string $navigationLabel = 'الدورات المسجلة';

    protected static ?string $modelLabel = 'دورة مسجلة';

    protected static ?string $pluralModelLabel = 'الدورات المسجلة';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Course Creation')
                    ->tabs([
                        Tabs\Tab::make('المعلومات الأساسية')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('المعلومات الأساسية')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('title')
                                                    ->label('عنوان الدورة')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('أدخل عنوان الدورة باللغة العربية'),

                                                TextInput::make('title_en')
                                                    ->label('Course Title (English)')
                                                    ->maxLength(255)
                                                    ->placeholder('Enter course title in English'),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('course_code')
                                                    ->label('رمز الدورة')
                                                    ->required()
                                                    ->unique(ignoreRecord: true)
                                                    ->maxLength(50)
                                                    ->placeholder('مثال: MATH101'),

                                                Select::make('instructor_id')
                                                    ->label('المدرب')
                                                    ->options(function () {
                                                        $academyId = Auth::user()->academy_id;

                                                        return AcademicTeacherProfile::where('academy_id', $academyId)
                                                            ->where('is_approved', true)
                                                            ->where('is_active', true)
                                                            ->pluck('full_name', 'id');
                                                    })
                                                    ->searchable()
                                                    ->required()
                                                    ->placeholder('اختر المدرب'),
                                            ]),

                                        RichEditor::make('description')
                                            ->label('وصف الدورة')
                                            ->required()
                                            ->columnSpanFull()
                                            ->default(null)
                                            ->placeholder('أدخل وصف مفصل للدورة باللغة العربية'),

                                        Textarea::make('description_en')
                                            ->label('Course Description (English)')
                                            ->rows(3)
                                            ->columnSpanFull()
                                            ->default(null)
                                            ->placeholder('Enter course description in English'),
                                    ])
                                    ->collapsible(),

                                Section::make('التصنيف الأكاديمي')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('subject_id')
                                                    ->label('المادة الدراسية')
                                                    ->options(function () {
                                                        $academyId = Auth::user()->academy_id;

                                                        return AcademicSubject::where('academy_id', $academyId)
                                                            ->where('is_active', true)
                                                            ->pluck('name', 'id');
                                                    })
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->placeholder('اختر المادة الدراسية'),

                                                Select::make('grade_level_id')
                                                    ->label('المستوى الدراسي')
                                                    ->options(function () {
                                                        $academyId = Auth::user()->academy_id;

                                                        return AcademicGradeLevel::where('academy_id', $academyId)
                                                            ->where('is_active', true)
                                                            ->pluck('name', 'id');
                                                    })
                                                    ->searchable()
                                                    ->required()
                                                    ->placeholder('اختر المستوى الدراسي'),
                                            ]),

                                        Grid::make(3)
                                            ->schema([
                                                Select::make('level')
                                                    ->label('مستوى الدورة')
                                                    ->options([
                                                        'beginner' => 'مبتدئ',
                                                        'intermediate' => 'متوسط',
                                                        'advanced' => 'متقدم',
                                                    ])
                                                    ->required()
                                                    ->default('intermediate'),

                                                Select::make('difficulty_level')
                                                    ->label('مستوى الصعوبة')
                                                    ->options([
                                                        'easy' => 'سهل',
                                                        'medium' => 'متوسط',
                                                        'hard' => 'صعب',
                                                    ])
                                                    ->required()
                                                    ->default('medium'),

                                                Select::make('category')
                                                    ->label('فئة الدورة')
                                                    ->options([
                                                        'academic' => 'أكاديمي',
                                                        'skills' => 'مهارات',
                                                        'language' => 'لغة',
                                                        'technology' => 'تقنية',
                                                        'arts' => 'فنون',
                                                        'other' => 'أخرى',
                                                    ])
                                                    ->required()
                                                    ->default('academic'),
                                            ]),
                                    ])
                                    ->collapsible(),

                                Section::make('الوسائط')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\FileUpload::make('thumbnail_url')
                                                    ->label('صورة مصغرة للدورة')
                                                    ->image()
                                                    ->disk('public')
                                                    ->directory('courses/thumbnails')
                                                    ->visibility('public')
                                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                                    ->maxSize(10240) // 10MB
                                                    ->placeholder('اختر صورة مصغرة للدورة')
                                                    ->getUploadedFileNameForStorageUsing(
                                                        fn (TemporaryUploadedFile $file): string => 'course_thumbnail_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension()
                                                    )
                                                    ->optimize('webp')
                                                    ->resize(800, 600),
                                            ]),
                                    ])
                                    ->collapsible(),

                                Section::make('التسعير')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Toggle::make('is_free')
                                                    ->label('دورة مجانية')
                                                    ->default(false)
                                                    ->reactive(),

                                                TextInput::make('price')
                                                    ->label('السعر')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->visible(fn (Get $get): bool => ! $get('is_free'))
                                                    ->required(fn (Get $get): bool => ! $get('is_free'))
                                                    ->placeholder('0.00'),

                                                TextInput::make('discount_price')
                                                    ->label('سعر الخصم')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->visible(fn (Get $get): bool => ! $get('is_free'))
                                                    ->placeholder('0.00'),
                                            ]),

                                        Select::make('currency')
                                            ->label('العملة')
                                            ->options([
                                                'USD' => 'دولار أمريكي',
                                                'SAR' => 'ريال سعودي',
                                                'AED' => 'درهم إماراتي',
                                                'EGP' => 'جنيه مصري',
                                            ])
                                            ->default('USD')
                                            ->required(),
                                    ])
                                    ->collapsible(),

                                Section::make('محتوى الدورة')
                                    ->schema([
                                        KeyValue::make('prerequisites')
                                            ->label('المتطلبات المسبقة')
                                            ->keyLabel('المتطلب')
                                            ->valueLabel('الوصف')
                                            ->addActionLabel('إضافة متطلب')
                                            ->placeholder('أدخل المتطلبات المسبقة للدورة'),

                                        KeyValue::make('learning_outcomes')
                                            ->label('نتائج التعلم')
                                            ->keyLabel('النتيجة')
                                            ->valueLabel('الوصف')
                                            ->addActionLabel('إضافة نتيجة تعلم')
                                            ->placeholder('أدخل نتائج التعلم المتوقعة'),

                                        KeyValue::make('course_materials')
                                            ->label('المواد التعليمية')
                                            ->keyLabel('المادة')
                                            ->valueLabel('الوصف')
                                            ->addActionLabel('إضافة مادة')
                                            ->placeholder('أدخل المواد التعليمية المطلوبة'),

                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('total_sections')
                                                    ->label('عدد الأقسام')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->default(1)
                                                    ->required(),

                                                TextInput::make('total_lessons')
                                                    ->label('عدد الدروس')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->default(1)
                                                    ->required(),

                                                TextInput::make('duration_hours')
                                                    ->label('المدة بالساعات')
                                                    ->numeric()
                                                    ->minValue(0.5)
                                                    ->step(0.5)
                                                    ->default(1)
                                                    ->required(),
                                            ]),
                                    ])
                                    ->collapsible(),

                                Section::make('الإعدادات')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Toggle::make('is_published')
                                                    ->label('منشور')
                                                    ->default(false)
                                                    ->helperText('الدورة ستكون مرئية للطلاب عند النشر'),

                                                Toggle::make('is_featured')
                                                    ->label('مميزة')
                                                    ->default(false)
                                                    ->helperText('ستظهر الدورة في القسم المميز'),
                                            ]),

                                        Toggle::make('completion_certificate')
                                            ->label('شهادة إتمام')
                                            ->default(true)
                                            ->helperText('سيحصل الطلاب على شهادة عند إتمام الدورة'),

                                        TagsInput::make('tags')
                                            ->label('العلامات')
                                            ->separator(',')
                                            ->placeholder('أدخل العلامات مفصولة بفواصل'),

                                        Textarea::make('meta_description')
                                            ->label('وصف SEO')
                                            ->rows(2)
                                            ->maxLength(160)
                                            ->helperText('وصف مختصر للدورة لتحسين محركات البحث'),

                                        Textarea::make('notes')
                                            ->label('ملاحظات')
                                            ->rows(3)
                                            ->placeholder('ملاحظات إضافية للدورة'),
                                    ])
                                    ->collapsible(),
                            ]),

                        Tabs\Tab::make('دروس الدورة')
                            ->icon('heroicon-o-play')
                            ->schema([
                                Section::make('دروس الدورة')
                                    ->description('أضف دروس الدورة وحدد المحتوى لكل درس')
                                    ->schema([
                                        Repeater::make('lessons')
                                            ->label('الدروس')
                                            ->relationship('lessons')
                                            ->schema([
                                                Grid::make(2)
                                                    ->schema([
                                                        TextInput::make('title')
                                                            ->label('عنوان الدرس')
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->placeholder('أدخل عنوان الدرس'),

                                                        TextInput::make('title_en')
                                                            ->label('Lesson Title (English)')
                                                            ->maxLength(255)
                                                            ->placeholder('Enter lesson title in English'),
                                                    ]),

                                                TextInput::make('order')
                                                    ->label('ترتيب الدرس')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required()
                                                    ->placeholder('ترتيب الدرس في الدورة'),

                                                Forms\Components\FileUpload::make('video_url')
                                                    ->label('فيديو الدرس')
                                                    ->disk('public')
                                                    ->directory('lessons/videos')
                                                    ->visibility('public')
                                                    ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/mov', 'video/avi'])
                                                    ->maxSize(512 * 1024) // 512MB - matches Livewire config
                                                    ->chunkedUpload() // Enable chunked uploads for large video files
                                                    // ->required() // Temporarily disabled to test form save
                                                    ->columnSpanFull()
                                                    ->placeholder('اختر فيديو الدرس')
                                                    ->getUploadedFileNameForStorageUsing(
                                                        fn (TemporaryUploadedFile $file): string => 'lesson_video_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension()
                                                    )
                                                    ->uploadingMessage('جاري رفع الفيديو...')
                                                    ->removeUploadedFileButtonPosition('right')
                                                    ->uploadProgressIndicatorPosition('left'),

                                                RichEditor::make('description')
                                                    ->label('وصف الدرس')
                                                    ->required()
                                                    ->columnSpanFull()
                                                    ->default(null)
                                                    ->placeholder('أدخل وصف مفصل للدرس'),

                                                Textarea::make('description_en')
                                                    ->label('Lesson Description (English)')
                                                    ->rows(3)
                                                    ->columnSpanFull()
                                                    ->default(null)
                                                    ->placeholder('Enter lesson description in English'),

                                                KeyValue::make('learning_objectives')
                                                    ->label('أهداف التعلم')
                                                    ->keyLabel('الهدف')
                                                    ->valueLabel('الوصف')
                                                    ->addActionLabel('إضافة هدف')
                                                    ->columnSpanFull(),

                                                Forms\Components\FileUpload::make('attachments')
                                                    ->label('مرفقات الدرس')
                                                    ->multiple()
                                                    ->disk('public')
                                                    ->directory('lessons/attachments')
                                                    ->visibility('public')
                                                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/*'])
                                                    ->maxSize(51200) // 50MB per file
                                                    ->columnSpanFull()
                                                    ->getUploadedFileNameForStorageUsing(
                                                        fn (TemporaryUploadedFile $file): string => 'lesson_attachment_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension()
                                                    )
                                                    ->uploadingMessage('جاري رفع المرفقات...')
                                                    ->reorderable(),

                                                Grid::make(3)
                                                    ->schema([
                                                        Toggle::make('is_published')
                                                            ->label('منشور')
                                                            ->default(true),

                                                        Toggle::make('is_free_preview')
                                                            ->label('معاينة مجانية')
                                                            ->default(false)
                                                            ->helperText('يمكن للطلاب مشاهدة هذا الدرس مجاناً'),

                                                        Toggle::make('is_downloadable')
                                                            ->label('قابل للتحميل')
                                                            ->default(false)
                                                            ->helperText('يمكن للطلاب تحميل هذا الدرس'),
                                                    ]),

                                            ])
                                            ->defaultItems(1)
                                            ->addActionLabel('إضافة درس جديد')
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'درس جديد')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('course_code')
                    ->label('رمز الدورة')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان الدورة')
                    ->searchable()
                    ->limit(50)
                    ->sortable(),

                Tables\Columns\TextColumn::make('instructor.user.name')
                    ->label('المدرب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('المادة')
                    ->sortable(),

                Tables\Columns\TextColumn::make('gradeLevel.name')
                    ->label('المستوى')
                    ->sortable(),

                Tables\Columns\TextColumn::make('level')
                    ->label('المستوى')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'beginner' => 'success',
                        'intermediate' => 'warning',
                        'advanced' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'beginner' => 'مبتدئ',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                    }),

                Tables\Columns\TextColumn::make('price')
                    ->label('السعر')
                    ->money('SAR')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_free')
                    ->label('مجانية')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('منشور')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('مميزة')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star'),

                Tables\Columns\TextColumn::make('total_enrollments')
                    ->label('التسجيلات')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('avg_rating')
                    ->label('التقييم')
                    ->numeric(
                        decimalPlaces: 1,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('subject_id')
                    ->label('المادة الدراسية')
                    ->options(function () {
                        $academyId = Auth::user()->academy_id;

                        return AcademicSubject::where('academy_id', $academyId)
                            ->where('is_active', true)
                            ->pluck('name', 'id');
                    }),

                SelectFilter::make('grade_level_id')
                    ->label('المستوى الدراسي')
                    ->options(function () {
                        $academyId = Auth::user()->academy_id;

                        return AcademicGradeLevel::where('academy_id', $academyId)
                            ->where('is_active', true)
                            ->pluck('name', 'id');
                    }),

                SelectFilter::make('instructor_id')
                    ->label('المدرب')
                    ->options(function () {
                        $academyId = Auth::user()->academy_id;

                        return AcademicTeacherProfile::with('user')
                            ->where('academy_id', $academyId)
                            ->where('is_approved', true)
                            ->where('is_active', true)
                            ->get()
                            ->pluck('user.name', 'id')
                            ->toArray();
                    }),

                SelectFilter::make('level')
                    ->label('مستوى الدورة')
                    ->options([
                        'beginner' => 'مبتدئ',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                    ]),

                SelectFilter::make('category')
                    ->label('فئة الدورة')
                    ->options([
                        'academic' => 'أكاديمي',
                        'skills' => 'مهارات',
                        'language' => 'لغة',
                        'technology' => 'تقنية',
                        'arts' => 'فنون',
                        'other' => 'أخرى',
                    ]),

                TernaryFilter::make('is_free')
                    ->label('دورة مجانية'),

                TernaryFilter::make('is_published')
                    ->label('منشور'),

                TernaryFilter::make('is_featured')
                    ->label('مميزة'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('publish')
                    ->label('نشر')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (RecordedCourse $record) {
                        $record->update(['is_published' => true]);
                    })
                    ->visible(fn (RecordedCourse $record): bool => ! $record->is_published),

                Tables\Actions\Action::make('unpublish')
                    ->label('إلغاء النشر')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (RecordedCourse $record) {
                        $record->update(['is_published' => false]);
                    })
                    ->visible(fn (RecordedCourse $record): bool => $record->is_published),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('publish')
                        ->label('نشر المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_published' => true]);
                            });
                        }),

                    Tables\Actions\BulkAction::make('unpublish')
                        ->label('إلغاء نشر المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_published' => false]);
                            });
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Filter by current user's academy
        if (Auth::user()->academy_id) {
            $query->where('academy_id', Auth::user()->academy_id);
        }

        // If user is a teacher, only show their courses
        if (Auth::user()->isAcademicTeacher()) {
            $query->where('instructor_id', Auth::user()->academicTeacher->id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            SectionsRelationManager::class,
            RelationManagers\LessonsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecordedCourses::route('/'),
            'create' => Pages\CreateRecordedCourse::route('/create'),
            'edit' => Pages\EditRecordedCourse::route('/{record}/edit'),
            'view' => Pages\ViewRecordedCourse::route('/{record}'),
        ];
    }
}
