<?php

namespace App\Filament\Resources;

use App\Enums\CertificateTemplateStyle;
use App\Enums\DifficultyLevel;
use App\Filament\Resources\RecordedCourseResource\Pages;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\Academy;
use App\Models\RecordedCourse;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class RecordedCourseResource extends BaseResource
{
    protected static ?string $model = RecordedCourse::class;

    /**
     * Tenant ownership relationship for Filament multi-tenancy.
     */
    protected static ?string $tenantOwnershipRelationshipName = 'academy';

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationGroup = 'إدارة الدورات المسجلة';

    protected static ?string $navigationLabel = 'الدورات المسجلة';

    protected static ?string $modelLabel = 'دورة مسجلة';

    protected static ?string $pluralModelLabel = 'الدورات المسجلة';

    public static function form(Form $form): Form
    {
        $currentAcademy = AcademyContextService::getCurrentAcademy();

        return $form
            ->schema([
                Forms\Components\Tabs::make('admin-course-tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('المعلومات الأساسية')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Section::make('معلومات الدورة')
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

                                        Forms\Components\Select::make('academy_id')
                                            ->label('الأكاديمية')
                                            ->options(Academy::pluck('name', 'id'))
                                            ->default($currentAcademy?->id)
                                            ->disabled($currentAcademy !== null)
                                            ->required()
                                            ->live(),

                                        Forms\Components\Select::make('subject_id')
                                            ->label('المادة الدراسية')
                                            ->options(function () {
                                                $academyId = AcademyContextService::getCurrentAcademyId();

                                                return $academyId ? AcademicSubject::where('academy_id', $academyId)->where('is_active', true)->pluck('name', 'id') : [];
                                            })
                                            ->required()
                                            ->searchable()
                                            ->preload(),

                                        Forms\Components\Select::make('grade_level_id')
                                            ->label('الصف الدراسي')
                                            ->options(function (Get $get) use ($currentAcademy) {
                                                $academyId = $get('academy_id') ?? $currentAcademy?->id;

                                                if (! $academyId) {
                                                    return [];
                                                }

                                                return AcademicGradeLevel::where('academy_id', $academyId)
                                                    ->where('is_active', true)
                                                    ->whereNotNull('name')
                                                    ->where('name', '!=', '')
                                                    ->orderBy('name')
                                                    ->pluck('name', 'id');
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                    ])->columns(2),

                                Forms\Components\Section::make('تفاصيل الدورة')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
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

                                Forms\Components\Section::make('الوسائط')
                                    ->schema([
                                        SpatieMediaLibraryFileUpload::make('thumbnail_url')
                                            ->label('صورة مصغرة')
                                            ->image()
                                            ->collection('thumbnails')
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                            ->maxSize(10240) // 10MB max size
                                            ->helperText('أقصى حجم: 10 ميجابايت')
                                            ->nullable(),

                                        SpatieMediaLibraryFileUpload::make('materials')
                                            ->label('مواد الكورس')
                                            ->multiple()
                                            ->collection('materials')
                                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'])
                                            ->maxSize(51200) // 50MB max size
                                            ->helperText('أقصى حجم: 50 ميجابايت لكل ملف'),
                                    ])->columns(2),

                                Forms\Components\Section::make('ملاحظات')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Textarea::make('admin_notes')
                                                    ->label('ملاحظات الإدارة')
                                                    ->rows(3)
                                                    ->maxLength(1000)
                                                    ->helperText('ملاحظات داخلية للإدارة'),

                                                Forms\Components\Textarea::make('supervisor_notes')
                                                    ->label('ملاحظات المشرف')
                                                    ->rows(3)
                                                    ->maxLength(2000)
                                                    ->helperText('ملاحظات مرئية للمشرف والإدارة فقط'),
                                            ]),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('دروس الدورة')
                            ->icon('heroicon-o-play')
                            ->schema([
                                Forms\Components\Section::make('📚 إدارة دروس الدورة')
                                    ->description('يمكنك إضافة عدد لا محدود من الدروس وتحديد محتوى كل درس')
                                    ->schema([
                                        Forms\Components\Repeater::make('lessons')
                                            ->relationship('lessons')
                                            ->label('دروس الدورة')
                                            ->schema([
                                                Forms\Components\Hidden::make('course_section_id')
                                                    ->default(1), // Will be updated after course creation

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

                                                Forms\Components\Grid::make(3)
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
                                            ->itemLabel(fn (array $state): ?string => ! empty($state['title']) ? '📹 '.$state['title'] : 'درس جديد'
                                            )
                                            ->addActionLabel('➕ إضافة درس جديد')
                                            ->reorderableWithButtons(),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('المتطلبات والنتائج')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Forms\Components\Section::make('متطلبات الدورة')
                                    ->description('حدد المتطلبات الأساسية للالتحاق بهذه الدورة')
                                    ->schema([
                                        Forms\Components\TagsInput::make('prerequisites')
                                            ->label('المتطلبات المسبقة')
                                            ->placeholder('اضغط Enter لإضافة متطلب')
                                            ->helperText('مثال: معرفة أساسيات البرمجة، إتقان اللغة الإنجليزية')
                                            ->reorderable()
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('نتائج التعلم')
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
                            ]),

                        Forms\Components\Tabs\Tab::make('الشهادة')
                            ->icon('heroicon-o-academic-cap')
                            ->schema([
                                Forms\Components\Section::make('إعدادات الشهادة')
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
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->visible(fn () => ! AcademyContextService::hasAcademySelected())
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('المادة')
                    ->sortable(),

                Tables\Columns\TextColumn::make('gradeLevel.name')
                    ->label('الصف الدراسي')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('السعر')
                    ->money(fn ($record) => $record->academy?->currency?->value ?? 'SAR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label(__('filament.is_published'))
                    ->trueLabel(__('filament.tabs.published'))
                    ->falseLabel(__('filament.tabs.draft'))
                    ->placeholder(__('filament.all')),

                Tables\Filters\SelectFilter::make('difficulty_level')
                    ->label(__('filament.difficulty_level'))
                    ->options(DifficultyLevel::options()),

                Tables\Filters\SelectFilter::make('subject_id')
                    ->label(__('filament.course.subject'))
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('grade_level_id')
                    ->label(__('filament.grade_level'))
                    ->relationship('gradeLevel', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_free')
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
                Tables\Filters\TrashedFilter::make()
                    ->label(__('filament.filters.trashed')),
            ])
            ->actions([
                Tables\Actions\ReplicateAction::make()
                    ->label('نسخ الدورة')
                    ->form([
                        Forms\Components\Toggle::make('copy_sections')
                            ->label('نسخ الأقسام والدروس')
                            ->default(true)
                            ->helperText('نسخ جميع الأقسام والدروس مع الدورة'),
                    ])
                    ->beforeReplicaSaved(function (RecordedCourse $replica): void {
                        $replica->title = $replica->title.' (نسخة)';
                        $replica->is_published = false;
                        $replica->slug = $replica->slug.'-copy-'.time();
                    })
                    ->afterReplicaSaved(function (RecordedCourse $original, RecordedCourse $replica, array $data): void {
                        if ($data['copy_sections'] ?? true) {
                            foreach ($original->sections as $section) {
                                $newSection = $section->replicate(['recorded_course_id']);
                                $newSection->recorded_course_id = $replica->id;
                                $newSection->save();

                                foreach ($section->lessons as $lesson) {
                                    $newLesson = $lesson->replicate(['course_section_id']);
                                    $newLesson->course_section_id = $newSection->id;
                                    $newLesson->save();
                                }
                            }
                        }
                    })
                    ->successNotificationTitle('تم نسخ الدورة بنجاح'),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->label(__('filament.actions.restore')),
                Tables\Actions\ForceDeleteAction::make()
                    ->label(__('filament.actions.force_delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label(__('filament.actions.restore_selected')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label(__('filament.actions.force_delete_selected')),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        // Filter by current academy if selected
        if (AcademyContextService::hasAcademySelected()) {
            $query->where('academy_id', AcademyContextService::getCurrentAcademyId());
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            //
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
