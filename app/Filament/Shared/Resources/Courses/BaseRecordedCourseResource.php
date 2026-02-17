<?php

namespace App\Filament\Shared\Resources\Courses;

use App\Enums\CertificateTemplateStyle;
use App\Enums\DifficultyLevel;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\RecordedCourse;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Base Recorded Course Resource
 *
 * Shared functionality for Admin and Academy panels.
 * Child classes must implement query scoping and authorization methods.
 */
abstract class BaseRecordedCourseResource extends Resource
{
    protected static ?string $model = RecordedCourse::class;

    protected static ?string $tenantOwnershipRelationshipName = 'academy';

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationGroup = 'إدارة الدورات المسجلة';

    protected static ?string $navigationLabel = 'الدورات المسجلة';

    protected static ?string $modelLabel = 'دورة مسجلة';

    protected static ?string $pluralModelLabel = 'الدورات المسجلة';

    // ========================================
    // Abstract Methods - Panel-specific implementation
    // ========================================

    /**
     * Apply panel-specific query scoping.
     * Admin: No scoping (all academies) | Academy: Scope to current academy
     */
    abstract protected static function scopeEloquentQuery(Builder $query): Builder;

    /**
     * Get panel-specific table actions.
     * Admin: View, Edit, Replicate, Restore, ForceDelete | Academy: View, Edit, Publish/Unpublish
     */
    abstract protected static function getTableActions(): array;

    /**
     * Get panel-specific bulk actions.
     * Admin: Delete, Restore, ForceDelete | Academy: Delete, Publish/Unpublish
     */
    abstract protected static function getTableBulkActions(): array;

    /**
     * Get academy field for form (Admin only).
     * Admin: Academy selector | Academy: null (auto-scoped)
     */
    abstract protected static function getAcademyFormField(): ?Forms\Components\Select;

    /**
     * Get instructor field for form (panel-specific).
     * Admin: May not have instructor | Academy: Required instructor field
     */
    abstract protected static function getInstructorFormField(): ?Forms\Components\Select;

    /**
     * Get panel-specific form fields (admin notes, instructor, etc.).
     */
    abstract protected static function getPanelSpecificFormFields(): array;

    /**
     * Get grade level options (panel-specific scoping).
     */
    abstract protected static function getGradeLevelOptions(Get $get): array;

    /**
     * Get subject options (panel-specific scoping).
     */
    abstract protected static function getSubjectOptions(): array;

