<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\QuranSessionResource\Pages;
use App\Models\QuranSession;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Filament\Actions;

class QuranSessionResource extends Resource
{
    protected static ?string $model = QuranSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationLabel = 'جلساتي';

    protected static ?string $modelLabel = 'جلسة قرآن';

    protected static ?string $pluralModelLabel = 'جلسات القرآن';

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 1;

    // Scope to only the current teacher's sessions
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        
        if (!$user->isQuranTeacher() || !$user->quranTeacherProfile) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return no results
        }

        $teacherProfileId = $user->quranTeacherProfile->id;
        $userId = $user->id;

        return parent::getEloquentQuery()
            ->where(function($query) use ($teacherProfileId, $userId) {
                // Include both teacher profile ID (group sessions) and user ID (individual sessions)
                $query->where('quran_teacher_id', $teacherProfileId)
                      ->orWhere('quran_teacher_id', $userId);
            })
            ->where('academy_id', $user->academy_id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات الجلسة الأساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label('عنوان الجلسة')
                                    ->required()
                                    ->maxLength(255),
                                
                                Select::make('session_type')
                                    ->label('نوع الجلسة')
                                    ->options([
                                        'individual' => 'فردية',
                                        'group' => 'جماعية',
                                        'trial' => 'تجريبية',
                                        'makeup' => 'تعويضية',
                                    ])
                                    ->required(),
                                
                                DateTimePicker::make('scheduled_at')
                                    ->label('موعد الجلسة')
                                    ->required()
                                    ->native(false),
                                
                                TextInput::make('duration_minutes')
                                    ->label('مدة الجلسة (بالدقائق)')
                                    ->numeric()
                                    ->default(60)
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('المدة محددة بناءً على باقة القرآن المشترك بها'),
                            ]),
                            
                        Textarea::make('description')
                            ->label('وصف الجلسة')
                            ->rows(3),
                            
                        Textarea::make('lesson_objectives')
                            ->label('أهداف الدرس')
                            ->rows(3),
                            
                        Grid::make(3)
                            ->schema([
                                TextInput::make('monthly_session_number')
                                    ->label('رقم الجلسة الشهرية')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(31)
                                    ->helperText('رقم الجلسة ضمن الشهر (1، 2، 3...)')
                                    ->placeholder('سيتم تعيينه تلقائياً'),
                                    
                                DatePicker::make('session_month')
                                    ->label('شهر الجلسة')
                                    ->displayFormat('Y-m')
                                    ->format('Y-m-01')
                                    ->helperText('الشهر الذي تنتمي إليه الجلسة')
                                    ->default(now()->format('Y-m-01')),
                                    
                                Toggle::make('counts_toward_subscription')
                                    ->label('تحتسب ضمن الاشتراك')
                                    ->helperText('هل تحتسب هذه الجلسة ضمن جلسات الاشتراك؟')
                                    ->default(true),
                            ]),
                    ]),

                Section::make('معلومات الاجتماع')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('location_type')
                                    ->label('نوع الموقع')
                                    ->options([
                                        'online' => 'عبر الإنترنت',
                                        'in_person' => 'حضورياً',
                                    ])
                                    ->default('online')
                                    ->live(),
                                
                                TextInput::make('meeting_link')
                                    ->label('رابط الاجتماع')
                                    ->url()
                                    ->visible(fn ($get) => $get('location_type') === 'online'),
                                    
                                TextInput::make('meeting_id')
                                    ->label('معرف الاجتماع')
                                    ->visible(fn ($get) => $get('location_type') === 'online'),
                                    
                                TextInput::make('meeting_password')
                                    ->label('كلمة مرور الاجتماع')
                                    ->visible(fn ($get) => $get('location_type') === 'online'),
                            ]),
                            
                        Textarea::make('location_details')
                            ->label('تفاصيل الموقع')
                            ->visible(fn ($get) => $get('location_type') === 'in_person')
                            ->rows(2),
                            
                        Toggle::make('recording_enabled')
                            ->label('تسجيل الجلسة')
                            ->default(false),
                    ]),

                Section::make('محتوى الدرس')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('current_surah')
                                    ->label('رقم السورة الحالية')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(114),
                                    
                                TextInput::make('verses_covered_start')
                                    ->label('من الآية')
                                    ->numeric()
                                    ->minValue(1),
                                    
                                TextInput::make('verses_covered_end')
                                    ->label('إلى الآية')
                                    ->numeric()
                                    ->minValue(1),
                            ]),
                            
                        Textarea::make('homework_details')
                            ->label('تفاصيل الواجب المنزلي')
                            ->rows(3),
                            
                        Textarea::make('next_session_plan')
                            ->label('خطة الجلسة القادمة')
                            ->rows(3),
                    ]),

                Section::make('التقييم والملاحظات')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('recitation_quality')
                                    ->label('جودة التلاوة (من 10)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(10)
                                    ->step(0.5),
                                    
                                TextInput::make('tajweed_accuracy')
                                    ->label('دقة التجويد (من 10)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(10)
                                    ->step(0.5),
                                    
                                TextInput::make('mistakes_count')
                                    ->label('عدد الأخطاء')
                                    ->numeric()
                                    ->minValue(0),
                                    
                                TextInput::make('verses_memorized_today')
                                    ->label('الآيات المحفوظة اليوم')
                                    ->numeric()
                                    ->minValue(0),
                            ]),
                            
                        Textarea::make('teacher_feedback')
                            ->label('ملاحظات المعلم')
                            ->rows(4),
                            
                        Textarea::make('areas_for_improvement')
                            ->label('مجالات التحسين')
                            ->rows(3),
                            
                        Textarea::make('session_notes')
                            ->label('ملاحظات عامة')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('session_code')
                    ->label('رمز الجلسة')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('title')
                    ->label('عنوان الجلسة')
                    ->searchable()
                    ->limit(30),
                    
                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),
                    
                BadgeColumn::make('session_type')
                    ->label('نوع الجلسة')
                    ->colors([
                        'primary' => 'individual',
                        'success' => 'group',
                        'warning' => 'trial',
                        'info' => 'makeup',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'individual' => 'فردية',
                        'group' => 'جماعية',
                        'trial' => 'تجريبية',
                        'makeup' => 'تعويضية',
                        default => $state,
                    }),
                    
                TextColumn::make('scheduled_at')
                    ->label('موعد الجلسة')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                    
                TextColumn::make('duration_minutes')
                    ->label('المدة')
                    ->suffix(' دقيقة')
                    ->sortable(),
                    
                TextColumn::make('monthly_session_number')
                    ->label('رقم الجلسة')
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('session_month')
                    ->label('الشهر')
                    ->date('Y-m')
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('counts_toward_subscription')
                    ->label('تحتسب ضمن الاشتراك')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'نعم' : 'لا')
                    ->toggleable(),
                    
                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'warning' => 'scheduled',
                        'info' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                        'gray' => 'no_show',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'scheduled' => 'مجدولة',
                        'in_progress' => 'جارية',
                        'completed' => 'مكتملة',
                        'cancelled' => 'ملغية',
                        'no_show' => 'غياب',
                        default => $state,
                    }),
                    
                BadgeColumn::make('attendance_status')
                    ->label('الحضور')
                    ->colors([
                        'success' => 'present',
                        'danger' => 'absent',
                        'warning' => 'late',
                        'gray' => 'pending',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'present' => 'حاضر',
                        'absent' => 'غائب',
                        'late' => 'متأخر',
                        'pending' => 'في الانتظار',
                        null => 'غير محدد',
                        default => $state,
                    }),
                    
                TextColumn::make('current_surah')
                    ->label('السورة')
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('session_type')
                    ->label('نوع الجلسة')
                    ->options([
                        'individual' => 'فردية',
                        'group' => 'جماعية',
                        'trial' => 'تجريبية',
                        'makeup' => 'تعويضية',
                    ]),
                    
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'scheduled' => 'مجدولة',
                        'in_progress' => 'جارية',
                        'completed' => 'مكتملة',
                        'cancelled' => 'ملغية',
                        'no_show' => 'غياب',
                    ]),
                    
                SelectFilter::make('attendance_status')
                    ->label('الحضور')
                    ->options([
                        'present' => 'حاضر',
                        'absent' => 'غائب',
                        'late' => 'متأخر',
                        'pending' => 'في الانتظار',
                    ]),
                    
                Filter::make('today')
                    ->label('جلسات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),
                    
                Filter::make('this_week')
                    ->label('جلسات هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ])),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\DeleteAction::make()
                        ->label('حذف')
                        ->after(function (QuranSession $record) {
                            // Update session counts for individual circles
                            if ($record->individualCircle) {
                                $record->individualCircle->updateSessionCounts();
                            }
                        }),
                    Tables\Actions\Action::make('start_session')
                        ->label('بدء الجلسة')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->visible(fn (QuranSession $record): bool => $record->status === 'scheduled')
                        ->action(function (QuranSession $record) {
                            $record->update([
                                'status' => 'in_progress',
                                'started_at' => now(),
                            ]);
                        }),
                    Tables\Actions\Action::make('complete_session')
                        ->label('إنهاء الجلسة')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn (QuranSession $record): bool => $record->status === 'in_progress')
                        ->action(function (QuranSession $record) {
                            $record->update([
                                'status' => 'completed',
                                'ended_at' => now(),
                                'actual_duration_minutes' => now()->diffInMinutes($record->started_at),
                            ]);
                        }),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function (\Illuminate\Support\Collection $records) {
                            // Update session counts for affected individual circles
                            $individualCircleIds = $records->pluck('individual_circle_id')->filter()->unique();
                            foreach ($individualCircleIds as $circleId) {
                                $circle = \App\Models\QuranIndividualCircle::find($circleId);
                                if ($circle) {
                                    $circle->updateSessionCounts();
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('scheduled_at', 'desc');
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
            'index' => Pages\ListQuranSessions::route('/'),
            'create' => Pages\CreateQuranSession::route('/create'),
            'view' => Pages\ViewQuranSession::route('/{record}'),
            'edit' => Pages\EditQuranSession::route('/{record}/edit'),
        ];
    }
}