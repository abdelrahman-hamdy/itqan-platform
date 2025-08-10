<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\TeacherProfileResource\Pages;
use App\Models\QuranTeacherProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\ActionGroup;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;

class TeacherProfileResource extends Resource
{
    protected static ?string $model = QuranTeacherProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'ملفي الشخصي';

    protected static ?string $modelLabel = 'الملف الشخصي';

    protected static ?string $pluralModelLabel = 'الملف الشخصي';

    protected static ?string $navigationGroup = 'ملفي الشخصي';

    protected static ?int $navigationSort = 1;

    // Scope to only the current teacher's profile
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        
        if (!$user->isQuranTeacher() || !$user->quranTeacherProfile) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return no results
        }

        return parent::getEloquentQuery()
            ->where('id', $user->quranTeacherProfile->id)
            ->where('academy_id', $user->academy_id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('المعلومات الشخصية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('first_name')
                                    ->label('الاسم الأول')
                                    ->required()
                                    ->maxLength(255),
                                    
                                TextInput::make('last_name')
                                    ->label('اسم العائلة')
                                    ->required()
                                    ->maxLength(255),
                                    
                                TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                    
                                TextInput::make('phone')
                                    ->label('رقم الهاتف')
                                    ->tel()
                                    ->required()
                                    ->maxLength(20),
                            ]),
                            
                        FileUpload::make('avatar')
                            ->label('الصورة الشخصية')
                            ->image()
                            ->directory('avatars/teachers')
                            ->disk('public')
                            ->imageEditor()
                            ->circleCropper(),
                            
                        TextInput::make('teacher_code')
                            ->label('رمز المعلم')
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Section::make('المؤهلات العلمية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('educational_qualification')
                                    ->label('المؤهل العلمي')
                                    ->options([
                                        'high_school' => 'ثانوية عامة',
                                        'diploma' => 'دبلوم',
                                        'bachelor' => 'بكالوريوس',
                                        'master' => 'ماجستير',
                                        'phd' => 'دكتوراه',
                                        'other' => 'أخرى',
                                    ])
                                    ->required(),
                                    
                                TextInput::make('teaching_experience_years')
                                    ->label('سنوات الخبرة في التدريس')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(50)
                                    ->required(),
                            ]),
                            
                        TagsInput::make('certifications')
                            ->label('الشهادات والإجازات')
                            ->placeholder('أضف شهادة جديدة'),
                            
                        TagsInput::make('languages')
                            ->label('اللغات')
                            ->placeholder('أضف لغة جديدة')
                            ->suggestions([
                                'العربية',
                                'الإنجليزية',
                                'الفرنسية',
                                'التركية',
                                'الأردية',
                                'الماليزية',
                            ]),
                    ]),

                Section::make('أوقات العمل')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TimePicker::make('available_time_start')
                                    ->label('بداية الوقت المتاح')
                                    ->native(false)
                                    ->format('H:i'),
                                    
                                TimePicker::make('available_time_end')
                                    ->label('نهاية الوقت المتاح')
                                    ->native(false)
                                    ->format('H:i'),
                            ]),
                            
                        Select::make('available_days')
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
                            ->multiple()
                            ->required(),
                    ]),

                Section::make('نبذة تعريفية')
                    ->schema([
                        Textarea::make('bio_arabic')
                            ->label('النبذة التعريفية بالعربية')
                            ->rows(4)
                            ->maxLength(1000),
                            
                        Textarea::make('bio_english')
                            ->label('النبذة التعريفية بالإنجليزية')
                            ->rows(4)
                            ->maxLength(1000),
                    ]),

                Section::make('الإعدادات')
                    ->schema([
                        Toggle::make('offers_trial_sessions')
                            ->label('أقدم جلسات تجريبية')
                            ->helperText('هل تقبل طلبات الجلسات التجريبية من الطلاب الجدد؟'),
                            
                        Toggle::make('is_active')
                            ->label('الحساب نشط')
                            ->helperText('إذا كان الحساب غير نشط، لن يتمكن الطلاب من حجز جلسات معك')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('teacher_code')
                    ->label('رمز المعلم')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('full_name')
                    ->label('الاسم الكامل')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                    
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable(),
                    
                TextColumn::make('educational_qualification')
                    ->label('المؤهل العلمي')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'high_school' => 'ثانوية عامة',
                        'diploma' => 'دبلوم',
                        'bachelor' => 'بكالوريوس',
                        'master' => 'ماجستير',
                        'phd' => 'دكتوراه',
                        'other' => 'أخرى',
                        default => $state,
                    }),
                    
                TextColumn::make('teaching_experience_years')
                    ->label('سنوات الخبرة')
                    ->suffix(' سنة')
                    ->sortable(),
                    
                BadgeColumn::make('approval_status')
                    ->label('حالة الموافقة')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'في الانتظار',
                        'approved' => 'معتمد',
                        'rejected' => 'مرفوض',
                        default => $state,
                    }),
                    
                BadgeColumn::make('is_active')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state): string => $state ? 'نشط' : 'غير نشط')
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ]),
                    
                TextColumn::make('rating')
                    ->label('التقييم')
                    ->suffix('/5')
                    ->sortable()
                    ->color(fn ($record) => $record->rating >= 4 ? 'success' : 
                             ($record->rating >= 3 ? 'warning' : 'danger')),
                    
                TextColumn::make('total_students')
                    ->label('عدد الطلاب')
                    ->sortable(),
                    
                TextColumn::make('total_sessions')
                    ->label('إجمالي الجلسات')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
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
            'index' => Pages\ListTeacherProfiles::route('/'),
            'view' => Pages\ViewTeacherProfile::route('/{record}'),
            'edit' => Pages\EditTeacherProfile::route('/{record}/edit'),
        ];
    }
}