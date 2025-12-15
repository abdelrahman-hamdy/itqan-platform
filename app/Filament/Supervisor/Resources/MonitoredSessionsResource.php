<?php

namespace App\Filament\Supervisor\Resources;

use App\Enums\SessionStatus;
use App\Filament\Supervisor\Resources\MonitoredSessionsResource\Pages;
use App\Models\QuranSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Monitored Sessions Resource for Supervisor Panel
 * Allows supervisors to view and monitor Quran sessions
 */
class MonitoredSessionsResource extends BaseSupervisorResource
{
    protected static ?string $model = QuranSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationLabel = 'الجلسات المراقبة';

    protected static ?string $modelLabel = 'جلسة مراقبة';

    protected static ?string $pluralModelLabel = 'الجلسات المراقبة';

    protected static ?string $navigationGroup = 'الحلقات المراقبة';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الجلسة')
                    ->schema([
                        Forms\Components\TextInput::make('session_code')
                            ->label('رمز الجلسة')
                            ->disabled(),

                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الجلسة')
                            ->disabled(),

                        Forms\Components\Select::make('session_type')
                            ->label('نوع الجلسة')
                            ->options([
                                'individual' => 'فردية',
                                'group' => 'جماعية',
                                'trial' => 'تجريبية',
                                'makeup' => 'تعويضية',
                            ])
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(SessionStatus::options())
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('المعلم والحلقة')
                    ->schema([
                        Forms\Components\Select::make('quran_teacher_id')
                            ->relationship('quranTeacher', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? $record->full_name ?? 'غير محدد')
                            ->label('المعلم')
                            ->disabled(),

                        Forms\Components\Select::make('circle_id')
                            ->relationship('circle', 'name_ar')
                            ->label('الحلقة')
                            ->disabled(),

                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'name')
                            ->label('الطالب')
                            ->disabled()
                            ->visible(fn ($record) => $record?->session_type === 'individual'),
                    ])->columns(2),

                Forms\Components\Section::make('التوقيت')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->disabled(),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('المدة (دقيقة)')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('وقت البدء')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('ended_at')
                            ->label('وقت الانتهاء')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('تقييم المشرف')
                    ->schema([
                        Forms\Components\Select::make('supervisor_quality_rating')
                            ->label('تقييم جودة الجلسة')
                            ->options([
                                '1' => 'ضعيف',
                                '2' => 'مقبول',
                                '3' => 'جيد',
                                '4' => 'جيد جداً',
                                '5' => 'ممتاز',
                            ])
                            ->helperText('تقييم المشرف لجودة الجلسة'),

                        Forms\Components\Textarea::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->rows(4)
                            ->helperText('ملاحظات وتعليقات المشرف على الجلسة'),
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
                    ->label('العنوان')
                    ->searchable()
                    ->limit(25),

                TextColumn::make('teacher_name')
                    ->label('المعلم')
                    ->state(fn ($record) => $record->quranTeacher?->user?->name ?? 'غير محدد'),

                TextColumn::make('circle.name_ar')
                    ->label('الحلقة')
                    ->searchable()
                    ->limit(20)
                    ->placeholder('جلسة فردية'),

                BadgeColumn::make('session_type')
                    ->label('النوع')
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
                    ->label('الموعد')
                    ->dateTime('Y-m-d H:i')
                    ->timezone(static::getTimezone())
                    ->sortable(),

                TextColumn::make('duration_minutes')
                    ->label('المدة')
                    ->suffix(' د')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors(SessionStatus::colorOptions())
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof SessionStatus) {
                            return $state->label();
                        }
                        $status = SessionStatus::tryFrom($state);
                        return $status?->label() ?? $state;
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
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(SessionStatus::options()),

                Tables\Filters\SelectFilter::make('session_type')
                    ->label('نوع الجلسة')
                    ->options([
                        'individual' => 'فردية',
                        'group' => 'جماعية',
                        'trial' => 'تجريبية',
                        'makeup' => 'تعويضية',
                    ]),

                Tables\Filters\SelectFilter::make('quran_teacher_id')
                    ->label('المعلم')
                    ->relationship('quranTeacher', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? $record->full_name ?? 'غير محدد')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('circle_id')
                    ->label('الحلقة')
                    ->relationship('circle', 'name_ar')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('today')
                    ->label('جلسات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Tables\Filters\Filter::make('this_week')
                    ->label('جلسات هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])),

                Tables\Filters\Filter::make('completed')
                    ->label('المكتملة فقط')
                    ->query(fn (Builder $query): Builder => $query->where('status', SessionStatus::COMPLETED->value)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\Action::make('add_note')
                    ->label('إضافة ملاحظة')
                    ->icon('heroicon-o-pencil-square')
                    ->form([
                        Forms\Components\Textarea::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->rows(4)
                            ->required(),
                    ])
                    ->action(function (QuranSession $record, array $data): void {
                        // Store supervisor note - this would need a supervisor_notes field on the model
                        // For now, we can append to notes field or create a separate notes system
                        $record->update([
                            'notes' => $record->notes . "\n\n[ملاحظة المشرف]: " . $data['supervisor_notes'],
                        ]);
                    }),
            ])
            ->bulkActions([
                // No bulk actions for supervisors
            ]);
    }

    /**
     * Override query to filter by assigned teachers or department
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['quranTeacher.user', 'circle', 'student', 'academy']);

        $profile = static::getCurrentSupervisorProfile();

        if ($profile) {
            // If supervisor has assigned teachers, filter by them
            $assignedTeachers = $profile->assigned_teachers ?? [];
            if (!empty($assignedTeachers)) {
                $query->whereIn('quran_teacher_id', $assignedTeachers);
            }

            // If supervisor is not for quran department, return empty
            if (!$profile->canAccessDepartment('quran') && $profile->department !== 'general') {
                $query->whereRaw('1 = 0');
            }
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
            'index' => Pages\ListMonitoredSessions::route('/'),
            'view' => Pages\ViewMonitoredSession::route('/{record}'),
        ];
    }

    /**
     * Supervisors can view but typically not edit sessions
     */
    public static function canEdit($record): bool
    {
        return false;
    }
}
