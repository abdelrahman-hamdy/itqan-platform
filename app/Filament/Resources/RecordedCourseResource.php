<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecordedCourseResource\Pages;
use App\Helpers\AcademyHelper;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\Academy;
use App\Models\RecordedCourse;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class RecordedCourseResource extends Resource
{
    protected static ?string $model = RecordedCourse::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationGroup = 'إدارة الدورات المسجلة';

    protected static ?string $navigationLabel = 'الدورات المسجلة';

    protected static ?string $modelLabel = 'دورة مسجلة';

    protected static ?string $pluralModelLabel = 'الدورات المسجلة';

    public static function form(Form $form): Form
    {
        $currentAcademy = AcademyHelper::getCurrentAcademy();

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
                                            ->label('عنوان الدورة (عربي)')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('أدخل عنوان الدورة باللغة العربية')
                                            ->helperText('مطلوب - عنوان الدورة باللغة العربية'),

                                        Forms\Components\TextInput::make('title_en')
                                            ->label('عنوان الدورة (إنجليزي)')
                                            ->maxLength(255)
                                            ->placeholder('Enter course title in English')
                                            ->helperText('اختياري - عنوان الدورة باللغة الإنجليزية'),

                                        Forms\Components\TextInput::make('course_code')
                                            ->label('رمز الدورة')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->helperText('رمز فريد للدورة (مثال: MATH101)')
                                            ->placeholder('أدخل رمز الدورة'),

                                        Forms\Components\Textarea::make('description')
                                            ->label('وصف الدورة (عربي)')
                                            ->rows(3)
                                            ->maxLength(1000)
                                            ->required()
                                            ->placeholder('أدخل وصف مفصل للدورة باللغة العربية')
                                            ->helperText('مطلوب - يجب إدخال وصف للدورة')
                                            ->default('وصف الدورة'),

                                        Forms\Components\Textarea::make('description_en')
                                            ->label('وصف الدورة (إنجليزي)')
                                            ->rows(3)
                                            ->maxLength(1000)
                                            ->placeholder('Enter course description in English')
                                            ->helperText('اختياري - يمكن تركه فارغاً')
                                            ->default('Course Description'),

                                        Forms\Components\Select::make('academy_id')
                                            ->label('الأكاديمية')
                                            ->options(Academy::pluck('name', 'id'))
                                            ->default($currentAcademy?->id)
                                            ->disabled($currentAcademy !== null)
                                            ->required(),

                                        Forms\Components\Select::make('subject_id')
                                            ->label('المادة الدراسية')
                                            ->options(function () use ($currentAcademy) {
                                                $query = AcademicSubject::query();
                                                if ($currentAcademy) {
                                                    $query->where('academy_id', $currentAcademy->id);
                                                }

                                                return $query->pluck('name', 'id');
                                            })
                                            ->searchable()
                                            ->required(),

                                        Forms\Components\Select::make('grade_level_id')
                                            ->label('الصف الدراسي')
                                            ->options(function () use ($currentAcademy) {
                                                $query = AcademicGradeLevel::query();
                                                if ($currentAcademy) {
                                                    $query->where('academy_id', $currentAcademy->id);
                                                }

                                                return $query->where('is_active', true)
                                                    ->whereNotNull('name')
                                                    ->where('name', '!=', '')
                                                    ->orderBy('name')
                                                    ->pluck('name', 'id');
                                            })
                                            ->searchable()
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
                                                    ->prefix('SAR')
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

                                                Forms\Components\Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('title')
                                                            ->label('عنوان الدرس')
                                                            ->required()
                                                            ->maxLength(255),

                                                        Forms\Components\TextInput::make('title_en')
                                                            ->label('Lesson Title (English)')
                                                            ->maxLength(255),
                                                    ]),

                                                Forms\Components\RichEditor::make('description')
                                                    ->label('وصف الدرس')
                                                    ->required()
                                                    ->columnSpanFull(),

                                                Forms\Components\Textarea::make('description_en')
                                                    ->label('Lesson Description (English)')
                                                    ->rows(3)
                                                    ->columnSpanFull(),

                                                Forms\Components\FileUpload::make('video_url')
                                                    ->label('فيديو الدرس')
                                                    ->disk('public')
                                                    ->directory('lessons/videos')
                                                    ->visibility('public')
                                                    ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/mov', 'video/avi'])
                                                    ->maxSize(512 * 1024) // 512MB
                                                    ->columnSpanFull()
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
                    ->visible(fn () => ! AcademyHelper::hasAcademySelected())
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('المادة')
                    ->sortable(),

                Tables\Columns\TextColumn::make('grade_level.name')
                    ->label('الصف الدراسي')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('السعر')
                    ->money('SAR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('difficulty_level')
                    ->label('مستوى الصعوبة')
                    ->options([
                        'easy' => 'سهل',
                        'medium' => 'متوسط',
                        'hard' => 'صعب',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Filter by current academy if selected
        if (AcademyHelper::hasAcademySelected()) {
            $query->where('academy_id', AcademyHelper::getCurrentAcademyId());
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
