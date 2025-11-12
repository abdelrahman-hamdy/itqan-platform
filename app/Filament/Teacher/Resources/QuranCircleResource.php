<?php

namespace App\Filament\Teacher\Resources;

use App\Enums\WeekDays;
use App\Enums\DifficultyLevel;
use App\Enums\SessionDuration;
use App\Filament\Teacher\Resources\QuranCircleResource\Pages;
use App\Models\QuranCircle;
use App\Filament\Teacher\Resources\BaseTeacherResource;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class QuranCircleResource extends BaseTeacherResource
{
    protected static ?string $model = QuranCircle::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'حلقات القرآن الجماعية';

    protected static ?string $modelLabel = 'حلقة قرآن جماعية';

    protected static ?string $pluralModelLabel = 'حلقات القرآن الجماعية';

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 3;

    /**
     * Check if current user can view this record
     * Teachers can only view circles assigned to them
     */
    public static function canView(Model $record): bool
    {
        $user = Auth::user();

        if (!$user->isQuranTeacher() || !$user->quranTeacherProfile) {
            return false;
        }

        // Allow viewing if circle belongs to current teacher
        // quran_teacher_id now stores user_id directly after migration
        return $record->quran_teacher_id === $user->id;
    }

    /**
     * Check if current user can edit this record
     * Teachers can edit circles assigned to them
     */
    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        if (!$user->isQuranTeacher() || !$user->quranTeacherProfile) {
            return false;
        }

        // Allow editing if circle belongs to current teacher
        // quran_teacher_id now stores user_id directly after migration
        return $record->quran_teacher_id === $user->id;
    }

    /**
     * Get the Eloquent query with teacher-specific filtering
     * Only show circles for the current teacher
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (!$user->isQuranTeacher() || !$user->quranTeacherProfile) {
            return $query->whereRaw('1 = 0'); // Return no results
        }

        // quran_teacher_id now stores user_id directly after migration
        return $query->where('quran_teacher_id', $user->id);
    }

    /**
     * Teachers CAN create new circles
     * The circle will be automatically assigned to the current teacher
     */
    public static function canCreate(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Hidden field to auto-assign teacher
                TextInput::make('quran_teacher_id')
                    ->hidden()
                    ->dehydrated()
                    ->default(function () {
                        $user = Auth::user();
                        // quran_teacher_id now stores user_id directly after migration
                        return $user ? $user->id : null;
                    }),

                Section::make('معلومات الحلقة الأساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name_ar')
                                    ->label('اسم الحلقة (عربي)')
                                    ->required()
                                    ->maxLength(150),

                                TextInput::make('name_en')
                                    ->label('اسم الحلقة (إنجليزي)')
                                    ->maxLength(150),

                                TextInput::make('circle_code')
                                    ->label('رمز الحلقة')
                                    ->disabled()
                                    ->dehydrated(false),

                                Select::make('age_group')
                                    ->label('الفئة العمرية')
                                    ->options([
                                        'children' => 'أطفال (5-12 سنة)',
                                        'youth' => 'شباب (13-17 سنة)',
                                        'adults' => 'بالغون (18+ سنة)',
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

                                Select::make('specialization')
                                    ->label('التخصص')
                                    ->options([
                                        'memorization' => 'حفظ القرآن',
                                        'recitation' => 'تلاوة وتجويد',
                                        'interpretation' => 'تفسير',
                                        'arabic_language' => 'اللغة العربية',
                                        'complete' => 'شامل',
                                    ])
                                    ->default('memorization')
                                    ->required(),

                                Select::make('memorization_level')
                                    ->label('مستوى الحفظ')
                                    ->options(DifficultyLevel::options())
                                    ->default(DifficultyLevel::BEGINNER->value)
                                    ->required(),
                            ]),

                        TagsInput::make('learning_objectives')
                            ->label('أهداف الحلقة')
                            ->placeholder('أضف هدفاً من أهداف الحلقة')
                            ->helperText('أهداف تعليمية واضحة ومحددة للحلقة')
                            ->reorderable()
                            ->columnSpanFull(),

                        Textarea::make('description_ar')
                            ->label('وصف الحلقة (عربي)')
                            ->rows(3)
                            ->maxLength(500),

                        Textarea::make('description_en')
                            ->label('وصف الحلقة (إنجليزي)')
                            ->rows(3)
                            ->maxLength(500),
                    ]),

                Section::make('إعدادات الحلقة')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('max_students')
                                    ->label('الحد الأقصى للطلاب')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(20)
                                    ->default(8)
                                    ->required(),

                                TextInput::make('enrolled_students')
                                    ->label('عدد الطلاب الحالي')
                                    ->numeric()
                                    ->disabled()
                                    ->default(fn ($record) => $record ? $record->students()->count() : 0)
                                    ->dehydrated(false),

                                Select::make('session_duration_minutes')
                                    ->label('مدة الجلسة (بالدقائق)')
                                    ->options(SessionDuration::options())
                                    ->default(60)
                                    ->required(),

                                TextInput::make('monthly_fee')
                                    ->label('الرسوم الشهرية')
                                    ->numeric()
                                    ->prefix('SAR')
                                    ->minValue(0)
                                    ->required()
                                    ->helperText('هذه الرسوم للطلاب المشتركين في الحلقة'),

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
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('schedule_days')
                                    ->label('أيام الانعقاد')
                                    ->options(WeekDays::options())
                                    ->multiple()
                                    ->native(false)
                                    ->helperText('أيام انعقاد الحلقة - للمعلومات العامة'),

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
                                    ->native(false)
                                    ->searchable()
                                    ->helperText('تحديد الساعة المحددة لبداية الجلسات'),
                            ]),
                    ]),

                Section::make('الحالة والإعدادات الإدارية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('status')
                                    ->label('حالة الحلقة')
                                    ->helperText('تفعيل أو إلغاء تفعيل الحلقة')
                                    ->default(true),

                                Textarea::make('admin_notes')
                                    ->label('ملاحظات الإدارة')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->helperText('ملاحظات مرئية للمعلم والإدارة والمشرف فقط')
                                    ->columnSpanFull()
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('circle_code')
                    ->label('رمز الحلقة')
                    ->searchable()
                    ->fontFamily('mono')
                    ->weight(FontWeight::Bold),

                TextColumn::make('name_ar')
                    ->label('اسم الحلقة')
                    ->searchable()
                    ->limit(30),

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
                    ->label('أيام الانعقاد')
                    ->formatStateUsing(function ($state) {
                        if (! is_array($state) || empty($state)) {
                            return 'غير محدد';
                        }

                        return WeekDays::getDisplayNames($state);
                    })
                    ->wrap(),

                TextColumn::make('schedule_time')
                    ->label('الساعة')
                    ->placeholder('غير محدد'),

                TextColumn::make('schedule_status')
                    ->label('حالة الجدولة')
                    ->getStateUsing(fn ($record) => $record->schedule ? 'مُجدولة' : 'غير مُجدولة')
                    ->badge()
                    ->color(fn ($state) => $state === 'مُجدولة' ? 'success' : 'warning'),

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

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->options(DifficultyLevel::options()),

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

                    Tables\Actions\Action::make('view_circle')
                        ->label('عرض تفاصيل الحلقة')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn (QuranCircle $record): string =>
                            route('teacher.group-circles.show', [
                                'subdomain' => Auth::user()->academy->subdomain,
                                'circle' => $record->id
                            ])
                        )
                        ->openUrlInNewTab(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
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
}
