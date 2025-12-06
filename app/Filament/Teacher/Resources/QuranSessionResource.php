<?php

namespace App\Filament\Teacher\Resources;

use App\Enums\QuranSurah;
use App\Filament\Teacher\Resources\QuranSessionResource\Pages;
use App\Models\QuranSession;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
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

        if (! $user->isQuranTeacher() || ! $user->quranTeacherProfile) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return no results
        }

        $userId = $user->id;

        return parent::getEloquentQuery()
            ->where('quran_teacher_id', $userId)
            ->where('academy_id', $user->academy_id)
            ->with(['student', 'subscription', 'circle', 'individualCircle', 'sessionHomework']);
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
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('نوع الجلسة يُحدد تلقائياً'),

                                    DateTimePicker::make('scheduled_at')
                                    ->label('موعد الجلسة')
                                    ->required()
                                    ->native(false)
                                    ->seconds(false)
                                    ->timezone(fn () => auth()->user()?->academy?->timezone?->value ?? 'UTC')
                                    ->displayFormat('Y-m-d H:i'),

                                TextInput::make('duration_minutes')
                                    ->label('مدة الجلسة (بالدقائق)')
                                    ->numeric()
                                    ->default(60)
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('المدة محددة بناءً على باقة القرآن'),

                                Select::make('status')
                                    ->label('حالة الجلسة')
                                    ->options([
                                        'scheduled' => 'مجدولة',
                                        'ongoing' => 'جارية',
                                        'completed' => 'مكتملة',
                                        'cancelled' => 'ملغية',
                                        'absent' => 'غياب الطالب',
                                    ])
                                    ->default('scheduled')
                                    ->required(),
                            ]),
                    ]),

                Section::make('تفاصيل الجلسة')
                    ->schema([
                        Textarea::make('description')
                            ->label('وصف الجلسة')
                            ->rows(3),

                        Textarea::make('lesson_content')
                            ->label('محتوى الدرس')
                            ->rows(4),
                    ]),

                Section::make('الواجب المنزلي')
                    ->schema([
                        // New Memorization Section
                        Toggle::make('sessionHomework.has_new_memorization')
                            ->label('حفظ جديد')
                            ->live()
                            ->default(false),

                        Grid::make(2)
                            ->schema([
                                Select::make('sessionHomework.new_memorization_surah')
                                    ->label('سورة الحفظ الجديد')
                                    ->options(QuranSurah::getAllSurahs())
                                    ->searchable()
                                    ->visible(fn ($get) => $get('sessionHomework.has_new_memorization')),

                                TextInput::make('sessionHomework.new_memorization_pages')
                                    ->label('عدد الأوجه')
                                    ->numeric()
                                    ->step(0.5)
                                    ->minValue(0.5)
                                    ->maxValue(10)
                                    ->suffix('وجه')
                                    ->visible(fn ($get) => $get('sessionHomework.has_new_memorization')),

                            ])
                            ->visible(fn ($get) => $get('sessionHomework.has_new_memorization')),

                        // Review Section
                        Toggle::make('sessionHomework.has_review')
                            ->label('مراجعة')
                            ->live()
                            ->default(false),

                        Grid::make(2)
                            ->schema([
                                Select::make('sessionHomework.review_surah')
                                    ->label('سورة المراجعة')
                                    ->options(QuranSurah::getAllSurahs())
                                    ->searchable()
                                    ->visible(fn ($get) => $get('sessionHomework.has_review')),

                                TextInput::make('sessionHomework.review_pages')
                                    ->label('عدد أوجه المراجعة')
                                    ->numeric()
                                    ->step(0.5)
                                    ->minValue(0.5)
                                    ->maxValue(20)
                                    ->suffix('وجه')
                                    ->visible(fn ($get) => $get('sessionHomework.has_review')),

                            ])
                            ->visible(fn ($get) => $get('sessionHomework.has_review')),

                        // Comprehensive Review Section
                        Toggle::make('sessionHomework.has_comprehensive_review')
                            ->label('مراجعة شاملة')
                            ->live()
                            ->default(false),

                        CheckboxList::make('sessionHomework.comprehensive_review_surahs')
                            ->label('سور المراجعة الشاملة')
                            ->options(QuranSurah::getAllSurahs())
                            ->searchable()
                            ->columns(3)
                            ->visible(fn ($get) => $get('sessionHomework.has_comprehensive_review')),

                        Textarea::make('sessionHomework.additional_instructions')
                            ->label('تعليمات إضافية')
                            ->rows(3)
                            ->placeholder('أي تعليمات أو ملاحظات إضافية للطلاب'),
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
                    ->timezone(fn ($record) => $record->academy->timezone->value)
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
                    ->formatStateUsing(fn ($state): string => match ($state instanceof \App\Enums\SessionStatus ? $state->value : $state) {
                        'unscheduled' => 'غير مجدولة',
                        'scheduled' => 'مجدولة',
                        'ready' => 'جاهزة للبدء',
                        'ongoing' => 'جارية',
                        'completed' => 'مكتملة',
                        'cancelled' => 'ملغية',
                        'absent' => 'غياب الطالب',
                        default => $state,
                    }),

                BadgeColumn::make('attendance_status')
                    ->label('الحضور')
                    ->colors([
                        'success' => 'attended',
                        'danger' => 'absent',
                        'warning' => 'late',
                        'info' => 'leaved',
                        'gray' => 'pending',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'attended' => 'حاضر',
                        'absent' => 'غائب',
                        'late' => 'متأخر',
                        'leaved' => 'غادر مبكراً',
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
                        'attended' => 'حاضر',
                        'absent' => 'غائب',
                        'late' => 'متأخر',
                        'leaved' => 'غادر مبكراً',
                        'pending' => 'في الانتظار',
                    ]),

                Filter::make('today')
                    ->label('جلسات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Filter::make('this_week')
                    ->label('جلسات هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek(),
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
                ]),
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
