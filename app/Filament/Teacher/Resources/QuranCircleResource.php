<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\QuranCircleResource\Pages;
use App\Models\QuranCircle;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;

class QuranCircleResource extends Resource
{
    protected static ?string $model = QuranCircle::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'حلقاتي';

    protected static ?string $modelLabel = 'حلقة قرآن';

    protected static ?string $pluralModelLabel = 'حلقات القرآن';

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 3;

    // Scope to only the current teacher's circles
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        
        if (!$user->isQuranTeacher() || !$user->quranTeacherProfile) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return no results
        }

        return parent::getEloquentQuery()
            ->where('quran_teacher_id', $user->quranTeacherProfile->id)
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
                                TextInput::make('name')
                                    ->label('اسم الحلقة')
                                    ->required()
                                    ->maxLength(255),
                                    
                                TextInput::make('circle_code')
                                    ->label('رمز الحلقة')
                                    ->disabled()
                                    ->dehydrated(false),
                                    
                                Select::make('age_group')
                                    ->label('الفئة العمرية')
                                    ->options([
                                        'children' => 'أطفال (5-12 سنة)',
                                        'teenagers' => 'مراهقون (13-17 سنة)',
                                        'adults' => 'بالغون (18+ سنة)',
                                        'mixed' => 'مختلطة',
                                    ])
                                    ->required(),
                                    
                                Select::make('level')
                                    ->label('مستوى الحفظ')
                                    ->options([
                                        'beginner' => 'مبتدئ',
                                        'basic' => 'أساسي',
                                        'intermediate' => 'متوسط',
                                        'advanced' => 'متقدم',
                                        'hafez' => 'حافظ',
                                    ])
                                    ->required(),
                            ]),
                            
                        Textarea::make('description')
                            ->label('وصف الحلقة')
                            ->rows(3)
                            ->maxLength(500),
                            
                        TagsInput::make('goals')
                            ->label('أهداف الحلقة')
                            ->placeholder('أضف هدف جديد'),
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
                                    
                                TextInput::make('current_students')
                                    ->label('عدد الطلاب الحالي')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false),
                                    
                                TextInput::make('session_duration_minutes')
                                    ->label('مدة الجلسة (بالدقائق)')
                                    ->numeric()
                                    ->default(60)
                                    ->required(),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                TimePicker::make('start_time')
                                    ->label('وقت البداية')
                                    ->native(false)
                                    ->format('H:i')
                                    ->required(),
                                    
                                TimePicker::make('end_time')
                                    ->label('وقت النهاية')
                                    ->native(false)
                                    ->format('H:i')
                                    ->required(),
                            ]),
                            
                        Select::make('schedule_days')
                            ->label('أيام الانعقاد')
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
                                    ->maxValue(30)
                                    ->default(15)
                                    ->required()
                                    ->suffix('دقيقة'),
                            ]),
                    ]),

                Section::make('المحتوى التعليمي')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('current_surah')
                                    ->label('السورة الحالية')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(114),
                                    
                                TextInput::make('target_surah')
                                    ->label('السورة المستهدفة')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(114),
                            ]),
                            
                        Textarea::make('curriculum_notes')
                            ->label('ملاحظات المنهج')
                            ->rows(3),
                            
                        TagsInput::make('learning_methods')
                            ->label('طرق التعليم المستخدمة')
                            ->placeholder('أضف طريقة تعليم')
                            ->suggestions([
                                'التلقين',
                                'التكرار',
                                'التحفيز',
                                'المراجعة',
                                'التسميع',
                            ]),
                    ]),

                Section::make('الحالة والإعدادات')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->label('حالة الحلقة')
                                    ->options([
                                        'active' => 'نشطة',
                                        'paused' => 'متوقفة مؤقتاً',
                                        'cancelled' => 'ملغية',
                                        'completed' => 'مكتملة',
                                    ])
                                    ->default('active')
                                    ->required(),
                                    
                                Toggle::make('accepting_registrations')
                                    ->label('قبول تسجيلات جديدة')
                                    ->default(true),
                            ]),
                            
                        DateTimePicker::make('start_date')
                            ->label('تاريخ بداية الحلقة')
                            ->native(false)
                            ->required(),
                            
                        DateTimePicker::make('end_date')
                            ->label('تاريخ انتهاء الحلقة')
                            ->native(false),
                            
                        Textarea::make('teacher_notes')
                            ->label('ملاحظات المعلم')
                            ->rows(3),
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
                    
                TextColumn::make('name')
                    ->label('اسم الحلقة')
                    ->searchable()
                    ->limit(30),
                    
                BadgeColumn::make('age_group')
                    ->label('الفئة العمرية')
                    ->colors([
                        'primary' => 'children',
                        'success' => 'teenagers',
                        'warning' => 'adults',
                        'info' => 'mixed',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'children' => 'أطفال',
                        'teenagers' => 'مراهقون',
                        'adults' => 'بالغون',
                        'mixed' => 'مختلطة',
                        null => 'غير محدد',
                        default => $state,
                    }),
                    
                BadgeColumn::make('level')
                    ->label('المستوى')
                    ->colors([
                        'gray' => 'beginner',
                        'info' => 'basic',
                        'warning' => 'intermediate',
                        'success' => 'advanced',
                        'primary' => 'hafez',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'beginner' => 'مبتدئ',
                        'basic' => 'أساسي',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                        'hafez' => 'حافظ',
                        default => $state,
                    }),
                    
                TextColumn::make('current_students')
                    ->label('الطلاب الحاليون')
                    ->formatStateUsing(fn ($record) => $record->current_students . ' / ' . $record->max_students)
                    ->color(fn ($record) => $record->current_students >= $record->max_students ? 'danger' : 'primary'),
                    
                TextColumn::make('start_time')
                    ->label('وقت البداية')
                    ->time('H:i')
                    ->sortable(),
                    
                TextColumn::make('end_time')
                    ->label('وقت النهاية')
                    ->time('H:i')
                    ->sortable(),
                    
                TextColumn::make('schedule_days')
                    ->label('أيام الانعقاد')
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state)) return '';
                        $days = [
                            'sunday' => 'أحد',
                            'monday' => 'اثنين',
                            'tuesday' => 'ثلاثاء',
                            'wednesday' => 'أربعاء',
                            'thursday' => 'خميس',
                            'friday' => 'جمعة',
                            'saturday' => 'سبت',
                        ];
                        return implode(', ', array_map(fn($day) => $days[$day] ?? $day, $state));
                    }),
                    
                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'paused',
                        'danger' => 'cancelled',
                        'info' => 'completed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'نشطة',
                        'paused' => 'متوقفة',
                        'cancelled' => 'ملغية',
                        'completed' => 'مكتملة',
                        default => $state,
                    }),
                    
                BadgeColumn::make('accepting_registrations')
                    ->label('التسجيل')
                    ->formatStateUsing(fn ($state): string => $state ? 'مفتوح' : 'مغلق')
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ]),
                    
                TextColumn::make('start_date')
                    ->label('تاريخ البداية')
                    ->date('Y-m-d')
                    ->sortable(),
                    
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
                        'teenagers' => 'مراهقون',
                        'adults' => 'بالغون',
                        'mixed' => 'مختلطة',
                    ]),
                    
                SelectFilter::make('level')
                    ->label('المستوى')
                    ->options([
                        'beginner' => 'مبتدئ',
                        'basic' => 'أساسي',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                        'hafez' => 'حافظ',
                    ]),
                    
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'نشطة',
                        'paused' => 'متوقفة',
                        'cancelled' => 'ملغية',
                        'completed' => 'مكتملة',
                    ]),
                    
                Filter::make('accepting_registrations')
                    ->label('تقبل تسجيلات جديدة')
                    ->query(fn (Builder $query): Builder => $query->where('accepting_registrations', true)),
                    
                Filter::make('full_capacity')
                    ->label('مكتملة العدد')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('current_students', '>=', 'max_students')),
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
                ])
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