    // ========================================
    // Authorization - Override in child classes if needed
    // ========================================

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        return true;
    }

    // ========================================
    // Shared Form Implementation
    // ========================================

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('course-tabs')
                    ->tabs([
                        static::getBasicInfoTab(),
                        static::getLessonsTab(),
                        static::getPrerequisitesTab(),
                        static::getCertificateTab(),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }

    /**
     * Basic Information Tab
     */
    protected static function getBasicInfoTab(): Tabs\Tab
    {
        return Tabs\Tab::make('المعلومات الأساسية')
            ->icon('heroicon-o-information-circle')
            ->schema([
                Section::make('معلومات الدورة')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الدورة')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('أدخل عنوان الدورة'),

                        Forms\Components\TextInput::make('course_code')
                            ->label('رمز الدورة')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('رمز فريد للدورة (مثال: MATH101)')
                            ->placeholder('أدخل رمز الدورة'),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف الدورة')
                            ->rows(3)
                            ->maxLength(1000)
                            ->required()
                            ->placeholder('أدخل وصف مفصل للدورة'),

                        ...static::getPanelSpecificFormFields(),
                    ])->columns(2),

                Section::make('التصنيف الأكاديمي')
                    ->schema([
                        Forms\Components\Select::make('subject_id')
                            ->label('المادة الدراسية')
                            ->options(fn () => static::getSubjectOptions())
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('grade_level_id')
                            ->label('الصف الدراسي')
                            ->options(fn (Get $get) => static::getGradeLevelOptions($get))
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(2),

                Section::make('تفاصيل الدورة')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('duration_hours')
                                    ->label('مدة الدورة (بالساعات)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.5)
                                    ->default(0)
                                    ->required(),

                                Forms\Components\TextInput::make('price')
                                    ->label('السعر')
                                    ->numeric()
                                    ->prefix(getCurrencyCode())
                                    ->minValue(0)
                                    ->default(0)
                                    ->required(),

                                Forms\Components\Select::make('difficulty_level')
                                    ->label('مستوى الدورة')
                                    ->options([
                                        'easy' => 'سهل',
                                        'medium' => 'متوسط',
                                        'hard' => 'صعب',
                                    ])
                                    ->default('medium')
                                    ->required(),

                                Forms\Components\DateTimePicker::make('enrollment_deadline')
                                    ->label('آخر موعد للتسجيل')
                                    ->nullable()
                                    ->helperText('اتركه فارغاً للتسجيل المفتوح'),
                            ]),

                        Forms\Components\Toggle::make('is_published')
                            ->label('منشور')
                            ->default(false)
                            ->required(),
                    ])->columns(2),

                Section::make('المتطلبات والنتائج')
                    ->schema([
                        Forms\Components\TagsInput::make('prerequisites')
                            ->label('المتطلبات المسبقة')
                            ->placeholder('اضغط Enter لإضافة متطلب')
                            ->helperText('مثال: معرفة أساسيات البرمجة، إتقان اللغة الإنجليزية')
                            ->reorderable()
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('learning_outcomes')
                            ->label('نتائج التعلم')
                            ->placeholder('اضغط Enter لإضافة نتيجة')
                            ->helperText('مثال: بناء تطبيقات ويب كاملة، فهم قواعد البيانات')
                            ->reorderable()
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('tags')
                            ->label('الكلمات المفتاحية')
                            ->placeholder('اضغط Enter لإضافة كلمة')
                            ->helperText('تساعد في البحث والتصنيف')
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Lessons Tab
     */
    protected static function getLessonsTab(): Tabs\Tab
    {
        return Tabs\Tab::make('دروس الدورة')
            ->icon('heroicon-o-play')
            ->schema([
                Section::make('📚 إدارة دروس الدورة')
                    ->description('يمكنك إضافة عدد لا محدود من الدروس وتحديد محتوى كل درس')
                    ->schema([
                        Forms\Components\Repeater::make('lessons')
                            ->relationship('lessons')
                            ->label('دروس الدورة')
                            ->schema([
                                Forms\Components\Hidden::make('course_section_id')
                                    ->default(1),

                                Forms\Components\Hidden::make('created_by')
                                    ->default(auth()->id()),

                                Forms\Components\TextInput::make('title')
                                    ->label('عنوان الدرس')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\RichEditor::make('description')
                                    ->label('وصف الدرس')
                                    ->required()
                                    ->columnSpanFull(),

                                Forms\Components\FileUpload::make('video_url')
                                    ->label('فيديو الدرس')
                                    ->disk('public')
                                    ->directory('lessons/videos')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/mov', 'video/avi'])
                                    ->maxSize(512 * 1024) // 512MB
                                    ->columnSpanFull()
                                    ->removeUploadedFileButtonPosition('right')
                                    ->uploadProgressIndicatorPosition('left')
                                    ->getUploadedFileNameForStorageUsing(
                                        fn (TemporaryUploadedFile $file): string => 'lesson_video_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension()
                                    ),

                                Grid::make(3)
                                    ->schema([
                                        Forms\Components\Toggle::make('is_published')
                                            ->label('منشور')
                                            ->default(true),

                                        Forms\Components\Toggle::make('is_free_preview')
                                            ->label('معاينة مجانية')
                                            ->default(false),

                                        Forms\Components\Toggle::make('is_downloadable')
                                            ->label('قابل للتحميل')
                                            ->default(false),
                                    ]),
                            ])
                            ->defaultItems(1)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => ! empty($state['title']) ? '📹 '.$state['title'] : 'درس جديد')
                            ->addActionLabel('➕ إضافة درس جديد')
                            ->reorderableWithButtons(),
                    ]),
            ]);
    }

    /**
     * Prerequisites Tab
     */
    protected static function getPrerequisitesTab(): Tabs\Tab
    {
        return Tabs\Tab::make('المتطلبات والنتائج')
            ->icon('heroicon-o-clipboard-document-list')
            ->schema([
                Section::make('متطلبات الدورة')
                    ->description('حدد المتطلبات الأساسية للالتحاق بهذه الدورة')
                    ->schema([
                        Forms\Components\TagsInput::make('prerequisites')
                            ->label('المتطلبات المسبقة')
                            ->placeholder('اضغط Enter لإضافة متطلب')
                            ->helperText('مثال: معرفة أساسيات البرمجة، إتقان اللغة الإنجليزية')
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),

                Section::make('نتائج التعلم')
                    ->description('ماذا سيتعلم الطالب من هذه الدورة؟')
                    ->schema([
                        Forms\Components\TagsInput::make('learning_outcomes')
                            ->label('نتائج التعلم')
                            ->placeholder('اضغط Enter لإضافة نتيجة')
                            ->helperText('مثال: بناء تطبيقات ويب كاملة، فهم قواعد البيانات')
                            ->reorderable()
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('tags')
                            ->label('الكلمات المفتاحية')
                            ->placeholder('اضغط Enter لإضافة كلمة')
                            ->helperText('تساعد في البحث والتصنيف')
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Certificate Tab
     */
    protected static function getCertificateTab(): Tabs\Tab
    {
        return Tabs\Tab::make('الشهادة')
            ->icon('heroicon-o-academic-cap')
            ->schema([
                Section::make('إعدادات الشهادة')
                    ->description('تخصيص شهادة إتمام الدورة')
                    ->schema([
                        Forms\Components\Select::make('certificate_template_style')
                            ->label('تصميم الشهادة')
                            ->options(CertificateTemplateStyle::options())
                            ->helperText('اختر تصميم الشهادة التي ستُمنح للطلاب عند إتمام الدورة'),

                        Forms\Components\Textarea::make('certificate_template_text')
                            ->label('نص الشهادة المخصص')
                            ->rows(4)
                            ->placeholder('يُشهد بأن الطالب/ة قد أتم/ت بنجاح دورة...')
                            ->helperText('اتركه فارغاً لاستخدام النص الافتراضي')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    // ========================================
    // Shared Table Implementation
    // ========================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters())
            ->actions(static::getTableActions())
            ->bulkActions(static::getTableBulkActions());
    }

    /**
     * Get shared table columns
     */
    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('course_code')
                ->label('رمز الدورة')
                ->searchable()
                ->sortable(),

            TextColumn::make('title')
                ->label('العنوان')
                ->searchable()
                ->sortable()
                ->limit(50),

            TextColumn::make('subject.name')
                ->label('المادة')
                ->sortable(),

            TextColumn::make('gradeLevel.name')
                ->label('الصف الدراسي')
                ->sortable(),

            TextColumn::make('price')
                ->label('السعر')
                ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR'))
                ->sortable(),

            TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime()
                ->sortable(),
        ];
    }

    /**
     * Get shared table filters
     */
    protected static function getTableFilters(): array
    {
        return [
            TernaryFilter::make('is_published')
                ->label(__('filament.is_published'))
                ->trueLabel(__('filament.tabs.published'))
                ->falseLabel(__('filament.tabs.draft'))
                ->placeholder(__('filament.all')),

            SelectFilter::make('difficulty_level')
                ->label(__('filament.difficulty_level'))
                ->options(DifficultyLevel::options()),

            SelectFilter::make('subject_id')
                ->label(__('filament.course.subject'))
                ->relationship('subject', 'name')
                ->searchable()
                ->preload(),

            SelectFilter::make('grade_level_id')
                ->label(__('filament.grade_level'))
                ->relationship('gradeLevel', 'name')
                ->searchable()
                ->preload(),

            TernaryFilter::make('is_free')
                ->label(__('filament.course.is_free'))
                ->trueLabel(__('filament.tabs.free'))
                ->falseLabel(__('filament.tabs.paid'))
                ->placeholder(__('filament.all'))
                ->queries(
                    true: fn (Builder $query) => $query->where('price', 0),
                    false: fn (Builder $query) => $query->where('price', '>', 0),
                ),

            Tables\Filters\Filter::make('created_at')
                ->form([
                    Forms\Components\DatePicker::make('from')
                        ->label(__('filament.filters.from_date')),
                    Forms\Components\DatePicker::make('until')
                        ->label(__('filament.filters.to_date')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];
                    if ($data['from'] ?? null) {
                        $indicators['from'] = __('filament.filters.from_date').': '.$data['from'];
                    }
                    if ($data['until'] ?? null) {
                        $indicators['until'] = __('filament.filters.to_date').': '.$data['until'];
                    }

                    return $indicators;
                }),
        ];
    }

    // ========================================
    // Query Scoping
    // ========================================

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return static::scopeEloquentQuery($query);
    }
}
