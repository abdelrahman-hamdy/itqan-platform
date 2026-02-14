<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceStatus;
use App\Enums\SessionDuration;
use App\Enums\UserType;
use App\Filament\Resources\MeetingAttendanceResource\Pages;
use App\Models\MeetingAttendance;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MeetingAttendanceResource extends Resource
{
    protected static ?string $model = MeetingAttendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'سجل الحضور';

    protected static ?string $modelLabel = 'سجل حضور';

    protected static ?string $pluralModelLabel = 'سجل الحضور';

    protected static ?string $navigationGroup = 'التقارير والحضور';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['session', 'user']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الحضور')
                    ->schema([
                        Forms\Components\TextInput::make('session_id')
                            ->label('معرف الجلسة')
                            ->numeric()
                            ->required()
                            ->disabled(),
                        Forms\Components\Select::make('user_type')
                            ->label('نوع المستخدم')
                            ->options([
                                'student' => 'طالب',
                                'teacher' => 'معلم',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('user_id', null)),
                        Forms\Components\Select::make('user_id')
                            ->label('المستخدم')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(function (Forms\Get $get) {
                                $userType = $get('user_type');

                                if ($userType === 'teacher') {
                                    return \App\Models\User::where(function ($query) {
                                        $query->whereHas('quranTeacherProfile')
                                            ->orWhereHas('academicTeacherProfile');
                                    })
                                        ->get()
                                        ->mapWithKeys(fn ($user) => [
                                            $user->id => $user->display_name ?? $user->name ?? 'معلم #'.$user->id,
                                        ])
                                        ->toArray();
                                }

                                if ($userType === 'student') {
                                    return \App\Models\User::whereHas('studentProfile')
                                        ->get()
                                        ->mapWithKeys(fn ($user) => [
                                            $user->id => $user->display_name ?? $user->name ?? 'طالب #'.$user->id,
                                        ])
                                        ->toArray();
                                }

                                return [];
                            })
                            ->getOptionLabelUsing(fn ($value) => \App\Models\User::find($value)?->display_name
                                ?? \App\Models\User::find($value)?->name
                                ?? 'مستخدم #'.$value
                            )
                            ->disabled(fn (Forms\Get $get) => ! $get('user_type'))
                            ->helperText(fn (Forms\Get $get) => ! $get('user_type') ? 'اختر نوع المستخدم أولاً' : null),
                        Forms\Components\Select::make('session_type')
                            ->label('نوع الجلسة')
                            ->options([
                                'quran' => 'قرآن',
                                'academic' => 'أكاديمي',
                                'interactive' => 'تفاعلي',
                            ])
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('يتم تحديده تلقائياً من الجلسة'),
                    ])->columns(2),

                Forms\Components\Section::make('تفاصيل الحضور والوقت')
                    ->schema([
                        Forms\Components\DateTimePicker::make('first_join_time')
                            ->label('أول وقت دخول'),
                        Forms\Components\DateTimePicker::make('last_leave_time')
                            ->label('آخر وقت خروج'),
                        Forms\Components\TextInput::make('total_duration_minutes')
                            ->label('إجمالي المدة (دقيقة)')
                            ->numeric()
                            ->default(0)
                            ->suffix('دقيقة'),
                        Forms\Components\TextInput::make('join_count')
                            ->label('عدد مرات الدخول')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('leave_count')
                            ->label('عدد مرات الخروج')
                            ->numeric()
                            ->default(0),
                        Forms\Components\DateTimePicker::make('session_start_time')
                            ->label('وقت بدء الجلسة')
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                $duration = $get('session_duration_minutes');
                                if ($state && $duration) {
                                    $endTime = Carbon::parse($state)->addMinutes((int) $duration);
                                    $set('session_end_time', $endTime->format('Y-m-d H:i:s'));
                                }
                            }),
                        Forms\Components\Select::make('session_duration_minutes')
                            ->label('مدة الجلسة')
                            ->options(SessionDuration::options())
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                $startTime = $get('session_start_time');
                                if ($startTime && $state) {
                                    $endTime = Carbon::parse($startTime)->addMinutes((int) $state);
                                    $set('session_end_time', $endTime->format('Y-m-d H:i:s'));
                                }
                            }),
                        Forms\Components\DateTimePicker::make('session_end_time')
                            ->label('وقت انتهاء الجلسة')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('يتم حسابه تلقائياً من وقت البدء والمدة'),
                    ])->columns(3),

                Forms\Components\Section::make('حالة الحضور والحساب')
                    ->schema([
                        Forms\Components\Select::make('attendance_status')
                            ->label('حالة الحضور')
                            ->options(AttendanceStatus::options())
                            ->required(),
                        Forms\Components\TextInput::make('attendance_percentage')
                            ->label('نسبة الحضور')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(0),
                        Forms\Components\DateTimePicker::make('attendance_calculated_at')
                            ->label('تاريخ الحساب'),
                        Forms\Components\Toggle::make('is_calculated')
                            ->label('محسوب تلقائياً')
                            ->default(true),
                    ])->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('session.title')
                    ->label('الجلسة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->formatStateUsing(fn ($record) => $record->user?->display_name
                        ?? $record->user?->name
                        ?? 'مستخدم #'.$record->user_id
                    )
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'student' => 'طالب',
                        'teacher' => 'معلم',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'student' => 'info',
                        'teacher' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('session_type')
                    ->label('نوع الجلسة')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'quran' => 'قرآن',
                        'academic' => 'أكاديمي',
                        'interactive' => 'تفاعلي',
                        default => $state ?? '-',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('attendance_status')
                    ->label('الحضور')
                    ->badge()
                    ->color(fn (mixed $state): string => match (true) {
                        $state === AttendanceStatus::ATTENDED, $state === AttendanceStatus::ATTENDED->value => 'success',
                        $state === AttendanceStatus::LATE, $state === AttendanceStatus::LATE->value => 'warning',
                        $state === AttendanceStatus::LEFT, $state === AttendanceStatus::LEFT->value => 'info',
                        $state === AttendanceStatus::ABSENT, $state === AttendanceStatus::ABSENT->value => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(function (mixed $state): string {
                        if (! $state) {
                            return '-';
                        }
                        if ($state instanceof AttendanceStatus) {
                            return $state->label();
                        }
                        try {
                            return AttendanceStatus::from($state)->label();
                        } catch (\ValueError $e) {
                            return (string) $state;
                        }
                    }),
                Tables\Columns\TextColumn::make('attendance_percentage')
                    ->label('نسبة الحضور')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => $state.'%'),
                Tables\Columns\TextColumn::make('total_duration_minutes')
                    ->label('المدة (دقيقة)')
                    ->numeric()
                    ->sortable()
                    ->suffix(' د'),
                Tables\Columns\TextColumn::make('first_join_time')
                    ->label('أول دخول')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_leave_time')
                    ->label('آخر خروج')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('join_count')
                    ->label('مرات الدخول')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('leave_count')
                    ->label('مرات الخروج')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_calculated')
                    ->label('محسوب تلقائياً')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('attendance_calculated_at')
                    ->label('تاريخ الحساب')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('last_heartbeat_at')
                    ->label('آخر نبض')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('attendance_status')
                    ->label('حالة الحضور')
                    ->options(AttendanceStatus::options()),
                Tables\Filters\SelectFilter::make('user_type')
                    ->label('نوع المستخدم')
                    ->options([
                        'student' => 'طالب',
                        'teacher' => 'معلم',
                    ]),
                Tables\Filters\SelectFilter::make('session_type')
                    ->label('نوع الجلسة')
                    ->options([
                        'quran' => 'قرآن',
                        'academic' => 'أكاديمي',
                        'interactive' => 'تفاعلي',
                    ]),
                Tables\Filters\TernaryFilter::make('is_calculated')
                    ->label('محسوب تلقائياً'),
                Tables\Filters\Filter::make('teachers_only')
                    ->label('المعلمين فقط')
                    ->query(fn (Builder $query): Builder => $query->where('user_type', 'teacher')),
                Tables\Filters\Filter::make('students_only')
                    ->label('الطلاب فقط')
                    ->query(fn (Builder $query): Builder => $query->where('user_type', UserType::STUDENT->value)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListMeetingAttendances::route('/'),
            'create' => Pages\CreateMeetingAttendance::route('/create'),
            'view' => Pages\ViewMeetingAttendance::route('/{record}'),
            'edit' => Pages\EditMeetingAttendance::route('/{record}/edit'),
        ];
    }
}
