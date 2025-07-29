<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuranCircleResource\Pages;
use App\Models\QuranCircle;
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
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class QuranCircleResource extends Resource
{
    protected static ?string $model = QuranCircle::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'حلقات القرآن';

    protected static ?string $modelLabel = 'حلقة قرآن';

    protected static ?string $pluralModelLabel = 'حلقات القرآن';

    protected static ?string $navigationGroup = 'قسم القرآن الكريم';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات الدائرة الأساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('quran_teacher_id')
                                    ->label('معلم القرآن')
                                    ->options(\App\Models\QuranTeacher::where('approval_status', 'approved')
                                        ->get()
                                        ->pluck('full_name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                TextInput::make('name_ar')
                                    ->label('اسم الدائرة (عربي)')
                                    ->required()
                                    ->maxLength(100),

                                TextInput::make('name_en')
                                    ->label('اسم الدائرة (إنجليزي)')
                                    ->maxLength(100),

                                Select::make('memorization_level')
                                    ->label('المستوى')
                                    ->options([
                                        'beginner' => 'مبتدئ',
                                        'intermediate' => 'متوسط',
                                        'advanced' => 'متقدم',
                                    ])
                                    ->required(),
                            ]),
                    ]),

                Section::make('تفاصيل الفئة المستهدفة')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('age_group')
                                    ->label('الفئة العمرية')
                                    ->options([
                                        'children' => 'أطفال',
                                        'youth' => 'شباب',
                                        'adults' => 'كبار',
                                        'all_ages' => 'كل الفئات',
                                    ])
                                    ->required(),

                                Select::make('gender_type')
                                    ->label('النوع')
                                    ->options([
                                        'male' => 'رجال',
                                        'female' => 'نساء',
                                        'mixed' => 'مختلط',
                                    ])
                                    ->required(),



                                TextInput::make('max_students')
                                    ->label('الحد الأقصى للطلاب')
                                    ->numeric()
                                    ->minValue(3)
                                    ->maxValue(15)
                                    ->default(8)
                                    ->required(),

                                TextInput::make('monthly_fee')
                                    ->label('الرسوم الشهرية')
                                    ->numeric()
                                    ->prefix('SAR')
                                    ->minValue(0)
                                    ->required(),
                            ]),
                    ]),

                Section::make('الجدول الزمني')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('schedule_days')
                                    ->label('أيام الأسبوع')
                                    ->options([
                                        'saturday' => 'السبت',
                                        'sunday' => 'الأحد',
                                        'monday' => 'الاثنين',
                                        'tuesday' => 'الثلاثاء',
                                        'wednesday' => 'الأربعاء',
                                        'thursday' => 'الخميس',
                                        'friday' => 'الجمعة',
                                    ])
                                    ->multiple()
                                    ->required(),

                                TimePicker::make('schedule_time')
                                    ->label('توقيت الحلقة')
                                    ->required(),

                                TextInput::make('session_duration_minutes')
                                    ->label('مدة الجلسة (دقيقة)')
                                    ->numeric()
                                    ->minValue(30)
                                    ->maxValue(120)
                                    ->default(60)
                                    ->required(),
                            ]),
                    ]),

                Section::make('نوع الدائرة والمناهج')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('circle_type')
                                    ->label('نوع الدائرة')
                                    ->options([
                                        'memorization' => 'حفظ',
                                        'recitation' => 'تلاوة وتجويد',
                                        'interpretation' => 'تفسير',
                                        'general' => 'عام',
                                    ])
                                    ->required(),

                                TagsInput::make('learning_objectives')
                                    ->label('أهداف التعلم')
                                    ->placeholder('أضف هدف تعليمي')
                                    ->reorderable(),

                                Textarea::make('prerequisites')
                                    ->label('المتطلبات المسبقة')
                                    ->rows(3)
                                    ->maxLength(500),
                            ]),
                    ]),



                Section::make('المكان ووسائل التعلم')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('location_type')
                                    ->label('نوع المكان')
                                    ->options([
                                        'online' => 'عبر الإنترنت',
                                        'physical' => 'حضوري',
                                        'hybrid' => 'مختلط',
                                    ])
                                    ->default('online')
                                    ->required(),

                                TextInput::make('physical_location')
                                    ->label('المكان الفعلي')
                                    ->maxLength(200)
                                    ->visible(fn (Forms\Get $get) => in_array($get('location_type'), ['physical', 'hybrid'])),

                                TextInput::make('online_platform')
                                    ->label('منصة الإنترنت')
                                    ->maxLength(100)
                                    ->visible(fn (Forms\Get $get) => in_array($get('location_type'), ['online', 'hybrid'])),

                                TextInput::make('meeting_link')
                                    ->label('رابط الاجتماع')
                                    ->url()
                                    ->visible(fn (Forms\Get $get) => in_array($get('location_type'), ['online', 'hybrid'])),

                                TagsInput::make('materials_required')
                                    ->label('المواد المطلوبة')
                                    ->placeholder('أضف مادة'),
                            ]),
                    ]),

                Section::make('الوصف والملاحظات')
                    ->schema([
                        Textarea::make('description_ar')
                            ->label('وصف الدائرة (عربي)')
                            ->rows(4)
                            ->maxLength(500),

                        Textarea::make('description_en')
                            ->label('وصف الدائرة (إنجليزي)')
                            ->rows(4)
                            ->maxLength(500),

                        Textarea::make('notes')
                            ->label('ملاحظات إدارية')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),

                Section::make('حالة الدائرة')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->label('الحالة')
                                    ->options([
                                        'draft' => 'مسودة',
                                        'published' => 'منشورة',
                                        'active' => 'نشطة',
                                        'completed' => 'مكتملة',
                                        'cancelled' => 'ملغية',
                                        'suspended' => 'موقفة',
                                    ])
                                    ->default('draft'),

                                Select::make('enrollment_status')
                                    ->label('حالة التسجيل')
                                    ->options([
                                        'closed' => 'مغلق',
                                        'open' => 'مفتوح',
                                        'full' => 'ممتلئ',
                                    ])
                                    ->default('closed'),
                            ]),
                    ])
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('circle_code')
                    ->label('رمز الدائرة')
                    ->searchable()
                    ->fontFamily('mono')
                    ->weight(FontWeight::Bold),

                TextColumn::make('name_ar')
                    ->label('اسم الدائرة')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('quranTeacher.full_name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('memorization_level')
                    ->label('المستوى')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'beginner' => 'مبتدئ',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                        default => $state,
                    }),

                BadgeColumn::make('age_group')
                    ->label('الفئة العمرية')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'children' => 'أطفال',
                        'youth' => 'شباب',
                        'adults' => 'كبار',
                        'all_ages' => 'كل الفئات',
                        default => $state,
                    }),

                BadgeColumn::make('gender_type')
                    ->label('النوع')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'male' => 'رجال',
                        'female' => 'نساء',
                        'mixed' => 'مختلط',
                        default => $state,
                    })
                    ->colors([
                        'info' => 'male',
                        'success' => 'female',
                        'warning' => 'mixed',
                    ]),

                TextColumn::make('enrolled_students')
                    ->label('المسجلون')
                    ->alignCenter()
                    ->color('info'),

                TextColumn::make('max_students')
                    ->label('الحد الأقصى')
                    ->alignCenter(),

                TextColumn::make('schedule_days')
                    ->label('الأيام')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            $days = [];
                            foreach ($state as $day) {
                                $days[] = match ($day) {
                                    'saturday' => 'السبت',
                                    'sunday' => 'الأحد',
                                    'monday' => 'الاثنين',
                                    'tuesday' => 'الثلاثاء',
                                    'wednesday' => 'الأربعاء',
                                    'thursday' => 'الخميس',
                                    'friday' => 'الجمعة',
                                    default => $day,
                                };
                            }
                            return implode(', ', $days);
                        }
                        return match ($state) {
                            'saturday' => 'السبت',
                            'sunday' => 'الأحد',
                            'monday' => 'الاثنين',
                            'tuesday' => 'الثلاثاء',
                            'wednesday' => 'الأربعاء',
                            'thursday' => 'الخميس',
                            'friday' => 'الجمعة',
                            default => $state,
                        };
                    }),

                TextColumn::make('schedule_time')
                    ->label('الوقت')
                    ->time('H:i'),

                TextColumn::make('monthly_fee')
                    ->label('الرسوم الشهرية')
                    ->money('SAR'),

                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'مسودة',
                        'published' => 'منشورة',
                        'active' => 'نشطة',
                        'completed' => 'مكتملة',
                        'cancelled' => 'ملغية',
                        'suspended' => 'موقفة',
                        default => $state,
                    })
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'published',
                        'success' => 'active',
                        'primary' => 'completed',
                        'danger' => 'cancelled',
                        'warning' => 'suspended',
                    ]),

                BadgeColumn::make('enrollment_status')
                    ->label('التسجيل')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'closed' => 'مغلق',
                        'open' => 'مفتوح',
                        'full' => 'ممتلئ',
                        default => $state,
                    })
                    ->colors([
                        'secondary' => 'closed',
                        'success' => 'open',
                        'warning' => 'full',
                    ]),

                TextColumn::make('circle_start_date')
                    ->label('تاريخ البداية')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'published' => 'منشورة',
                        'active' => 'نشطة',
                        'completed' => 'مكتملة',
                        'cancelled' => 'ملغية',
                        'suspended' => 'موقفة',
                    ]),

                SelectFilter::make('level')
                    ->label('المستوى')
                    ->options([
                        'beginner' => 'مبتدئ',
                        'elementary' => 'أولي',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                        'expert' => 'خبير',
                    ]),

                SelectFilter::make('enrollment_status')
                    ->label('حالة التسجيل')
                    ->options([
                        'closed' => 'مغلق',
                        'open' => 'مفتوح',
                        'full' => 'ممتلئ',
                    ]),

                Filter::make('available_spots')
                    ->label('يوجد أماكن متاحة')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('enrolled_students < max_students')),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\Action::make('publish')
                        ->label('نشر للتسجيل')
                        ->icon('heroicon-o-megaphone')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (QuranCircle $record) => $record->update([
                            'status' => 'published',
                            'enrollment_status' => 'open',
                        ]))
                        ->visible(fn (QuranCircle $record) => $record->status === 'draft'),
                    Tables\Actions\Action::make('start')
                        ->label('بدء الدائرة')
                        ->icon('heroicon-o-play-circle')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->action(fn (QuranCircle $record) => $record->update([
                            'status' => 'active',
                            'enrollment_status' => 'closed',
                            'actual_start_date' => now(),
                        ]))
                        ->visible(fn (QuranCircle $record) => $record->status === 'published' && $record->enrolled_students >= 3),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranCircles::route('/'),
            'create' => Pages\CreateQuranCircle::route('/create'),
            'view' => Pages\ViewQuranCircle::route('/{record}'),
            'edit' => Pages\EditQuranCircle::route('/{record}/edit'),
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
        return static::getModel()::where('status', 'draft')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'info';
    }
} 