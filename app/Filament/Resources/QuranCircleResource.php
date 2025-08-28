<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuranCircleResource\Pages;
use App\Models\QuranCircle;
use App\Traits\ScopedToAcademy;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuranCircleResource extends BaseResource
{
    use ScopedToAcademy;

    protected static ?string $model = QuranCircle::class;

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy'; // QuranCircle -> Academy (direct relationship)
    }

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'حلقات القرآن الجماعية';

    protected static ?string $modelLabel = 'حلقة قرآن جماعية';

    protected static ?string $pluralModelLabel = 'حلقات القرآن الجماعية';

    protected static ?string $navigationGroup = 'إدارة القرآن';

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
                                    ->options(\App\Models\QuranTeacherProfile::all()
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
                                    ->maxValue(100)
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
                    ->description('سيتم تحديد الجدول من قبل المعلم لضمان عدم التعارض مع جدوله الشخصي')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Select::make('schedule_time')
                                    ->label('الساعة')
                                    ->options([
                                        '00:00' => '12:00 ص',
                                        '01:00' => '01:00 ص',
                                        '02:00' => '02:00 ص',
                                        '03:00' => '03:00 ص',
                                        '04:00' => '04:00 ص',
                                        '05:00' => '05:00 ص',
                                        '06:00' => '06:00 ص',
                                        '07:00' => '07:00 ص',
                                        '08:00' => '08:00 ص',
                                        '09:00' => '09:00 ص',
                                        '10:00' => '10:00 ص',
                                        '11:00' => '11:00 ص',
                                        '12:00' => '12:00 م',
                                        '13:00' => '01:00 م',
                                        '14:00' => '02:00 م',
                                        '15:00' => '03:00 م',
                                        '16:00' => '04:00 م',
                                        '17:00' => '05:00 م',
                                        '18:00' => '06:00 م',
                                        '19:00' => '07:00 م',
                                        '20:00' => '08:00 م',
                                        '21:00' => '09:00 م',
                                        '22:00' => '10:00 م',
                                        '23:00' => '11:00 م',
                                    ])
                                    ->disabled()
                                    ->placeholder('سيحدده المعلم')
                                    ->helperText('يحدد المعلم التوقيت من تقويمه'),

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
                                    ->disabled()
                                    ->placeholder('سيحدده المعلم')
                                    ->helperText('يحدد المعلم الأيام من تقويمه'),

                                Select::make('session_duration_minutes')
                                    ->label('مدة الجلسة')
                                    ->options([
                                        30 => '30 دقيقة',
                                        45 => '45 دقيقة',
                                        60 => '60 دقيقة',
                                    ])
                                    ->default(60)
                                    ->required(),

                                Select::make('monthly_sessions_count')
                                    ->label('عدد الجلسات الشهرية')
                                    ->options([
                                        4 => '4 جلسات (جلسة واحدة أسبوعياً)',
                                        8 => '8 جلسات (جلستين أسبوعياً)',
                                        12 => '12 جلسة (3 جلسات أسبوعياً)',
                                        16 => '16 جلسة (4 جلسات أسبوعياً)',
                                        20 => '20 جلسة (5 جلسات أسبوعياً)',
                                    ])
                                    ->default(8)
                                    ->required()
                                    ->helperText('يحدد هذا الرقم عدد الجلسات التي يمكن للمعلم جدولتها شهرياً'),

                                Select::make('schedule_period')
                                    ->label('فترة الجدولة')
                                    ->options([
                                        'week' => 'أسبوع واحد',
                                        'month' => 'شهر واحد',
                                        'two_months' => 'شهرين',
                                    ])
                                    ->default('month')
                                    ->required()
                                    ->helperText('تحدد كم من الوقت مقدماً يمكن جدولة الجلسات'),
                            ]),
                    ]),

                Section::make('إعدادات الاجتماعات')
                    ->description('إعدادات توقيت الاجتماعات والحضور للجلسات')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('preparation_minutes')
                                    ->label('وقت تحضير الاجتماع (دقيقة)')
                                    ->helperText('الوقت قبل بداية الجلسة لإنشاء الاجتماع')
                                    ->numeric()
                                    ->minValue(5)
                                    ->maxValue(30)
                                    ->default(15)
                                    ->required()
                                    ->suffix('دقيقة'),

                                TextInput::make('ending_buffer_minutes')
                                    ->label('وقت إضافي بعد انتهاء الجلسة (دقيقة)')
                                    ->helperText('الوقت الإضافي لبقاء الاجتماع مفتوح بعد انتهاء الجلسة')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(15)
                                    ->default(5)
                                    ->required()
                                    ->suffix('دقيقة'),

                                TextInput::make('late_join_grace_period_minutes')
                                    ->label('فترة السماح للانضمام المتأخر (دقيقة)')
                                    ->helperText('الوقت المسموح للطلاب للانضمام بعد بداية الجلسة دون اعتبارهم متأخرين')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->suffix('دقيقة')
                                    ->rule(function (Forms\Get $get) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $sessionDuration = $get('session_duration_minutes');
                                            if ($sessionDuration && $value > $sessionDuration) {
                                                $fail('فترة السماح للانضمام المتأخر لا يمكن أن تكون أكبر من مدة الجلسة.');
                                            }
                                        };
                                    }),
                            ]),
                    ]),

                Section::make('حلقات القرآن الجماعية')
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

                                Select::make('location_type')
                                    ->label('نوع المكان*')
                                    ->options([
                                        'online' => 'عبر الإنترنت',
                                        'physical' => 'حضوري',
                                        'hybrid' => 'مختلط',
                                    ])
                                    ->default('online')
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

                Section::make('الحالة والإعدادات')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('status')
                                    ->label('حالة الحلقة')
                                    ->helperText('تفعيل أو إلغاء تفعيل الحلقة')
                                    ->default(true),

                                Select::make('enrollment_status')
                                    ->label('حالة التسجيل')
                                    ->options([
                                        'closed' => 'مغلق',
                                        'open' => 'مفتوح',
                                        'full' => 'ممتلئ',
                                    ])
                                    ->default('closed'),

                                TextInput::make('enrolled_students')
                                    ->label('عدد الطلاب الحالي')
                                    ->numeric()
                                    ->disabled()
                                    ->default(fn ($record) => $record ? $record->students()->count() : 0)
                                    ->dehydrated(false),

                                Textarea::make('admin_notes')
                                    ->label('ملاحظات الإدارة')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->helperText('ملاحظات مرئية للمعلم والإدارة والمشرف فقط')
                                    ->columnSpanFull(),
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

                static::getAcademyColumn(),

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

                TextColumn::make('students_count')
                    ->label('المسجلون')
                    ->alignCenter()
                    ->color('info')
                    ->getStateUsing(fn ($record) => $record->students()->count()),

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
                    ->formatStateUsing(fn (bool $state): string => $state ? 'نشطة' : 'متوقفة')
                    ->colors([
                        'success' => true,
                        'danger' => false,
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
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        '1' => 'نشطة',
                        '0' => 'متوقفة',
                    ]),

                SelectFilter::make('memorization_level')
                    ->label('المستوى')
                    ->options([
                        'beginner' => 'مبتدئ',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                    ]),

                SelectFilter::make('enrollment_status')
                    ->label('حالة التسجيل')
                    ->options([
                        'closed' => 'مغلق',
                        'open' => 'مفتوح',
                        'full' => 'ممتلئ',
                    ]),

                SelectFilter::make('age_group')
                    ->label('الفئة العمرية')
                    ->options([
                        'children' => 'أطفال',
                        'youth' => 'شباب',
                        'adults' => 'كبار',
                        'all_ages' => 'كل الفئات',
                    ]),

                SelectFilter::make('gender_type')
                    ->label('النوع')
                    ->options([
                        'male' => 'رجال',
                        'female' => 'نساء',
                        'mixed' => 'مختلط',
                    ]),

                Filter::make('available_spots')
                    ->label('يوجد أماكن متاحة')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('(SELECT COUNT(*) FROM quran_circle_students WHERE circle_id = quran_circles.id) < max_students')
                    ),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\Action::make('toggle_status')
                        ->label(fn (QuranCircle $record) => $record->status ? 'إلغاء التفعيل' : 'تفعيل')
                        ->icon(fn (QuranCircle $record) => $record->status ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                        ->color(fn (QuranCircle $record) => $record->status ? 'warning' : 'success')
                        ->requiresConfirmation()
                        ->modalHeading(fn (QuranCircle $record) => $record->status ? 'إلغاء تفعيل الحلقة' : 'تفعيل الحلقة')
                        ->modalDescription(fn (QuranCircle $record) => $record->status
                            ? 'هل أنت متأكد من إلغاء تفعيل هذه الحلقة؟ لن يتمكن الطلاب من الانضمام إليها.'
                            : 'هل أنت متأكد من تفعيل هذه الحلقة؟ ستصبح متاحة للطلاب للانضمام.'
                        )
                        ->action(fn (QuranCircle $record) => $record->update([
                            'status' => ! $record->status,
                            'enrollment_status' => $record->status ? 'closed' : 'open',
                        ])),
                    Tables\Actions\Action::make('activate')
                        ->label('تفعيل للتسجيل')
                        ->icon('heroicon-o-megaphone')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (QuranCircle $record) => $record->update([
                            'status' => true,
                            'enrollment_status' => 'open',
                        ])),
                    Tables\Actions\DeleteAction::make()
                        ->label('حذف'),
                ]),
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

    public static function getNavigationBadge(): ?string
    {
        // Use the scoped query from trait for consistent academy filtering
        $query = static::getEloquentQuery()->where('status', false);

        return $query->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'info';
    }
}
