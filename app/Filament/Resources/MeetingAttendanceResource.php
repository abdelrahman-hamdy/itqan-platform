<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceStatus;
use App\Enums\SessionDuration;
use App\Filament\Resources\MeetingAttendanceResource\Pages\EditMeetingAttendance;
use App\Filament\Resources\MeetingAttendanceResource\Pages\ListMeetingAttendances;
use App\Filament\Resources\MeetingAttendanceResource\Pages\ViewMeetingAttendance;
use App\Models\AcademicIndividualLesson;
use App\Models\InteractiveCourse;
use App\Models\MeetingAttendance;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use ValueError;

class MeetingAttendanceResource extends BaseResource
{
    protected static ?string $model = MeetingAttendance::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'سجل الحضور';

    protected static ?string $modelLabel = 'سجل حضور';

    protected static ?string $pluralModelLabel = 'سجل الحضور';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير والحضور';

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الحضور')
                    ->schema([
                        TextInput::make('session_id')
                            ->label('معرف الجلسة')
                            ->numeric()
                            ->required()
                            ->disabled(),
                        Select::make('user_type')
                            ->label('نوع المستخدم')
                            ->options(__('enums.attendance_user_type'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('user_id', null)),
                        Select::make('user_id')
                            ->label('المستخدم')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(function (Get $get) {
                                $userType = $get('user_type');

                                if ($userType === 'teacher') {
                                    return User::where(function ($query) {
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
                                    return User::whereHas('studentProfile')
                                        ->get()
                                        ->mapWithKeys(fn ($user) => [
                                            $user->id => $user->display_name ?? $user->name ?? 'طالب #'.$user->id,
                                        ])
                                        ->toArray();
                                }

                                return [];
                            })
                            ->getOptionLabelUsing(fn ($value) => User::find($value)?->display_name
                                ?? User::find($value)?->name
                                ?? 'مستخدم #'.$value
                            )
                            ->disabled(fn (Get $get) => ! $get('user_type'))
                            ->helperText(fn (Get $get) => ! $get('user_type') ? 'اختر نوع المستخدم أولاً' : null),
                        Select::make('session_type')
                            ->label('نوع الجلسة')
                            ->options(__('enums.session_type'))
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('يتم تحديده تلقائياً من الجلسة'),
                    ])->columns(2),

                Section::make('تفاصيل الحضور والوقت')
                    ->schema([
                        DateTimePicker::make('first_join_time')
                            ->label('أول وقت دخول'),
                        DateTimePicker::make('last_leave_time')
                            ->label('آخر وقت خروج'),
                        TextInput::make('total_duration_minutes')
                            ->label('إجمالي المدة (دقيقة)')
                            ->numeric()
                            ->default(0)
                            ->suffix('دقيقة'),
                        TextInput::make('join_count')
                            ->label('عدد مرات الدخول')
                            ->numeric()
                            ->default(0),
                        TextInput::make('leave_count')
                            ->label('عدد مرات الخروج')
                            ->numeric()
                            ->default(0),
                        DateTimePicker::make('session_start_time')
                            ->label('وقت بدء الجلسة')
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                $duration = $get('session_duration_minutes');
                                if ($state && $duration) {
                                    $endTime = Carbon::parse($state)->addMinutes((int) $duration);
                                    $set('session_end_time', $endTime->format('Y-m-d H:i:s'));
                                }
                            }),
                        Select::make('session_duration_minutes')
                            ->label('مدة الجلسة')
                            ->options(SessionDuration::options())
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                $startTime = $get('session_start_time');
                                if ($startTime && $state) {
                                    $endTime = Carbon::parse($startTime)->addMinutes((int) $state);
                                    $set('session_end_time', $endTime->format('Y-m-d H:i:s'));
                                }
                            }),
                        DateTimePicker::make('session_end_time')
                            ->label('وقت انتهاء الجلسة')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('يتم حسابه تلقائياً من وقت البدء والمدة'),
                    ])->columns(3),

                Section::make('حالة الحضور والحساب')
                    ->schema([
                        Select::make('attendance_status')
                            ->label('حالة الحضور')
                            ->options(AttendanceStatus::options())
                            ->required(),
                        TextInput::make('attendance_percentage')
                            ->label('نسبة الحضور')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(0),
                        DateTimePicker::make('attendance_calculated_at')
                            ->label('تاريخ الحساب'),
                        Toggle::make('is_calculated')
                            ->label('محسوب تلقائياً')
                            ->default(true),
                    ])->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('session.title')
                    ->label('الجلسة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->formatStateUsing(fn ($record) => $record->user?->display_name
                        ?? $record->user?->name
                        ?? 'مستخدم #'.$record->user_id
                    )
                    ->searchable()
                    ->sortable(),
                TextColumn::make('attendance_status')
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
                        } catch (ValueError $e) {
                            return (string) $state;
                        }
                    }),
                TextColumn::make('user_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("enums.attendance_user_type.{$state}") ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'student' => 'info',
                        'teacher' => 'success',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('session_type')
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
                TextColumn::make('attendance_percentage')
                    ->label('نسبة الحضور')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => $state.'%')
                    ->toggleable(),
                TextColumn::make('total_duration_minutes')
                    ->label('وقت الحضور (دقيقة)')
                    ->numeric()
                    ->sortable()
                    ->suffix(' د')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_calculated')
                    ->label('محسوب تلقائياً')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('attendance_status')
                    ->label('حالة الحضور')
                    ->options(AttendanceStatus::options()),
                SelectFilter::make('user_type')
                    ->label('نوع المستخدم')
                    ->options(__('enums.attendance_user_type')),
                Filter::make('session_source')
                    ->schema([
                        Select::make('session_type')
                            ->label('نوع الجلسة')
                            ->options([
                                'individual_quran' => 'حلقات قرآن فردية',
                                'group_quran' => 'حلقات قرآن جماعية',
                                'academic' => __('enums.session_type.academic'),
                                'interactive' => __('enums.session_type.interactive'),
                            ])
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('source_id', null)),
                        Select::make('source_id')
                            ->label(fn (Get $get): string => match ($get('session_type')) {
                                'individual_quran' => 'الحلقة الفردية',
                                'group_quran' => 'الحلقة الجماعية',
                                'academic' => 'الدرس',
                                'interactive' => 'الدورة',
                                default => 'المصدر',
                            })
                            ->searchable()
                            ->preload()
                            ->options(function (Get $get): array {
                                return match ($get('session_type')) {
                                    'individual_quran' => QuranIndividualCircle::query()
                                        ->pluck('name', 'id')
                                        ->toArray(),
                                    'group_quran' => QuranCircle::query()
                                        ->pluck('name', 'id')
                                        ->toArray(),
                                    'academic' => AcademicIndividualLesson::query()
                                        ->pluck('name', 'id')
                                        ->toArray(),
                                    'interactive' => InteractiveCourse::query()
                                        ->pluck('title', 'id')
                                        ->toArray(),
                                    default => [],
                                };
                            })
                            ->visible(fn (Get $get): bool => filled($get('session_type'))),
                    ])
                    ->columns(2)
                    ->columnSpan(2)
                    ->query(function (Builder $query, array $data): Builder {
                        $sessionType = $data['session_type'] ?? null;
                        $sourceId = $data['source_id'] ?? null;

                        if (! $sessionType) {
                            return $query;
                        }

                        if ($sessionType === 'individual_quran') {
                            $query->where('session_type', 'quran')
                                ->whereIn('session_id', function ($sub) use ($sourceId) {
                                    $sub->select('id')->from('quran_sessions')
                                        ->whereNotNull('individual_circle_id');
                                    if ($sourceId) {
                                        $sub->where('individual_circle_id', (int) $sourceId);
                                    }
                                });
                        } elseif ($sessionType === 'group_quran') {
                            $query->where('session_type', 'quran')
                                ->whereIn('session_id', function ($sub) use ($sourceId) {
                                    $sub->select('id')->from('quran_sessions')
                                        ->whereNotNull('circle_id');
                                    if ($sourceId) {
                                        $sub->where('circle_id', (int) $sourceId);
                                    }
                                });
                        } elseif ($sessionType === 'academic') {
                            $query->where('session_type', 'academic');
                            if ($sourceId) {
                                $query->whereIn('session_id', function ($sub) use ($sourceId) {
                                    $sub->select('id')->from('academic_sessions')
                                        ->where('academic_individual_lesson_id', (int) $sourceId);
                                });
                            }
                        } elseif ($sessionType === 'interactive') {
                            $query->where('session_type', 'interactive');
                            if ($sourceId) {
                                $query->whereIn('session_id', function ($sub) use ($sourceId) {
                                    $sub->select('id')->from('interactive_course_sessions')
                                        ->where('course_id', (int) $sourceId);
                                });
                            }
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['session_type'] ?? null) {
                            $labels = [
                                'individual_quran' => 'حلقات قرآن فردية',
                                'group_quran' => 'حلقات قرآن جماعية',
                                'academic' => __('enums.session_type.academic'),
                                'interactive' => __('enums.session_type.interactive'),
                            ];
                            $indicators['session_type'] = 'نوع الجلسة: '.($labels[$data['session_type']] ?? $data['session_type']);
                        }
                        if ($data['source_id'] ?? null) {
                            $indicators['source_id'] = match ($data['session_type'] ?? '') {
                                'individual_quran' => 'الحلقة الفردية محددة',
                                'group_quran' => 'الحلقة الجماعية محددة',
                                'academic' => 'الدرس محدد',
                                'interactive' => 'الدورة محددة',
                                default => 'المصدر محدد',
                            };
                        }

                        return $indicators;
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('عرض'),
                    EditAction::make()->label('تعديل'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListMeetingAttendances::route('/'),
            'view' => ViewMeetingAttendance::route('/{record}'),
            'edit' => EditMeetingAttendance::route('/{record}/edit'),
        ];
    }
}
