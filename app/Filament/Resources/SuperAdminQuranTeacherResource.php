<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SuperAdminQuranTeacherResource\Pages;
use App\Models\QuranTeacherProfile;
use App\Models\Academy;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;

class SuperAdminQuranTeacherResource extends Resource
{
    protected static ?string $model = QuranTeacherProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'معلمو القرآن (عالمي)';

    protected static ?string $modelLabel = 'معلم قرآن';

    protected static ?string $pluralModelLabel = 'معلمو القرآن';

    protected static ?string $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 1;

    // NO ACADEMY SCOPING - Show all teachers across all academies

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('الأكاديمية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('academy_id')
                                    ->label('الأكاديمية')
                                    ->options(Academy::pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ]),

                Section::make('المعلومات الشخصية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('first_name')
                                    ->label('الاسم الأول')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('last_name')
                                    ->label('الاسم الأخير')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->helperText('سيستخدم المعلم هذا البريد للدخول إلى المنصة'),
                                TextInput::make('phone')
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

                Section::make('المؤهلات والخبرة')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('educational_qualification')
                                    ->label('المؤهل التعليمي')
                                    ->options([
                                        'bachelor' => 'بكالوريوس',
                                        'master' => 'ماجستير',
                                        'phd' => 'دكتوراه',
                                        'diploma' => 'دبلوم',
                                        'other' => 'أخرى',
                                    ])
                                    ->default('bachelor')
                                    ->required(),
                                TextInput::make('teaching_experience_years')
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

                Section::make('الأوقات المتاحة')
                    ->schema([
                        Grid::make(2)
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

                Section::make('السيرة الذاتية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Textarea::make('bio_arabic')
                                    ->label('السيرة الذاتية (عربي)')
                                    ->maxLength(1000)
                                    ->rows(4)
                                    ->helperText('اكتب نبذة عن خبرتك في تدريس القرآن الكريم'),
                                Textarea::make('bio_english')
                                    ->label('السيرة الذاتية (إنجليزي)')
                                    ->maxLength(1000)
                                    ->rows(4),
                            ]),
                    ]),

                Section::make('الحالة والإعدادات')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('نشط')
                                    ->default(true),
                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('الصورة')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->full_name) . '&background=4169E1&color=fff'),

                TextColumn::make('teacher_code')
                    ->label('رمز المعلم')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('full_name')
                    ->label('اسم المعلم')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('academy.name')
                    ->label('الأكاديمية')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('email')
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

                BadgeColumn::make('is_active')
                    ->label('نشط')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'نشط' : 'غير نشط')
                    ->colors([
                        'success' => true,
                        'gray' => false,
                    ]),

                TextColumn::make('total_students')
                    ->label('عدد الطلاب')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('total_sessions')
                    ->label('عدد الجلسات')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('rating')
                    ->label('التقييم')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        return str_repeat('⭐', round($state)) . " ({$state}/5)";
                    }),

                TextColumn::make('languages')
                    ->label('اللغات')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state)) return '-';
                        $languageNames = [
                            'arabic' => 'العربية',
                            'english' => 'الإنجليزية',
                            'french' => 'الفرنسية',
                            'urdu' => 'الأردو',
                            'turkish' => 'التركية',
                            'malay' => 'الماليزية',
                        ];
                        return collect($state)->map(fn($lang) => $languageNames[$lang] ?? $lang)->implode(', ');
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('certifications')
                    ->label('الشهادات')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state)) return '-';
                        return collect($state)->take(2)->implode(', ') . (count($state) > 2 ? '...' : '');
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('educational_qualification')
                    ->label('المؤهل التعليمي')
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'bachelor' => 'بكالوريوس',
                            'master' => 'ماجستير',
                            'phd' => 'دكتوراه',
                            'diploma' => 'دبلوم',
                            'other' => 'أخرى',
                            default => $state,
                        };
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('teaching_experience_years')
                    ->label('سنوات الخبرة')
                    ->numeric()
                    ->suffix(' سنة')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('academy_id')
                    ->label('الأكاديمية')
                    ->options(Academy::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),



                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('نشط'),

                SelectFilter::make('educational_qualification')
                    ->label('المؤهل التعليمي')
                    ->options([
                        'bachelor' => 'بكالوريوس',
                        'master' => 'ماجستير',
                        'phd' => 'دكتوراه',
                        'diploma' => 'دبلوم',
                        'other' => 'أخرى',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('activate')
                        ->label('تفعيل')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => !$record->is_active)
                        ->action(function ($record) {
                            $record->update(['is_active' => true]);
                        })
                        ->successNotificationTitle('تم تفعيل المعلم بنجاح'),
                        
                    Tables\Actions\Action::make('deactivate')
                        ->label('إلغاء تفعيل')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => $record->is_active)
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['is_active' => false]);
                        })
                        ->successNotificationTitle('تم إلغاء تفعيل المعلم بنجاح'),

                    Tables\Actions\Action::make('suspend')
                        ->label('إيقاف')
                        ->icon('heroicon-o-no-symbol')
                        ->color('warning')
                        ->visible(fn (QuranTeacherProfile $record) => $record->is_active)
                        ->form([
                            Textarea::make('suspension_reason')
                                ->label('سبب الإيقاف')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (QuranTeacherProfile $record, array $data) {
                            $record->suspend($data['suspension_reason']);
                        })
                        ->successNotificationTitle('تم إيقاف المعلم'),

                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('اعتماد المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->approve();
                        })
                        ->requiresConfirmation()
                        ->successNotificationTitle('تم اعتماد المعلمين المحددين'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('المعلومات الأساسية')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('teacher_code')
                                    ->label('رمز المعلم')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('academy.name')
                                    ->label('الأكاديمية')
                                    ->badge()
                                    ->color('primary'),

                                Infolists\Components\TextEntry::make('full_name')
                                    ->label('اسم المعلم')
                                    ->weight(FontWeight::Bold),

                                Infolists\Components\TextEntry::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('phone')
                                    ->label('رقم الهاتف'),


                            ])
                    ]),

                Infolists\Components\Section::make('الإحصائيات')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_students')
                                    ->label('إجمالي الطلاب'),

                                Infolists\Components\TextEntry::make('total_sessions')
                                    ->label('إجمالي الجلسات'),

                                Infolists\Components\TextEntry::make('total_reviews')
                                    ->label('عدد التقييمات'),

                                Infolists\Components\TextEntry::make('rating')
                                    ->label('متوسط التقييم')
                                    ->formatStateUsing(function ($state) {
                                        if (!$state) return 'لا يوجد تقييم';
                                        return str_repeat('⭐', round($state)) . " ({$state}/5)";
                                    }),
                            ])
                    ]),

                Infolists\Components\Section::make('السيرة الذاتية')
                    ->schema([
                        Infolists\Components\TextEntry::make('bio_arabic')
                            ->label('السيرة الذاتية (عربي)')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('bio_english')
                            ->label('السيرة الذاتية (إنجليزي)')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuperAdminQuranTeachers::route('/'),
            'view' => Pages\ViewSuperAdminQuranTeacher::route('/{record}'),
            'edit' => Pages\EditSuperAdminQuranTeacher::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Only super admin can access global Quran teacher management
        return \App\Services\AcademyContextService::isSuperAdmin();
    }
}