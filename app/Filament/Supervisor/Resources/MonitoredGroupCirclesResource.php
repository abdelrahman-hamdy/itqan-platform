<?php

namespace App\Filament\Supervisor\Resources;

use App\Enums\CircleEnrollmentStatus;
use App\Enums\DifficultyLevel;
use App\Enums\WeekDays;
use App\Filament\Supervisor\Resources\MonitoredGroupCirclesResource\Pages;
use App\Models\QuranCircle;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Monitored Group Circles Resource for Supervisor Panel
 * Aligned with Admin QuranCircleResource - allows supervisors to manage group Quran circles
 * for their assigned teachers
 */
class MonitoredGroupCirclesResource extends BaseSupervisorResource
{
    protected static ?string $model = QuranCircle::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'الحلقات الجماعية';

    protected static ?string $modelLabel = 'حلقة جماعية';

    protected static ?string $pluralModelLabel = 'الحلقات الجماعية';

    protected static ?string $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات الحلقة الأساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('اسم الحلقة')
                                    ->required()
                                    ->maxLength(150),

                                Select::make('quran_teacher_id')
                                    ->label('معلم القرآن')
                                    ->options(function () {
                                        $teacherIds = static::getAssignedQuranTeacherIds();
                                        if (empty($teacherIds)) {
                                            return ['0' => 'لا توجد معلمين مُسندين'];
                                        }

                                        return \App\Models\QuranTeacherProfile::whereIn('user_id', $teacherIds)
                                            ->active()
                                            ->get()
                                            ->mapWithKeys(function ($teacher) {
                                                $userId = $teacher->user_id;
                                                $fullName = $teacher->display_name ?? $teacher->full_name ?? 'معلم غير محدد';

                                                return [$userId => $fullName];
                                            })->toArray();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required(),

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

                        Textarea::make('description')
                            ->label('وصف الحلقة')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        TagsInput::make('learning_objectives')
                            ->label('أهداف الحلقة')
                            ->placeholder('أضف هدفاً من أهداف الحلقة')
                            ->helperText('أهداف تعليمية واضحة ومحددة للحلقة')
                            ->reorderable()
                            ->columnSpanFull(),
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

                                TextInput::make('students_count')
                                    ->label('عدد الطلاب الحالي')
                                    ->numeric()
                                    ->disabled()
                                    ->default(fn ($record) => $record ? $record->students()->count() : 0)
                                    ->dehydrated(false),

                                TextInput::make('monthly_fee')
                                    ->label('الرسوم الشهرية')
                                    ->numeric()
                                    ->prefix(getCurrencyCode())
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
                                    ->helperText('وقت الحلقة - للمعلومات العامة'),
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

                                Toggle::make('enrollment_status')
                                    ->label('حالة التسجيل')
                                    ->helperText('تفعيل/إلغاء تفعيل التسجيل في الحلقة')
                                    ->default(false)
                                    ->live()
                                    ->dehydrateStateUsing(fn ($state) => $state ? CircleEnrollmentStatus::OPEN : CircleEnrollmentStatus::CLOSED)
                                    ->formatStateUsing(function ($state) {
                                        return $state === CircleEnrollmentStatus::OPEN || $state === CircleEnrollmentStatus::FULL;
                                    }),
                            ]),
                    ]),

                Section::make('ملاحظات')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Textarea::make('admin_notes')
                                    ->label('ملاحظات الإدارة')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->helperText('ملاحظات داخلية للإدارة'),

                                Textarea::make('supervisor_notes')
                                    ->label('ملاحظات المشرف')
                                    ->rows(3)
                                    ->maxLength(2000)
                                    ->helperText('ملاحظات مرئية للمشرف والإدارة فقط'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('circle_code')
                    ->label('رمز الحلقة')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الحلقة')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('quranTeacher.full_name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('memorization_level')
                    ->label('المستوى')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'beginner' => 'مبتدئ',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                        default => $state,
                    }),

                Tables\Columns\BadgeColumn::make('age_group')
                    ->label('الفئة العمرية')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'children' => 'أطفال',
                        'youth' => 'شباب',
                        'adults' => 'كبار',
                        'all_ages' => 'كل الفئات',
                        default => $state,
                    }),

                Tables\Columns\BadgeColumn::make('gender_type')
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

                Tables\Columns\TextColumn::make('students_count')
                    ->label('المسجلون')
                    ->alignCenter()
                    ->color('info')
                    ->getStateUsing(fn ($record) => $record->students()->count()),

                Tables\Columns\TextColumn::make('max_students')
                    ->label('الحد الأقصى')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('schedule_days')
                    ->label('أيام الانعقاد')
                    ->formatStateUsing(function ($state) {
                        if (! is_array($state) || empty($state)) {
                            return 'غير محدد';
                        }

                        return WeekDays::getDisplayNames($state);
                    })
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('schedule_time')
                    ->label('الساعة')
                    ->placeholder('غير محدد')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('monthly_fee')
                    ->label('الرسوم الشهرية')
                    ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR'))
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'نشطة' : 'متوقفة')
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ]),

                Tables\Columns\BadgeColumn::make('enrollment_status')
                    ->label('التسجيل')
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof \App\Enums\CircleEnrollmentStatus) {
                            return $state->arabicLabel();
                        }

                        return match ($state) {
                            'open' => 'مفتوح',
                            'closed' => 'مغلق',
                            'full' => 'ممتلئ',
                            'waitlist' => 'قائمة انتظار',
                            default => 'غير محدد',
                        };
                    })
                    ->color(function ($state): string {
                        if ($state instanceof \App\Enums\CircleEnrollmentStatus) {
                            return $state->color();
                        }

                        return match ($state) {
                            'open' => 'success',
                            'closed' => 'gray',
                            'full' => 'warning',
                            'waitlist' => 'info',
                            default => 'gray',
                        };
                    })
                    ->alignCenter()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('status')
                    ->label('الحالة')
                    ->trueLabel('نشطة')
                    ->falseLabel('متوقفة'),

                Tables\Filters\SelectFilter::make('memorization_level')
                    ->label('مستوى الحفظ')
                    ->options(DifficultyLevel::options()),

                Tables\Filters\SelectFilter::make('enrollment_status')
                    ->label('حالة التسجيل')
                    ->options([
                        'open' => 'مفتوح',
                        'closed' => 'مغلق',
                        'full' => 'ممتلئ',
                    ]),

                Tables\Filters\SelectFilter::make('age_group')
                    ->label('الفئة العمرية')
                    ->options([
                        'children' => 'أطفال',
                        'youth' => 'شباب',
                        'adults' => 'كبار',
                        'all_ages' => 'كل الفئات',
                    ]),

                Tables\Filters\SelectFilter::make('gender_type')
                    ->label('النوع')
                    ->options([
                        'male' => 'رجال',
                        'female' => 'نساء',
                        'mixed' => 'مختلط',
                    ]),

                Tables\Filters\SelectFilter::make('quran_teacher_id')
                    ->label('المعلم')
                    ->options(function () {
                        $teacherIds = static::getAssignedQuranTeacherIds();

                        return \App\Models\User::whereIn('id', $teacherIds)
                            ->get()
                            ->mapWithKeys(fn ($user) => [$user->id => $user->full_name ?? $user->name ?? $user->email]);
                    })
                    ->searchable()
                    ->preload(),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                Tables\Actions\ActionGroup::make([
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
                            'enrollment_status' => $record->status ? CircleEnrollmentStatus::CLOSED : CircleEnrollmentStatus::OPEN,
                        ])),
                    Tables\Actions\Action::make('view_sessions')
                        ->label('الجلسات')
                        ->icon('heroicon-o-calendar-days')
                        ->url(fn (QuranCircle $record): string => MonitoredAllSessionsResource::getUrl('index', [
                            'activeTab' => 'quran',
                            'tableFilters[circle_id][value]' => $record->id,
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
            ]);
    }

    /**
     * Only show navigation if supervisor has assigned Quran teachers.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::hasAssignedQuranTeachers();
    }

    /**
     * Override query to filter by assigned Quran teacher IDs.
     * Only show GROUP circles (not individual).
     * Eager load relationships to prevent N+1 queries.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['quranTeacher', 'academy'])
            ->withCount('students');

        // Filter by assigned Quran teacher IDs
        $teacherIds = static::getAssignedQuranTeacherIds();

        if (! empty($teacherIds)) {
            $query->whereIn('quran_teacher_id', $teacherIds);
        } else {
            // No teachers assigned - return empty result
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonitoredGroupCircles::route('/'),
            'view' => Pages\ViewMonitoredGroupCircle::route('/{record}'),
            'edit' => Pages\EditMonitoredGroupCircle::route('/{record}/edit'),
        ];
    }

    /**
     * Supervisors can edit any circle shown in their filtered list.
     * The query already filters to only show circles for assigned teachers.
     */
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true;
    }
}
