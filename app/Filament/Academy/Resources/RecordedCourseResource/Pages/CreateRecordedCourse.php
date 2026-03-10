<?php

namespace App\Filament\Academy\Resources\RecordedCourseResource\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Actions\Action;
use App\Filament\Academy\Resources\RecordedCourseResource;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\AcademicTeacherProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CreateRecordedCourse extends CreateRecord
{
    protected static string $resource = RecordedCourseResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('course-creation-tabs')
                    ->label('إنشاء دورة مسجلة جديدة')
                    ->tabs([
                        Tab::make('المعلومات الأساسية')
                            ->icon('heroicon-o-information-circle')
                            ->badge(fn (?array $state): ?string => ! empty($state['title']) && ! empty($state['course_code']) && ! empty($state['instructor_id'])
                                    ? '✓' : null
                            )
                            ->badgeColor('success')
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
                                                            ->whereHas('user', fn ($q) => $q->where('active_status', true))
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
                                            ->placeholder('أدخل وصف مفصل للدورة باللغة العربية'),

                                        Textarea::make('description_en')
                                            ->label('Course Description (English)')
                                            ->rows(3)
                                            ->columnSpanFull()
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
                                                Select::make('difficulty_level')
                                                    ->label('مستوى الدورة')
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

                                        Select::make('language')
                                            ->label('لغة الدورة')
                                            ->options([
                                                'ar' => 'العربية',
                                                'en' => 'English',
                                                'ar-en' => 'عربي/إنجليزي',
                                            ])
                                            ->required()
                                            ->default('ar'),
                                    ])
                                    ->collapsible(),

                                Section::make('الوسائط')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                FileUpload::make('thumbnail_url')
                                                    ->label('صورة مصغرة للدورة')
                                                    ->image()
                                                    ->imageEditor()
                                                    ->imageCropAspectRatio('16:9')
                                                    ->imageResizeTargetWidth('400')
                                                    ->imageResizeTargetHeight('225')
                                                    ->directory('courses/thumbnails')
                                                    ->placeholder('اختر صورة مصغرة للدورة'),

                                                FileUpload::make('trailer_video_url')
                                                    ->label('فيديو تعريفي')
                                                    ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg'])
                                                    ->maxSize(100 * 1024) // 100MB
                                                    ->directory('courses/trailers')
                                                    ->placeholder('اختر فيديو تعريفي للدورة'),
                                            ]),
                                    ])
                                    ->collapsible(),

                                Section::make('التسعير')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Toggle::make('is_free')
                                                    ->label('دورة مجانية')
                                                    ->default(false)
                                                    ->live(),

                                                TextInput::make('price')
                                                    ->label('السعر')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->visible(fn (Get $get): bool => ! $get('is_free'))
                                                    ->required(fn (Get $get): bool => ! $get('is_free'))
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
                                            ->addActionLabel('إضافة متطلب'),

                                        KeyValue::make('learning_outcomes')
                                            ->label('نتائج التعلم')
                                            ->keyLabel('النتيجة')
                                            ->valueLabel('الوصف')
                                            ->addActionLabel('إضافة نتيجة تعلم'),

                                        KeyValue::make('course_materials')
                                            ->label('المواد التعليمية')
                                            ->keyLabel('المادة')
                                            ->valueLabel('الوصف')
                                            ->addActionLabel('إضافة مادة'),

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

                        Tab::make('دروس الدورة')
                            ->icon('heroicon-o-play')
                            ->badge(fn (?array $state): ?string => ! empty($state['lessons']) && count($state['lessons']) > 0
                                    ? (string) count($state['lessons']) : null
                            )
                            ->badgeColor('primary')
                            ->schema([
                                Section::make('📚 إدارة دروس الدورة')
                                    ->description('يمكنك إضافة عدد لا محدود من الدروس، وترتيبها، وتحديد محتوى كل درس بشكل منفصل. كل درس يحتوي على فيديو منفصل وإعدادات خاصة به.')
                                    ->headerActions([
                                        Action::make('help')
                                            ->label('مساعدة')
                                            ->icon('heroicon-o-question-mark-circle')
                                            ->color('info')
                                            ->action(fn () => null)
                                            ->modalHeading('كيفية إضافة الدروس')
                                            ->modalDescription('1. انقر على "إضافة درس جديد" لإضافة درس\n2. املأ تفاصيل كل درس\n3. يمكنك ترتيب الدروس باستخدام الأزرار\n4. كل درس له إعدادات منفصلة'),
                                    ])
                                    ->schema([
                                        Repeater::make('lessons')
                                            ->label('دروس الدورة')
                                            ->addActionLabel('➕ إضافة درس جديد')
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

                                                Grid::make(2)
                                                    ->schema([
                                                        TextInput::make('lesson_code')
                                                            ->label('رمز الدرس')
                                                            ->maxLength(50)
                                                            ->placeholder('مثال: LESSON01'),

                                                        TextInput::make('order')
                                                            ->label('ترتيب الدرس')
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->required()
                                                            ->default(fn ($component) => $component->getContainer()->getStatePath() ? count($component->getContainer()->getStatePath()) + 1 : 1)
                                                            ->placeholder('ترتيب الدرس في الدورة')
                                                            ->helperText('سيتم ترقيم الدروس تلقائياً'),
                                                    ]),

                                                FileUpload::make('video_url')
                                                    ->label('🎥 فيديو الدرس')
                                                    ->disk('public')
                                                    ->directory('lessons/videos')
                                                    ->visibility('public')
                                                    ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/mov', 'video/avi'])
                                                    ->maxSize(500 * 1024) // 500MB
                                                    // ->required() // Temporarily disabled to test form save
                                                    ->columnSpanFull()
                                                    ->helperText('الحد الأقصى: 500 ميجابايت. الصيغ المدعومة: MP4, WebM, MOV, AVI')
                                                    ->placeholder('اسحب الفيديو هنا أو انقر للاختيار')
                                                    ->getUploadedFileNameForStorageUsing(
                                                        fn (TemporaryUploadedFile $file): string => 'lesson_video_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension()
                                                    )
                                                    ->removeUploadedFileButtonPosition('right')
                                                    ->uploadProgressIndicatorPosition('left'),

                                                RichEditor::make('description')
                                                    ->label('وصف الدرس')
                                                    ->required()
                                                    ->columnSpanFull()
                                                    ->placeholder('أدخل وصف مفصل للدرس'),

                                                Textarea::make('description_en')
                                                    ->label('Lesson Description (English)')
                                                    ->rows(3)
                                                    ->columnSpanFull()
                                                    ->placeholder('Enter lesson description in English'),

                                                Grid::make(3)
                                                    ->schema([
                                                        TextInput::make('video_duration_seconds')
                                                            ->label('مدة الفيديو (بالثواني)')
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->placeholder('مدة الفيديو'),

                                                        TextInput::make('estimated_study_time_minutes')
                                                            ->label('وقت الدراسة المقدر (بالدقائق)')
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->placeholder('الوقت المقدر للدراسة'),

                                                        Select::make('difficulty_level')
                                                            ->label('مستوى الصعوبة')
                                                            ->options([
                                                                'easy' => 'سهل',
                                                                'medium' => 'متوسط',
                                                                'hard' => 'صعب',
                                                            ])
                                                            ->default('medium'),
                                                    ]),

                                                Select::make('lesson_type')
                                                    ->label('نوع الدرس')
                                                    ->options([
                                                        'video' => 'فيديو',
                                                        'quiz' => 'اختبار',
                                                        'assignment' => 'مهمة',
                                                        'reading' => 'قراءة',
                                                        'exercise' => 'تمرين',
                                                    ])
                                                    ->default('video')
                                                    ->required(),

                                                KeyValue::make('learning_objectives')
                                                    ->label('أهداف التعلم')
                                                    ->keyLabel('الهدف')
                                                    ->valueLabel('الوصف')
                                                    ->addActionLabel('إضافة هدف')
                                                    ->columnSpanFull(),

                                                FileUpload::make('attachments')
                                                    ->label('مرفقات الدرس')
                                                    ->multiple()
                                                    ->disk('public')
                                                    ->directory('lessons/attachments')
                                                    ->visibility('public')
                                                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/*'])
                                                    ->columnSpanFull()
                                                    ->storeFileNamesIn('attachments_names')
                                                    ->getUploadedFileNameForStorageUsing(
                                                        fn (TemporaryUploadedFile $file): string => 'lesson_attachment_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension()
                                                    )
                                                    ->moveFiles(),

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

                                                Textarea::make('notes')
                                                    ->label('ملاحظات الدرس')
                                                    ->rows(3)
                                                    ->columnSpanFull()
                                                    ->placeholder('ملاحظات إضافية للدرس'),
                                            ])
                                            ->minItems(1)
                                            ->defaultItems(1)
                                            ->maxItems(50)
                                            ->reorderableWithButtons()
                                            ->collapsible()
                                            ->cloneable()
                                            ->itemLabel(fn (array $state): ?string => ! empty($state['title']) ?
                                                    '📹 '.$state['title'].(isset($state['order']) ? ' (ترتيب: '.$state['order'].')' : '') :
                                                    'درس جديد'
                                            )
                                            ->columnSpanFull()
                                            ->grid(1),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = new (static::getModel())($data);
        $record->academy_id = Auth::user()->academy_id;
        $record->save();

        return $record;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set the created_by to the current user
        $data['created_by'] = Auth::user()->id;

        // Generate course code if not provided
        if (empty($data['course_code'])) {
            $data['course_code'] = $this->generateCourseCode();
        }

        // Store lessons data for later processing and remove from main data
        if (isset($data['lessons'])) {
            $this->lessonsData = $data['lessons'];
            unset($data['lessons']);
        }

        // Set default values for required fields

        $data['duration_hours'] = $data['duration_hours'] ?? 0;
        $data['language'] = $data['language'] ?? 'ar';
        $data['price'] = $data['price'] ?? 0;

        $data['is_published'] = $data['is_published'] ?? false;

        $data['difficulty_level'] = $data['difficulty_level'] ?? 'medium';

        // Set default values for description fields
        $data['description'] = $data['description'] ?? 'وصف الدورة';
        $data['description_en'] = $data['description_en'] ?? 'Course Description';

        return $data;
    }

    protected function afterCreate(): void
    {
        // Create lessons after course is created
        if (! empty($this->lessonsData)) {
            foreach ($this->lessonsData as $index => $lessonData) {
                $lessonData['recorded_course_id'] = $this->record->id;
                $lessonData['created_by'] = Auth::user()->id;

                // Generate lesson code if not provided
                if (empty($lessonData['lesson_code'])) {
                    $lessonData['lesson_code'] = $this->generateLessonCode($index + 1);
                }

                // Set published_at if lesson is published
                if ($lessonData['is_published'] ?? false) {
                    $lessonData['published_at'] = now();
                }

                $this->record->lessons()->create($lessonData);
            }

            // Update course statistics
            $this->record->updateStats();
        }
    }

    private $lessonsData = [];

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الدورة بنجاح';
    }

    private function generateCourseCode(): string
    {
        $academy = Auth::user()->academy;
        $prefix = strtoupper(substr($academy->name, 0, 3));
        $timestamp = now()->format('ymd');
        $random = strtoupper(Str::random(3));

        return "{$prefix}{$timestamp}{$random}";
    }

    private function generateLessonCode(int $lessonNumber): string
    {
        $courseCode = $this->record->course_code ?? 'COURSE';

        return "{$courseCode}_LESSON".str_pad($lessonNumber, 2, '0', STR_PAD_LEFT);
    }
}
