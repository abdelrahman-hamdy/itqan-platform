<?php

namespace App\Filament\Teacher\Resources;

use App\Enums\WeekDays;
use App\Filament\Teacher\Resources\QuranCircleResource\Pages;
use App\Models\QuranCircle;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class QuranCircleResource extends Resource
{
    protected static ?string $model = QuranCircle::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'حلقات القرآن الجماعية';

    protected static ?string $modelLabel = 'حلقة قرآن';

    protected static ?string $pluralModelLabel = 'حلقات القرآن';

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 3;

    // Scope to only the current teacher's circles
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if (! $user->isQuranTeacher() || ! $user->quranTeacherProfile) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return no results
        }

        return parent::getEloquentQuery()
            ->where('quran_teacher_id', $user->id)
            ->where('academy_id', $user->academy_id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                                    ->options([
                                        'beginner' => 'مبتدئ',
                                        'elementary' => 'أساسي',
                                        'intermediate' => 'متوسط',
                                        'advanced' => 'متقدم',
                                        'expert' => 'خبير',
                                    ])
                                    ->default('beginner')
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
                        Grid::make(3)
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
                                    ->options([
                                        30 => '30 دقيقة',
                                        45 => '45 دقيقة',
                                        60 => '60 دقيقة',
                                    ])
                                    ->default(60)
                                    ->required(),
                            ]),

                        Select::make('schedule_days')
                            ->label('أيام الانعقاد')
                            ->options(WeekDays::options())
                            ->multiple()
                            ->native(false)
                            ->helperText('أيام انعقاد الحلقة - للمعلومات العامة')
                            ->columnSpanFull(),

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

                Section::make('الحالة والإعدادات')
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
                                    ->disabled()
                                    ->helperText('ملاحظات من الإدارة (للقراءة فقط)')
                                    ->columnSpanFull(),
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
                    ->sortable(),

                TextColumn::make('name_ar')
                    ->label('اسم الحلقة')
                    ->searchable()
                    ->limit(30)
                    ->placeholder('غير محدد'),

                BadgeColumn::make('age_group')
                    ->label('الفئة العمرية')
                    ->colors([
                        'primary' => 'children',
                        'success' => 'youth',
                        'warning' => 'adults',
                        'info' => 'all_ages',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'children' => 'أطفال',
                        'youth' => 'شباب',
                        'adults' => 'بالغون',
                        'all_ages' => 'كل الفئات',
                        null => 'غير محدد',
                        default => $state,
                    }),

                BadgeColumn::make('gender_type')
                    ->label('النوع')
                    ->colors([
                        'primary' => 'male',
                        'success' => 'female',
                        'info' => 'mixed',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'male' => 'رجال',
                        'female' => 'نساء',
                        'mixed' => 'مختلط',
                        null => 'غير محدد',
                        default => $state,
                    }),

                BadgeColumn::make('specialization')
                    ->label('التخصص')
                    ->colors([
                        'primary' => 'memorization',
                        'success' => 'recitation',
                        'warning' => 'interpretation',
                        'info' => 'arabic_language',
                        'secondary' => 'complete',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'memorization' => 'حفظ',
                        'recitation' => 'تلاوة',
                        'interpretation' => 'تفسير',
                        'arabic_language' => 'لغة عربية',
                        'complete' => 'شامل',
                        null => 'غير محدد',
                        default => $state,
                    }),

                BadgeColumn::make('memorization_level')
                    ->label('المستوى')
                    ->colors([
                        'gray' => 'beginner',
                        'info' => 'elementary',
                        'warning' => 'intermediate',
                        'success' => 'advanced',
                        'primary' => 'expert',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'beginner' => 'مبتدئ',
                        'elementary' => 'أساسي',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                        'expert' => 'خبير',
                        null => 'غير محدد',
                        default => $state,
                    }),

                TextColumn::make('current_students')
                    ->label('الطلاب الحاليون')
                    ->formatStateUsing(fn ($record) => $record->students()->count().' / '.$record->max_students)
                    ->color(fn ($record) => $record->students()->count() >= $record->max_students ? 'danger' : 'primary'),

                TextColumn::make('schedule_time')
                    ->label('الساعة')
                    ->time('H:i')
                    ->sortable()
                    ->placeholder('غير محدد'),

                TextColumn::make('schedule_days')
                    ->label('أيام الانعقاد')
                    ->formatStateUsing(function ($state) {
                        if (! is_array($state) || empty($state)) {
                            return 'غير محدد';
                        }

                        return WeekDays::getDisplayNames($state);
                    }),

                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ])
                    ->formatStateUsing(fn (bool $state): string => $state ? 'نشطة' : 'غير نشطة'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('age_group')
                    ->label('الفئة العمرية')
                    ->options([
                        'children' => 'أطفال',
                        'youth' => 'شباب',
                        'adults' => 'بالغون',
                        'all_ages' => 'كل الفئات',
                    ]),

                SelectFilter::make('memorization_level')
                    ->label('المستوى')
                    ->options([
                        'beginner' => 'مبتدئ',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                    ]),

                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        '1' => 'نشطة',
                        '0' => 'غير نشطة',
                    ]),

                SelectFilter::make('enrollment_status')
                    ->label('حالة التسجيل')
                    ->options([
                        'open' => 'مفتوح',
                        'closed' => 'مغلق',
                        'full' => 'ممتلئ',
                    ]),

                Filter::make('full_capacity')
                    ->label('مكتملة العدد')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('(SELECT COUNT(*) FROM quran_circle_students WHERE circle_id = quran_circles.id) >= max_students')
                    ),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\Action::make('manage_students')
                        ->label('إدارة الطلاب')
                        ->icon('heroicon-o-users')
                        ->color('info')
                        ->url(fn (QuranCircle $record): string => '#'), // Will implement later
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Teacher\Resources\QuranCircleResource\RelationManagers\SessionsRelationManager::class,
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

    public static function getBreadcrumb(): string
    {
        return static::$pluralModelLabel ?? 'حلقات القرآن';
    }
}
