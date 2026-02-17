<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceStatus;
use App\Enums\SessionDuration;
use App\Filament\Resources\MeetingAttendanceResource\Pages;
use App\Models\MeetingAttendance;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MeetingAttendanceResource extends BaseResource
{
    protected static ?string $model = MeetingAttendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'سجل الحضور';

    protected static ?string $modelLabel = 'سجل حضور';

    protected static ?string $pluralModelLabel = 'سجل الحضور';

    protected static ?string $navigationGroup = 'التقارير والحضور';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

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
                            ->options(__('enums.attendance_user_type'))
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
                            ->options(__('enums.session_type'))
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
                    ->formatStateUsing(fn (string $state): string => __("enums.attendance_user_type.{$state}") ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'student' => 'info',
                        'teacher' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('session_type')
                    ->label('نوع الجلسة')
                    ->badge()
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return '-';
                        }
                        $key = "enums.session_type.{$state}";
                        $translated = __($key);

                        return $translated !== $key ? $translated : $state;
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'quran', 'individual' => 'primary',
                        'academic' => 'success',
                        'interactive' => 'warning',
                        'group' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->formatStateUsing(fn (string $state): string => $state.'%')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_duration_minutes')
                    ->label('وقت الحضور (دقيقة)')
                    ->numeric()
                    ->sortable()
                    ->suffix(' د')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_calculated')
                    ->label('محسوب تلقائياً')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('attendance_status')
                    ->label('حالة الحضور')
                    ->options(AttendanceStatus::options()),
                Tables\Filters\SelectFilter::make('user_type')
                    ->label('نوع المستخدم')
                    ->options(__('enums.attendance_user_type')),
                Tables\Filters\Filter::make('session_source')
                    ->form([
                        Forms\Components\Select::make('session_type')
                            ->label('نوع الجلسة')
                            ->options(__('enums.session_type'))
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('source_id', null)),
                        Forms\Components\Select::make('source_id')
                            ->label(fn (Forms\Get $get): string => match ($get('session_type')) {
                                'quran' => 'الحلقة',
                                'academic' => 'الدرس',
                                'interactive' => 'الدورة',
                                default => 'الحلقة / الدورة',
                            })
                            ->searchable()
                            ->preload()
                            ->options(function (Forms\Get $get): array {
                                return match ($get('session_type')) {
                                    'quran' => static::getQuranSourceOptions(),
                                    'academic' => \App\Models\AcademicIndividualLesson::query()
                                        ->pluck('name', 'id')
                                        ->toArray(),
                                    'interactive' => \App\Models\InteractiveCourse::query()
                                        ->pluck('title', 'id')
                                        ->toArray(),
                                    default => [],
                                };
                            })
                            ->visible(fn (Forms\Get $get): bool => filled($get('session_type'))),
                    ])
                    ->columns(2)
                    ->columnSpan(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['session_type'] ?? null, fn (Builder $q, string $type) => $q->where('session_type', $type))
                            ->when($data['source_id'] ?? null, function (Builder $q) use ($data) {
                                $sourceId = $data['source_id'];
                                $sessionType = $data['session_type'];

                                if ($sessionType === 'quran') {
                                    $q->whereIn('session_id', function ($sub) use ($sourceId) {
                                        $sub->select('id')->from('quran_sessions');
                                        if (str_starts_with($sourceId, 'circle:')) {
                                            $sub->where('circle_id', (int) str_replace('circle:', '', $sourceId));
                                        } else {
                                            $sub->where('individual_circle_id', (int) str_replace('individual:', '', $sourceId));
                                        }
                                    });
                                } elseif ($sessionType === 'academic') {
                                    $q->whereIn('session_id', function ($sub) use ($sourceId) {
                                        $sub->select('id')->from('academic_sessions')
                                            ->where('academic_individual_lesson_id', (int) $sourceId);
                                    });
                                } elseif ($sessionType === 'interactive') {
                                    $q->whereIn('session_id', function ($sub) use ($sourceId) {
                                        $sub->select('id')->from('interactive_course_sessions')
                                            ->where('course_id', (int) $sourceId);
                                    });
                                }

                                return $q;
                            });
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['session_type'] ?? null) {
                            $indicators['session_type'] = 'نوع الجلسة: '.__("enums.session_type.{$data['session_type']}");
                        }
                        if ($data['source_id'] ?? null) {
                            $indicators['source_id'] = match ($data['session_type'] ?? '') {
                                'quran' => 'الحلقة محددة',
                                'academic' => 'الدرس محدد',
                                'interactive' => 'الدورة محددة',
                                default => 'المصدر محدد',
                            };
                        }

                        return $indicators;
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
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

    private static function getQuranSourceOptions(): array
    {
        $options = [];

        $circles = \App\Models\QuranCircle::query()->pluck('name', 'id');
        foreach ($circles as $id => $name) {
            $options["circle:{$id}"] = 'حلقة جماعية: '.($name ?: "#{$id}");
        }

        $individuals = \App\Models\QuranIndividualCircle::query()->pluck('name', 'id');
        foreach ($individuals as $id => $name) {
            $options["individual:{$id}"] = 'حلقة فردية: '.($name ?: "#{$id}");
        }

        return $options;
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
            'view' => Pages\ViewMeetingAttendance::route('/{record}'),
            'edit' => Pages\EditMeetingAttendance::route('/{record}/edit'),
        ];
    }
}
