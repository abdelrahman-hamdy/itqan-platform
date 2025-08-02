<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecordedCourseResource\Pages;
use App\Models\RecordedCourse;
use App\Models\Academy;
use App\Models\AcademicTeacher;
use App\Models\AcademicSubject;
use App\Models\AcademicGradeLevel;
use App\Helpers\AcademyHelper;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecordedCourseResource extends BaseResource
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
                        Forms\Components\TextInput::make('title_ar')
                            ->label('عنوان الدورة (عربي)')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('title_en')
                            ->label('عنوان الدورة (إنجليزي)')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description_ar')
                            ->label('وصف الدورة (عربي)')
                            ->rows(3)
                            ->maxLength(1000),

                        Forms\Components\Textarea::make('description_en')
                            ->label('وصف الدورة (إنجليزي)')
                            ->rows(3)
                            ->maxLength(1000),

                        Forms\Components\Select::make('academy_id')
                            ->label('الأكاديمية')
                            ->options(Academy::pluck('name', 'id'))
                            ->default($currentAcademy?->id)
                            ->disabled($currentAcademy !== null)
                            ->required(),

                        Forms\Components\Select::make('instructor_id')
                            ->label('المدرس')
                            ->options(function () use ($currentAcademy) {
                                $query = AcademicTeacher::with('user');
                                if ($currentAcademy) {
                                    $query->whereHas('user', function($q) use ($currentAcademy) {
                                        $q->where('academy_id', $currentAcademy->id);
                                    });
                                }
                                return $query->get()->mapWithKeys(function($teacher) {
                                    $academyName = $teacher->user->academy->name ?? '';
                                    return [$teacher->id => $teacher->user->name . ' (' . $academyName . ')'];
                                });
                            })
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('subject_id')
                            ->label('المادة الدراسية')
                            ->options(AcademicSubject::pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('grade_level_id')
                            ->label('المستوى الدراسي')
                            ->options(AcademicGradeLevel::pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('تفاصيل الدورة')
                    ->schema([
                        Forms\Components\TextInput::make('duration')
                            ->label('مدة الدورة (بالساعات)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.5),

                        Forms\Components\TextInput::make('lessons_count')
                            ->label('عدد الدروس')
                            ->numeric()
                            ->minValue(0),

                        Forms\Components\TextInput::make('price')
                            ->label('السعر')
                            ->numeric()
                            ->prefix('SAR')
                            ->minValue(0),

                        Forms\Components\Select::make('difficulty_level')
                            ->label('مستوى الصعوبة')
                            ->options([
                                'beginner' => 'مبتدئ',
                                'intermediate' => 'متوسط',
                                'advanced' => 'متقدم',
                            ])
                            ->default('intermediate'),

                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'draft' => 'مسودة',
                                'published' => 'منشور',
                                'archived' => 'مؤرشف',
                            ])
                            ->default('draft'),
                    ])->columns(2),

                Forms\Components\Section::make('الوسائط')
                    ->schema([
                        Forms\Components\FileUpload::make('thumbnail')
                            ->label('صورة مصغرة')
                            ->image()
                            ->directory('course-thumbnails'),

                        Forms\Components\FileUpload::make('intro_video')
                            ->label('فيديو تعريفي')
                            ->acceptedFileTypes(['video/mp4', 'video/mov', 'video/avi', 'video/wmv', 'video/flv', 'video/webm'])
                            ->directory('course-videos')
                            ->maxSize(512000) // 512MB max size
                            ->helperText('الأنواع المدعومة: MP4, MOV, AVI, WMV, FLV, WebM'),

                        Forms\Components\FileUpload::make('materials')
                            ->label('الملفات المرفقة')
                            ->multiple()
                            ->directory('course-materials'),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('دروس الدورة')
                            ->icon('heroicon-o-play')
                            ->schema([
                                Forms\Components\Section::make('📚 إدارة دروس الدورة')
                                    ->description('يمكنك إضافة عدد لا محدود من الدروس وتحديد محتوى كل درس')
                                    ->schema([
                                        Forms\Components\Repeater::make('lessons')
                                            ->label('دروس الدورة')
                                            ->schema([
                                                Forms\Components\Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('title')
                                                            ->label('عنوان الدرس')
                                                            ->required()
                                                            ->maxLength(255),

                                                        Forms\Components\TextInput::make('order')
                                                            ->label('ترتيب الدرس')
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->required(),
                                                    ]),

                                                Forms\Components\RichEditor::make('description')
                                                    ->label('وصف الدرس')
                                                    ->required()
                                                    ->columnSpanFull(),

                                                Forms\Components\FileUpload::make('video_url')
                                                    ->label('🎥 فيديو الدرس')
                                                    ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/mov'])
                                                    ->maxSize(500 * 1024)
                                                    ->directory('lessons/videos')
                                                    ->required()
                                                    ->columnSpanFull(),

                                                Forms\Components\Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\Toggle::make('is_published')
                                                            ->label('منشور')
                                                            ->default(true),

                                                        Forms\Components\Toggle::make('is_free_preview')
                                                            ->label('معاينة مجانية')
                                                            ->default(false),
                                                    ]),
                                            ])
                                            ->defaultItems(1)
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => 
                                                !empty($state['title']) ? "📹 " . $state['title'] : "درس جديد"
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
                Tables\Columns\TextColumn::make('title_ar')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->visible(fn() => !AcademyHelper::hasAcademySelected())
                    ->sortable(),

                Tables\Columns\TextColumn::make('instructor.user.name')
                    ->label('المدرس')
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('المادة')
                    ->sortable(),

                Tables\Columns\TextColumn::make('grade_level.name')
                    ->label('المستوى')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('السعر')
                    ->money('SAR')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'published',
                        'danger' => 'archived',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'مسودة',
                        'published' => 'منشور',
                        'archived' => 'مؤرشف',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'published' => 'منشور',
                        'archived' => 'مؤرشف',
                    ]),

                Tables\Filters\SelectFilter::make('difficulty_level')
                    ->label('مستوى الصعوبة')
                    ->options([
                        'beginner' => 'مبتدئ',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
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