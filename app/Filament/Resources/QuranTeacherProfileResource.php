<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuranTeacherProfileResource\Pages;
use App\Models\QuranTeacherProfile;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Services\AcademyContextService;

class QuranTeacherProfileResource extends BaseResource
{

    protected static ?string $model = QuranTeacherProfile::class;
    
    protected static ?string $tenantOwnershipRelationshipName = 'user';

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'معلمو القرآن';

    protected static ?string $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'معلم قرآن';

    protected static ?string $pluralModelLabel = 'معلمو القرآن';

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy'; // QuranTeacherProfile -> Academy (direct relationship)
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الشخصية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('first_name')
                                    ->label('الاسم الأول')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('last_name')
                                    ->label('الاسم الأخير')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->helperText('سيستخدم المعلم هذا البريد للدخول إلى المنصة'),
                                Forms\Components\TextInput::make('phone')
                                    ->label('رقم الهاتف')
                                    ->tel()
                                    ->maxLength(20),
                            ]),
                        Forms\Components\FileUpload::make('avatar')
                            ->label('الصورة الشخصية')
                            ->image()
                            ->imageEditor()
                            ->circleCropper()
                            ->directory('avatars/quran-teachers')
                            ->maxSize(2048),
                    ]),

                Forms\Components\Section::make('المؤهلات والخبرة')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('educational_qualification')
                                    ->label('المؤهل التعليمي')
                                    ->options([
                                        'bachelor' => 'بكالوريوس',
                                        'master' => 'ماجستير',
                                        'phd' => 'دكتوراه',
                                        'ijazah' => 'إجازة في القرآن',
                                        'diploma' => 'دبلوم',
                                        'other' => 'أخرى',
                                    ])
                                    ->default('bachelor')
                                    ->required(),
                                Forms\Components\TextInput::make('teaching_experience_years')
                                    ->label('سنوات الخبرة في تدريس القرآن')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(50)
                                    ->default(0),
                            ]),
                        Forms\Components\TagsInput::make('certifications')
                            ->label('الشهادات والإجازات')
                            ->placeholder('أضف شهادة أو إجازة')
                            ->helperText('مثل: إجازة في القراءات، شهادة تجويد، إلخ'),
                        Forms\Components\CheckboxList::make('languages')
                            ->label('اللغات التي يجيدها')
                            ->options([
                                'arabic' => 'العربية',
                                'english' => 'الإنجليزية',
                                'french' => 'الفرنسية',
                                'urdu' => 'الأردو',
                                'turkish' => 'التركية',
                                'malay' => 'الماليزية',
                            ])
                            ->default(['arabic'])
                            ->columns(2),
                    ]),

                Forms\Components\Section::make('الأوقات المتاحة')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('available_time_start')
                                    ->label('وقت البدء')
                                    ->default('08:00')
                                    ->required(),
                                Forms\Components\TimePicker::make('available_time_end')
                                    ->label('وقت الانتهاء')
                                    ->default('18:00')
                                    ->required(),
                            ]),
                        Forms\Components\CheckboxList::make('available_days')
                            ->label('الأيام المتاحة')
                            ->options([
                                'sunday' => 'الأحد',
                                'monday' => 'الاثنين',
                                'tuesday' => 'الثلاثاء',
                                'wednesday' => 'الأربعاء',
                                'thursday' => 'الخميس',
                                'friday' => 'الجمعة',
                                'saturday' => 'السبت',
                            ])
                            ->columns(2)
                            ->required(),
                    ]),

                Forms\Components\Section::make('السيرة الذاتية')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('bio_arabic')
                                    ->label('السيرة الذاتية (عربي)')
                                    ->maxLength(1000)
                                    ->rows(4)
                                    ->helperText('اكتب نبذة عن خبرتك في تدريس القرآن الكريم'),
                                Forms\Components\Textarea::make('bio_english')
                                    ->label('السيرة الذاتية (إنجليزي)')
                                    ->maxLength(1000)
                                    ->rows(4),
                            ]),
                    ]),

                Forms\Components\Section::make('الحالة والموافقة')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('approval_status')
                                    ->label('حالة الموافقة')
                                    ->options([
                                        'pending' => 'في الانتظار',
                                        'approved' => 'معتمد',
                                        'rejected' => 'مرفوض',
                                    ])
                                    ->default('pending')
                                    ->required(),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('نشط')
                                    ->default(true),
                            ]),
                    ])
                    ->visible(fn () => auth()->user()->isAdmin()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getAcademyColumn(), // Add academy column when viewing all academies
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('الصورة')
                    ->circular(),
                Tables\Columns\TextColumn::make('teacher_code')
                    ->label('رمز المعلم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('الاسم')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\IconColumn::make('user_id')
                    ->label('مربوط بحساب')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\BadgeColumn::make('approval_status')
                    ->label('حالة الموافقة')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'pending' => 'في الانتظار',
                            'approved' => 'معتمد',
                            'rejected' => 'مرفوض',
                            default => $state,
                        };
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('educational_qualification')
                    ->label('المؤهل التعليمي')
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'bachelor' => 'بكالوريوس',
                            'master' => 'ماجستير',
                            'phd' => 'دكتوراه',
                            'ijazah' => 'إجازة في القرآن',
                            'diploma' => 'دبلوم',
                            'other' => 'أخرى',
                            default => $state,
                        };
                    }),
                Tables\Columns\TextColumn::make('teaching_experience_years')
                    ->label('سنوات الخبرة')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating')
                    ->label('التقييم')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . ' ⭐' : 'غير مقيم')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('approval_status')
                    ->label('حالة الموافقة')
                    ->options([
                        'pending' => 'في الانتظار',
                        'approved' => 'معتمد',
                        'rejected' => 'مرفوض',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('نشط'),
                Tables\Filters\TernaryFilter::make('user_id')
                    ->label('مربوط بحساب')
                    ->nullable()
                    ->trueLabel('مربوط')
                    ->falseLabel('غير مربوط'),
                Tables\Filters\SelectFilter::make('educational_qualification')
                    ->label('المؤهل التعليمي')
                    ->options([
                        'bachelor' => 'بكالوريوس',
                        'master' => 'ماجستير',
                        'phd' => 'دكتوراه',
                        'ijazah' => 'إجازة في القرآن',
                        'diploma' => 'دبلوم',
                        'other' => 'أخرى',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranTeacherProfiles::route('/'),
            'create' => Pages\CreateQuranTeacherProfile::route('/create'),
            'edit' => Pages\EditQuranTeacherProfile::route('/{record}/edit'),
        ];
    }
}
