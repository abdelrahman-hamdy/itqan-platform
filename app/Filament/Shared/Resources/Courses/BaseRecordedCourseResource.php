<?php

namespace App\Filament\Shared\Resources\Courses;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\FileUpload;
use App\Enums\CertificateTemplateStyle;
use Filament\Tables\Enums\FiltersLayout;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\RecordedCourse;
use App\Services\AcademyContextService;
use Filament\Facades\Filament;
use Filament\Forms;
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

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-video-camera';

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة الدورات المسجلة';

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
    abstract protected static function getAcademyFormField(): ?Select;

    /**
     * Get instructor field for form (panel-specific).
     * Admin: May not have instructor | Academy: Required instructor field
     */
    abstract protected static function getInstructorFormField(): ?Select;

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
    protected static function getBasicInfoTab(): Tab
    {
        return Tab::make('المعلومات الأساسية')
            ->icon('heroicon-o-information-circle')
            ->schema([
                Section::make('معلومات الدورة')
                    ->schema([
                        TextInput::make('title')
                            ->label('عنوان الدورة')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('أدخل عنوان الدورة'),

                        TextInput::make('course_code')
                            ->label('رمز الدورة')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('رمز فريد للدورة (مثال: MATH101)')
                            ->placeholder('أدخل رمز الدورة'),

                        Textarea::make('description')
                            ->label('وصف الدورة')
                            ->rows(3)
                            ->maxLength(1000)
                            ->required()
                            ->placeholder('أدخل وصف مفصل للدورة'),

                        ...static::getPanelSpecificFormFields(),
                    ])->columns(2),

                Section::make('التصنيف الأكاديمي')
                    ->schema([
                        Select::make('subject_id')
                            ->label('المادة الدراسية')
                            ->options(fn () => static::getSubjectOptions())
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('grade_level_id')
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
                                TextInput::make('duration_hours')
                                    ->label('مدة الدورة (بالساعات)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.5)
                                    ->default(0)
                                    ->required(),

                                TextInput::make('price')
                                    ->label('السعر')
                                    ->numeric()
                                    ->prefix(getCurrencyCode())
                                    ->minValue(0)
                                    ->default(0)
                                    ->required(),

                                Select::make('difficulty_level')
                                    ->label('مستوى الدورة')
                                    ->options([
                                        'easy' => 'سهل',
                                        'medium' => 'متوسط',
                                        'hard' => 'صعب',
                                    ])
                                    ->default('medium')
                                    ->required(),

                                DateTimePicker::make('enrollment_deadline')
                                    ->label('آخر موعد للتسجيل')
                                    ->nullable()
                                    ->helperText('اتركه فارغاً للتسجيل المفتوح'),
                            ]),

                        Toggle::make('is_published')
                            ->label('منشور')
                            ->default(false)
                            ->required(),
                    ])->columns(2),

                Section::make('المتطلبات والنتائج')
                    ->schema([
                        TagsInput::make('prerequisites')
                            ->label('المتطلبات المسبقة')
                            ->placeholder('اضغط Enter لإضافة متطلب')
                            ->helperText('مثال: معرفة أساسيات البرمجة، إتقان اللغة الإنجليزية')
                            ->reorderable()
                            ->columnSpanFull(),

                        TagsInput::make('learning_outcomes')
                            ->label('نتائج التعلم')
                            ->placeholder('اضغط Enter لإضافة نتيجة')
                            ->helperText('مثال: بناء تطبيقات ويب كاملة، فهم قواعد البيانات')
                            ->reorderable()
                            ->columnSpanFull(),

                        TagsInput::make('tags')
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
    protected static function getLessonsTab(): Tab
    {
        return Tab::make('دروس الدورة')
            ->icon('heroicon-o-play')
            ->schema([
                Section::make('📚 إدارة دروس الدورة')
                    ->description('يمكنك إضافة عدد لا محدود من الدروس وتحديد محتوى كل درس')
                    ->schema([
                        Repeater::make('lessons')
                            ->relationship('lessons')
                            ->label('دروس الدورة')
                            ->schema([
                                Hidden::make('course_section_id')
                                    ->default(1),

                                Hidden::make('created_by')
                                    ->default(auth()->id()),

                                TextInput::make('title')
                                    ->label('عنوان الدرس')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                RichEditor::make('description')
                                    ->label('وصف الدرس')
                                    ->required()
                                    ->columnSpanFull(),

                                FileUpload::make('video_url')
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
                                        Toggle::make('is_published')
                                            ->label('منشور')
                                            ->default(true),

                                        Toggle::make('is_free_preview')
                                            ->label('معاينة مجانية')
                                            ->default(false),

                                        Toggle::make('is_downloadable')
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
    protected static function getPrerequisitesTab(): Tab
    {
        return Tab::make('المتطلبات والنتائج')
            ->icon('heroicon-o-clipboard-document-list')
            ->schema([
                Section::make('متطلبات الدورة')
                    ->description('حدد المتطلبات الأساسية للالتحاق بهذه الدورة')
                    ->schema([
                        TagsInput::make('prerequisites')
                            ->label('المتطلبات المسبقة')
                            ->placeholder('اضغط Enter لإضافة متطلب')
                            ->helperText('مثال: معرفة أساسيات البرمجة، إتقان اللغة الإنجليزية')
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),

                Section::make('نتائج التعلم')
                    ->description('ماذا سيتعلم الطالب من هذه الدورة؟')
                    ->schema([
                        TagsInput::make('learning_outcomes')
                            ->label('نتائج التعلم')
                            ->placeholder('اضغط Enter لإضافة نتيجة')
                            ->helperText('مثال: بناء تطبيقات ويب كاملة، فهم قواعد البيانات')
                            ->reorderable()
                            ->columnSpanFull(),

                        TagsInput::make('tags')
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
    protected static function getCertificateTab(): Tab
    {
        return Tab::make('الشهادة')
            ->icon('heroicon-o-academic-cap')
            ->schema([
                Section::make('إعدادات الشهادة')
                    ->description('تخصيص شهادة إتمام الدورة')
                    ->schema([
                        Select::make('certificate_template_style')
                            ->label('تصميم الشهادة')
                            ->options(CertificateTemplateStyle::options())
                            ->helperText('اختر تصميم الشهادة التي ستُمنح للطلاب عند إتمام الدورة'),

                        Textarea::make('certificate_template_text')
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
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->recordActions(static::getTableActions())
            ->toolbarActions(static::getTableBulkActions());
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
                ->placeholder(__('filament.all'))
                ->trueLabel(__('filament.tabs.published'))
                ->falseLabel(__('filament.tabs.draft')),

            TernaryFilter::make('is_free')
                ->label(__('filament.course.is_free'))
                ->placeholder(__('filament.all'))
                ->trueLabel(__('filament.tabs.free'))
                ->falseLabel(__('filament.tabs.paid'))
                ->queries(
                    true: fn (Builder $query) => $query->where('price', 0),
                    false: fn (Builder $query) => $query->where('price', '>', 0),
                ),

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
        ];
    }

    // ========================================
    // Academy Context Methods
    // ========================================

    protected static function isViewingAllAcademies(): bool
    {
        if (Filament::getTenant() !== null) {
            return false;
        }

        $academyContextService = app(AcademyContextService::class);

        return $academyContextService->getCurrentAcademyId() === null;
    }

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }

    protected static function getAcademyColumn(): TextColumn
    {
        $academyPath = static::getAcademyRelationshipPath();

        return TextColumn::make($academyPath.'.name')
            ->label('الأكاديمية')
            ->sortable()
            ->searchable()
            ->visible(static::isViewingAllAcademies())
            ->placeholder('غير محدد');
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
