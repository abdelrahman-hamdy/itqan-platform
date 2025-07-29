<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuranTeacherResource\Pages;
use App\Models\QuranTeacher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\ActionGroup;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class QuranTeacherResource extends Resource
{
    protected static ?string $model = QuranTeacher::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'معلمو القرآن';

    protected static ?string $modelLabel = 'معلم قرآن';

    protected static ?string $pluralModelLabel = 'معلمو القرآن';

    protected static ?string $navigationGroup = 'قسم القرآن الكريم';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات المعلم الأساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('user_id')
                                    ->label('المستخدم')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('الاسم')
                                            ->required(),
                                        TextInput::make('email')
                                            ->label('البريد الإلكتروني')
                                            ->email()
                                            ->required(),
                                        TextInput::make('password')
                                            ->label('كلمة المرور')
                                            ->password()
                                            ->required(),
                                    ]),

                                Select::make('specialization')
                                    ->label('التخصص')
                                    ->options([
                                        'memorization' => 'الحفظ',
                                        'recitation' => 'التلاوة والتجويد',
                                        'interpretation' => 'التفسير',
                                        'arabic_language' => 'اللغة العربية',
                                        'general' => 'عام',
                                    ])
                                    ->required(),

                                Select::make('memorization_level')
                                    ->label('مستوى الحفظ')
                                    ->options([
                                        'beginner' => 'مبتدئ',
                                        'elementary' => 'أولي',
                                        'intermediate' => 'متوسط',
                                        'advanced' => 'متقدم',
                                        'expert' => 'خبير',
                                    ])
                                    ->required(),

                                TextInput::make('teaching_experience_years')
                                    ->label('سنوات الخبرة')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(50)
                                    ->required(),
                            ]),
                    ]),

                Section::make('معلومات الإجازة والشهادات')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('has_ijazah')
                                    ->label('لديه إجازة')
                                    ->live(),

                                Select::make('ijazah_type')
                                    ->label('نوع الإجازة')
                                    ->options([
                                        'memorization' => 'إجازة حفظ',
                                        'recitation' => 'إجازة تلاوة',
                                        'ten_readings' => 'إجازة القراءات العشر',
                                        'teaching' => 'إجازة تدريس',
                                        'general' => 'إجازة عامة',
                                    ])
                                    ->visible(fn (Forms\Get $get) => $get('has_ijazah')),

                                Textarea::make('ijazah_chain')
                                    ->label('سلسلة الإجازة')
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->visible(fn (Forms\Get $get) => $get('has_ijazah')),

                                TagsInput::make('certifications')
                                    ->label('الشهادات والدورات')
                                    ->placeholder('أضف شهادة'),

                                TagsInput::make('achievements')
                                    ->label('الإنجازات')
                                    ->placeholder('أضف إنجاز'),
                            ]),
                    ]),

                Section::make('معلومات التدريس والأسعار')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('hourly_rate_individual')
                                    ->label('السعر للجلسة الفردية')
                                    ->numeric()
                                    ->prefix('SAR')
                                    ->minValue(0)
                                    ->required(),

                                TextInput::make('hourly_rate_group')
                                    ->label('السعر للجلسة الجماعية')
                                    ->numeric()
                                    ->prefix('SAR')
                                    ->minValue(0)
                                    ->required(),

                                Select::make('currency')
                                    ->label('العملة')
                                    ->options([
                                        'SAR' => 'ريال سعودي',
                                        'USD' => 'دولار أمريكي',
                                        'EUR' => 'يورو',
                                    ])
                                    ->default('SAR')
                                    ->required(),

                                TextInput::make('max_students_per_circle')
                                    ->label('أقصى عدد طلاب في الدائرة')
                                    ->numeric()
                                    ->minValue(3)
                                    ->maxValue(15)
                                    ->default(8)
                                    ->required(),

                                TextInput::make('preferred_session_duration')
                                    ->label('مدة الجلسة المفضلة (دقيقة)')
                                    ->numeric()
                                    ->minValue(30)
                                    ->maxValue(120)
                                    ->default(45)
                                    ->required(),

                                TagsInput::make('available_grade_levels')
                                    ->label('المستويات المتاحة')
                                    ->placeholder('أضف مستوى'),
                            ]),
                    ]),

                Section::make('الجدول الزمني والتوفر')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TagsInput::make('available_days')
                                    ->label('الأيام المتاحة')
                                    ->suggestions([
                                        'saturday' => 'السبت',
                                        'sunday' => 'الأحد', 
                                        'monday' => 'الاثنين',
                                        'tuesday' => 'الثلاثاء',
                                        'wednesday' => 'الأربعاء',
                                        'thursday' => 'الخميس',
                                        'friday' => 'الجمعة',
                                    ])
                                    ->placeholder('اختر الأيام'),

                                TagsInput::make('available_times')
                                    ->label('الأوقات المتاحة')
                                    ->placeholder('مثال: 09:00-12:00')
                                    ->helperText('أدخل الأوقات بصيغة 09:00-12:00'),

                                TagsInput::make('teaching_methods')
                                    ->label('طرق التدريس')
                                    ->placeholder('أضف طريقة تدريس'),
                            ]),
                    ]),

                Section::make('النبذة التعريفية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Textarea::make('bio_ar')
                                    ->label('النبذة التعريفية (عربي)')
                                    ->rows(4)
                                    ->maxLength(2000),

                                Textarea::make('bio_en')
                                    ->label('النبذة التعريفية (إنجليزي)')
                                    ->rows(4)
                                    ->maxLength(2000),

                                Textarea::make('teaching_philosophy')
                                    ->label('فلسفة التدريس')
                                    ->rows(3)
                                    ->maxLength(1500)
                                    ->columnSpanFull(),

                                Textarea::make('notes')
                                    ->label('ملاحظات إدارية')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('حالة المعلم')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->label('الحالة')
                                    ->options([
                                        'active' => 'نشط',
                                        'inactive' => 'غير نشط',
                                        'suspended' => 'موقف',
                                        'pending' => 'في الانتظار',
                                    ])
                                    ->default('inactive'),

                                Select::make('approval_status')
                                    ->label('حالة الاعتماد')
                                    ->options([
                                        'pending' => 'في الانتظار',
                                        'approved' => 'معتمد',
                                        'rejected' => 'مرفوض',
                                        'under_review' => 'قيد المراجعة',
                                    ])
                                    ->default('pending'),
                            ]),
                    ])
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('اسم المعلم')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('teacher_code')
                    ->label('رمز المعلم')
                    ->searchable()
                    ->fontFamily('mono'),

                BadgeColumn::make('specialization')
                    ->label('التخصص')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'memorization' => 'الحفظ',
                        'recitation' => 'التلاوة والتجويد',
                        'interpretation' => 'التفسير',
                        'arabic_language' => 'اللغة العربية',
                        'general' => 'عام',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'memorization',
                        'warning' => 'recitation',
                        'info' => 'interpretation',
                        'primary' => 'arabic_language',
                        'secondary' => 'general',
                    ]),

                BadgeColumn::make('memorization_level')
                    ->label('مستوى الحفظ')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'beginner' => 'مبتدئ',
                        'elementary' => 'أولي',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                        'expert' => 'خبير',
                        default => $state,
                    }),

                TextColumn::make('teaching_experience_years')
                    ->label('سنوات الخبرة')
                    ->suffix(' سنة')
                    ->sortable(),

                BadgeColumn::make('has_ijazah')
                    ->label('الإجازة')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'لديه إجازة' : 'لا يوجد')
                    ->colors([
                        'success' => true,
                        'secondary' => false,
                    ]),

                TextColumn::make('hourly_rate_individual')
                    ->label('السعر الفردي')
                    ->money('SAR')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'نشط',
                        'inactive' => 'غير نشط',
                        'suspended' => 'موقف',
                        'pending' => 'في الانتظار',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'active',
                        'secondary' => 'inactive',
                        'danger' => 'suspended',
                        'warning' => 'pending',
                    ]),

                BadgeColumn::make('approval_status')
                    ->label('حالة الاعتماد')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'في الانتظار',
                        'approved' => 'معتمد',
                        'rejected' => 'مرفوض',
                        'under_review' => 'قيد المراجعة',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'info' => 'under_review',
                    ]),

                TextColumn::make('rating')
                    ->label('التقييم')
                    ->badge()
                    ->formatStateUsing(fn (?float $state): string => $state ? number_format($state, 1) . '/5' : 'لا يوجد'),

                TextColumn::make('total_students')
                    ->label('عدد الطلاب')
                    ->counts()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('specialization')
                    ->label('التخصص')
                    ->options([
                        'memorization' => 'الحفظ',
                        'recitation' => 'التلاوة والتجويد',
                        'interpretation' => 'التفسير',
                        'arabic_language' => 'اللغة العربية',
                        'general' => 'عام',
                    ]),

                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'نشط',
                        'inactive' => 'غير نشط',
                        'suspended' => 'موقف',
                        'pending' => 'في الانتظار',
                    ]),

                SelectFilter::make('approval_status')
                    ->label('حالة الاعتماد')
                    ->options([
                        'pending' => 'في الانتظار',
                        'approved' => 'معتمد',
                        'rejected' => 'مرفوض',
                        'under_review' => 'قيد المراجعة',
                    ]),

                TernaryFilter::make('has_ijazah')
                    ->label('الإجازة')
                    ->trueLabel('لديه إجازة')
                    ->falseLabel('لا يوجد إجازة')
                    ->placeholder('الكل'),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\Action::make('approve')
                        ->label('اعتماد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (QuranTeacher $record) => $record->update([
                            'approval_status' => 'approved',
                            'approved_at' => now(),
                            'approved_by' => auth()->id(),
                            'status' => 'active'
                        ]))
                        ->visible(fn (QuranTeacher $record) => $record->approval_status === 'pending'),
                    Tables\Actions\Action::make('reject')
                        ->label('رفض')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Textarea::make('rejection_reason')
                                ->label('سبب الرفض')
                                ->required()
                        ])
                        ->action(function (QuranTeacher $record, array $data) {
                            $record->update([
                                'approval_status' => 'rejected',
                                'approved_by' => auth()->id(),
                                'status' => 'inactive',
                                'notes' => ($record->notes ? $record->notes . "\n\n" : '') . 
                                          'سبب الرفض: ' . $data['rejection_reason']
                            ]);
                        })
                        ->visible(fn (QuranTeacher $record) => $record->approval_status === 'pending'),
                    Tables\Actions\DeleteAction::make()
                        ->label('حذف'),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات المعلم')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('اسم المعلم'),
                                Infolists\Components\TextEntry::make('teacher_code')
                                    ->label('رمز المعلم'),
                                Infolists\Components\TextEntry::make('specialization')
                                    ->label('التخصص')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'memorization' => 'الحفظ',
                                        'recitation' => 'التلاوة والتجويد',
                                        'interpretation' => 'التفسير',
                                        'arabic_language' => 'اللغة العربية',
                                        'general' => 'عام',
                                        default => $state,
                                    }),
                                Infolists\Components\TextEntry::make('memorization_level')
                                    ->label('مستوى الحفظ'),
                                Infolists\Components\TextEntry::make('teaching_experience_years')
                                    ->label('سنوات الخبرة')
                                    ->suffix(' سنة'),
                                Infolists\Components\IconEntry::make('has_ijazah')
                                    ->label('الإجازة')
                                    ->boolean(),
                            ]),
                    ]),
                    
                Infolists\Components\Section::make('معلومات الأسعار')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('hourly_rate_individual')
                                    ->label('السعر الفردي')
                                    ->money('SAR'),
                                Infolists\Components\TextEntry::make('hourly_rate_group')
                                    ->label('السعر الجماعي')
                                    ->money('SAR'),
                                Infolists\Components\TextEntry::make('max_students_per_circle')
                                    ->label('أقصى عدد طلاب'),
                            ]),
                    ]),

                Infolists\Components\Section::make('الإحصائيات')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('rating')
                                    ->label('التقييم')
                                    ->formatStateUsing(fn (?float $state): string => $state ? number_format($state, 1) . '/5' : 'لا يوجد'),
                                Infolists\Components\TextEntry::make('total_reviews')
                                    ->label('عدد التقييمات'),
                                Infolists\Components\TextEntry::make('total_sessions')
                                    ->label('عدد الجلسات'),
                                Infolists\Components\TextEntry::make('total_students')
                                    ->label('عدد الطلاب'),
                            ]),
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
            'index' => Pages\ListQuranTeachers::route('/'),
            'create' => Pages\CreateQuranTeacher::route('/create'),
            'view' => Pages\ViewQuranTeacher::route('/{record}'),
            'edit' => Pages\EditQuranTeacher::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('approval_status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}
