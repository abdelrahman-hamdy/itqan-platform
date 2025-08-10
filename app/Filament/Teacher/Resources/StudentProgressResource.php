<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\StudentProgressResource\Pages;
use App\Filament\Teacher\Resources\QuranSessionResource;
use App\Models\QuranSubscription;
use App\Models\User;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;

class StudentProgressResource extends Resource
{
    protected static ?string $model = QuranSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'تقدم الطلاب';

    protected static ?string $modelLabel = 'تقدم طالب';

    protected static ?string $pluralModelLabel = 'تقدم الطلاب';

    protected static ?string $navigationGroup = 'ملفي الشخصي';

    protected static ?int $navigationSort = 2;

    // Scope to only the current teacher's students
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        
        if (!$user->isQuranTeacher() || !$user->quranTeacherProfile) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return no results
        }

        return parent::getEloquentQuery()
            ->where('quran_teacher_id', $user->quranTeacherProfile->id)
            ->where('academy_id', $user->academy_id)
            ->where('subscription_status', 'active');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات الطالب')
                    ->schema([
                        TextInput::make('student.name')
                            ->label('اسم الطالب')
                            ->disabled(),
                            
                        TextInput::make('subscription_code')
                            ->label('رمز الاشتراك')
                            ->disabled(),
                            
                        TextInput::make('package.name_ar')
                            ->label('الباقة')
                            ->disabled(),
                    ])->columns(3),

                Section::make('التقدم في الحفظ')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('current_surah')
                                    ->label('السورة الحالية')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(114)
                                    ->required(),
                                
                                TextInput::make('current_verse')
                                    ->label('الآية الحالية')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required(),
                                
                                TextInput::make('verses_memorized')
                                    ->label('إجمالي الآيات المحفوظة')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                Select::make('memorization_level')
                                    ->label('مستوى الحفظ')
                                    ->options([
                                        'beginner' => 'مبتدئ',
                                        'intermediate' => 'متوسط',
                                        'advanced' => 'متقدم',
                                        'hafez' => 'حافظ',
                                    ])
                                    ->required(),
                                
                                TextInput::make('progress_percentage')
                                    ->label('نسبة التقدم')
                                    ->suffix('%')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required(),
                            ]),
                    ]),

                Section::make('الأداء والتقييم')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('rating')
                                    ->label('التقييم العام')
                                    ->options([
                                        1 => '1 - ضعيف',
                                        2 => '2 - مقبول',
                                        3 => '3 - جيد',
                                        4 => '4 - جيد جداً',
                                        5 => '5 - ممتاز',
                                    ]),
                                
                                DateTimePicker::make('last_session_at')
                                    ->label('آخر جلسة')
                                    ->disabled(),
                            ]),
                            
                        Textarea::make('notes')
                            ->label('ملاحظات المعلم حول التقدم')
                            ->rows(4),
                            
                        Textarea::make('review_text')
                            ->label('تقييم مفصل')
                            ->rows(3),
                    ]),

                Section::make('الجلسات والحضور')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('total_sessions')
                                    ->label('إجمالي الجلسات')
                                    ->numeric()
                                    ->disabled(),
                                
                                TextInput::make('sessions_used')
                                    ->label('الجلسات المستخدمة')
                                    ->numeric()
                                    ->disabled(),
                                
                                TextInput::make('sessions_remaining')
                                    ->label('الجلسات المتبقية')
                                    ->numeric()
                                    ->disabled(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.name')
                    ->label('اسم الطالب')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                    
                TextColumn::make('subscription_code')
                    ->label('رمز الاشتراك')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('progress_percentage')
                    ->label('التقدم')
                    ->formatStateUsing(function ($state) {
                        $percentage = $state ?? 0;
                        return $percentage . '%';
                    })
                    ->badge()
                    ->color(function ($record) {
                        $percentage = $record->progress_percentage ?? 0;
                        return $percentage >= 75 ? 'success' : 
                               ($percentage >= 50 ? 'warning' : 'danger');
                    }),
                    
                TextColumn::make('current_surah')
                    ->label('السورة الحالية')
                    ->sortable(),
                    
                TextColumn::make('current_verse')
                    ->label('الآية الحالية')
                    ->sortable(),
                    
                TextColumn::make('verses_memorized')
                    ->label('الآيات المحفوظة')
                    ->sortable()
                    ->color('success'),
                    
                BadgeColumn::make('memorization_level')
                    ->label('مستوى الحفظ')
                    ->colors([
                        'gray' => 'beginner',
                        'info' => 'intermediate',
                        'warning' => 'advanced',
                        'success' => 'hafez',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'beginner' => 'مبتدئ',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                        'hafez' => 'حافظ',
                        null => 'غير محدد',
                        default => $state,
                    }),
                    
                TextColumn::make('sessions_remaining')
                    ->label('الجلسات المتبقية')
                    ->sortable()
                    ->color(fn ($record) => $record->sessions_remaining <= 3 ? 'danger' : 'primary'),
                    
                BadgeColumn::make('rating')
                    ->label('التقييم')
                    ->colors([
                        'danger' => 1,
                        'warning' => 2,
                        'info' => 3,
                        'success' => [4, 5],
                    ])
                    ->formatStateUsing(fn (?int $state): string => $state ? "{$state}/5" : 'غير مقيم'),
                    
                TextColumn::make('last_session_at')
                    ->label('آخر جلسة')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->since(),
                    
                TextColumn::make('created_at')
                    ->label('تاريخ بداية الاشتراك')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('memorization_level')
                    ->label('مستوى الحفظ')
                    ->options([
                        'beginner' => 'مبتدئ',
                        'intermediate' => 'متوسط',
                        'advanced' => 'متقدم',
                        'hafez' => 'حافظ',
                    ]),
                    
                SelectFilter::make('rating')
                    ->label('التقييم')
                    ->options([
                        1 => '1 - ضعيف',
                        2 => '2 - مقبول',
                        3 => '3 - جيد',
                        4 => '4 - جيد جداً',
                        5 => '5 - ممتاز',
                    ]),
                    
                Filter::make('high_progress')
                    ->label('تقدم عالي (75%+)')
                    ->query(fn (Builder $query): Builder => $query->where('progress_percentage', '>=', 75)),
                    
                Filter::make('low_sessions')
                    ->label('جلسات قليلة (أقل من 5)')
                    ->query(fn (Builder $query): Builder => $query->where('sessions_remaining', '<', 5)),
                    
                Filter::make('recent_activity')
                    ->label('نشاط حديث (آخر 7 أيام)')
                    ->query(fn (Builder $query): Builder => $query->where('last_session_at', '>=', now()->subDays(7))),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض التفاصيل'),
                    Tables\Actions\EditAction::make()
                        ->label('تحديث التقدم'),
                    Tables\Actions\Action::make('add_session')
                        ->label('إضافة جلسة')
                        ->icon('heroicon-o-plus')
                        ->color('success')
                        ->url(fn (QuranSubscription $record): string => 
                            QuranSessionResource::getUrl('create', [
                                'tenant' => filament()->getTenant(),
                                'quran_subscription_id' => $record->id,
                                'student_id' => $record->student_id,
                            ])
                        ),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ])
            ->defaultSort('progress_percentage', 'desc');
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
            'index' => Pages\ListStudentProgress::route('/'),
            'view' => Pages\ViewStudentProgress::route('/{record}'),
            'edit' => Pages\EditStudentProgress::route('/{record}/edit'),
        ];
    }
}