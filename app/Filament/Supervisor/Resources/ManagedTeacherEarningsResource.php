<?php

namespace App\Filament\Supervisor\Resources;

use App\Filament\Supervisor\Resources\ManagedTeacherEarningsResource\Pages;
use App\Models\TeacherEarning;
use App\Models\QuranTeacherProfile;
use App\Models\AcademicTeacherProfile;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Managed Teacher Earnings Resource for Supervisor Panel
 * Allows supervisors to view earnings of assigned teachers
 * Only visible when can_manage_teachers = true
 */
class ManagedTeacherEarningsResource extends BaseSupervisorResource
{
    protected static ?string $model = TeacherEarning::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'أرباح المعلمين';

    protected static ?string $modelLabel = 'ربح معلم';

    protected static ?string $pluralModelLabel = 'أرباح المعلمين';

    protected static ?string $navigationGroup = 'إدارة المعلمين';

    protected static ?int $navigationSort = 2;

    /**
     * Only show navigation if supervisor can manage teachers and has assigned teachers.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::canManageTeachers() && static::hasAssignedTeachers();
    }

    /**
     * Get assigned teacher profile IDs by type.
     */
    protected static function getAssignedTeacherProfileIds(): array
    {
        $quranTeacherUserIds = static::getAssignedQuranTeacherIds();
        $academicTeacherUserIds = static::getAssignedAcademicTeacherIds();

        $quranProfileIds = [];
        $academicProfileIds = [];

        // Get Quran teacher profile IDs
        if (!empty($quranTeacherUserIds)) {
            $quranProfileIds = QuranTeacherProfile::whereIn('user_id', $quranTeacherUserIds)
                ->pluck('id')
                ->toArray();
        }

        // Get Academic teacher profile IDs
        if (!empty($academicTeacherUserIds)) {
            $academicProfileIds = AcademicTeacherProfile::whereIn('user_id', $academicTeacherUserIds)
                ->pluck('id')
                ->toArray();
        }

        return [
            'quran' => $quranProfileIds,
            'academic' => $academicProfileIds,
        ];
    }

    /**
     * Override query to filter by assigned teacher profiles.
     */
    public static function getEloquentQuery(): Builder
    {
        $profileIds = static::getAssignedTeacherProfileIds();

        $query = TeacherEarning::query()
            ->with(['teacher.user', 'session', 'payout']);

        // Build filter based on assigned teacher profiles
        $hasQuran = !empty($profileIds['quran']);
        $hasAcademic = !empty($profileIds['academic']);

        if ($hasQuran || $hasAcademic) {
            $query->where(function ($q) use ($profileIds, $hasQuran, $hasAcademic) {
                if ($hasQuran) {
                    $q->orWhere(function ($sq) use ($profileIds) {
                        $sq->where('teacher_type', QuranTeacherProfile::class)
                           ->whereIn('teacher_id', $profileIds['quran']);
                    });
                }
                if ($hasAcademic) {
                    $q->orWhere(function ($sq) use ($profileIds) {
                        $sq->where('teacher_type', AcademicTeacherProfile::class)
                           ->whereIn('teacher_id', $profileIds['academic']);
                    });
                }
            });
        } else {
            // No teachers assigned - return empty result
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الربح')
                    ->schema([
                        Forms\Components\TextInput::make('teacher.user.name')
                            ->label('المعلم')
                            ->disabled(),

                        Forms\Components\TextInput::make('teacher_type')
                            ->label('نوع المعلم')
                            ->formatStateUsing(fn ($state) => match($state) {
                                QuranTeacherProfile::class => 'معلم قرآن',
                                AcademicTeacherProfile::class => 'معلم أكاديمي',
                                default => $state,
                            })
                            ->disabled(),

                        Forms\Components\TextInput::make('amount')
                            ->label('المبلغ')
                            ->numeric()
                            ->prefix('ر.س')
                            ->disabled(),

                        Forms\Components\TextInput::make('calculation_method')
                            ->label('طريقة الحساب')
                            ->formatStateUsing(fn ($record) => $record->calculation_method_label ?? '-')
                            ->disabled(),

                        Forms\Components\DatePicker::make('earning_month')
                            ->label('شهر الربح')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('session_completed_at')
                            ->label('تاريخ إكمال الجلسة')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('calculated_at')
                            ->label('تاريخ الحساب')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('تفاصيل الحساب')
                    ->schema([
                        Forms\Components\Placeholder::make('rate_snapshot_display')
                            ->label('الأسعار المستخدمة')
                            ->content(function ($record) {
                                if (empty($record?->rate_snapshot)) {
                                    return '-';
                                }

                                $labels = [
                                    'individual_rate' => 'سعر الجلسة الفردية',
                                    'group_rate' => 'سعر الجلسة الجماعية',
                                    'per_session' => 'سعر الجلسة',
                                    'per_student' => 'سعر لكل طالب',
                                    'hourly_rate' => 'سعر الساعة',
                                ];

                                $lines = [];
                                foreach ($record->rate_snapshot as $key => $value) {
                                    $label = $labels[$key] ?? $key;
                                    $lines[] = "{$label}: " . number_format($value, 2) . ' ر.س';
                                }

                                return implode(' | ', $lines) ?: '-';
                            }),

                        Forms\Components\Placeholder::make('calculation_metadata_display')
                            ->label('تفاصيل الجلسة')
                            ->content(function ($record) {
                                if (empty($record?->calculation_metadata)) {
                                    return '-';
                                }

                                $labels = [
                                    'session_type' => 'نوع الجلسة',
                                    'duration_minutes' => 'المدة (دقيقة)',
                                    'subject' => 'المادة',
                                    'students_count' => 'عدد الطلاب',
                                    'circle_type' => 'نوع الحلقة',
                                ];

                                $sessionTypes = [
                                    'individual' => 'فردية',
                                    'group' => 'جماعية',
                                    'trial' => 'تجريبية',
                                    'circle' => 'حلقة',
                                ];

                                $lines = [];
                                foreach ($record->calculation_metadata as $key => $value) {
                                    $label = $labels[$key] ?? $key;

                                    if ($key === 'session_type' && isset($sessionTypes[$value])) {
                                        $value = $sessionTypes[$value];
                                    }

                                    $lines[] = "{$label}: {$value}";
                                }

                                return implode(' | ', $lines) ?: '-';
                            }),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('الحالة')
                    ->schema([
                        Forms\Components\Toggle::make('is_finalized')
                            ->label('تم التأكيد')
                            ->helperText('هل تم تأكيد هذا الربح؟')
                            ->disabled(),

                        Forms\Components\Toggle::make('is_disputed')
                            ->label('معترض عليه')
                            ->helperText('هل يوجد اعتراض على هذا الربح؟')
                            ->disabled(),

                        Forms\Components\Textarea::make('dispute_notes')
                            ->label('ملاحظات الاعتراض')
                            ->rows(3)
                            ->disabled()
                            ->visible(fn ($get) => $get('is_disputed')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('teacher.user.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('teacher_type')
                    ->label('نوع المعلم')
                    ->formatStateUsing(fn ($state) => match($state) {
                        QuranTeacherProfile::class => 'قرآن',
                        AcademicTeacherProfile::class => 'أكاديمي',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        QuranTeacherProfile::class => 'success',
                        AcademicTeacherProfile::class => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('calculation_method')
                    ->label('طريقة الحساب')
                    ->formatStateUsing(fn ($record) => $record->calculation_method_label)
                    ->badge()
                    ->color('gray'),

                TextColumn::make('earning_month')
                    ->label('الشهر')
                    ->date('M Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_finalized')
                    ->label('مؤكد')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_disputed')
                    ->label('معترض')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('gray'),

                TextColumn::make('payout.payout_code')
                    ->label('رقم الصرف')
                    ->placeholder('غير مصروف')
                    ->badge()
                    ->color('success'),

                TextColumn::make('session_completed_at')
                    ->label('تاريخ الجلسة')
                    ->dateTime('Y-m-d H:i')
                    ->timezone(static::getTimezone())
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('session_completed_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('teacher_type')
                    ->label('نوع المعلم')
                    ->options([
                        QuranTeacherProfile::class => 'معلم قرآن',
                        AcademicTeacherProfile::class => 'معلم أكاديمي',
                    ]),

                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('المعلم')
                    ->options(function () {
                        $teacherIds = static::getAllAssignedTeacherIds();
                        return User::whereIn('id', $teacherIds)
                            ->get()
                            ->mapWithKeys(fn ($user) => [$user->id => $user->full_name ?? $user->name ?? $user->email]);
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $userId = $data['value'];
                            $user = User::find($userId);

                            if ($user) {
                                if ($user->user_type === 'quran_teacher') {
                                    $profile = $user->quranTeacherProfile;
                                    if ($profile) {
                                        $query->where('teacher_type', QuranTeacherProfile::class)
                                              ->where('teacher_id', $profile->id);
                                    }
                                } elseif ($user->user_type === 'academic_teacher') {
                                    $profile = $user->academicTeacherProfile;
                                    if ($profile) {
                                        $query->where('teacher_type', AcademicTeacherProfile::class)
                                              ->where('teacher_id', $profile->id);
                                    }
                                }
                            }
                        }
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_finalized')
                    ->label('مؤكد')
                    ->trueLabel('مؤكد')
                    ->falseLabel('غير مؤكد'),

                Tables\Filters\TernaryFilter::make('is_disputed')
                    ->label('معترض عليه')
                    ->trueLabel('معترض عليه')
                    ->falseLabel('غير معترض عليه'),

                Tables\Filters\Filter::make('unpaid')
                    ->label('غير مدفوع')
                    ->query(fn (Builder $query): Builder => $query->whereNull('payout_id')),
            ])
            ->actions([
                Tables\Actions\Action::make('finalize')
                    ->label('تأكيد')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_finalized && !$record->is_disputed)
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد الربح')
                    ->modalDescription('هل أنت متأكد من تأكيد هذا الربح؟')
                    ->action(fn ($record) => $record->update(['is_finalized' => true])),

                Tables\Actions\Action::make('dispute')
                    ->label('اعتراض')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->visible(fn ($record) => !$record->is_disputed)
                    ->form([
                        Forms\Components\Textarea::make('dispute_notes')
                            ->label('سبب الاعتراض')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(fn ($record, array $data) => $record->update([
                        'is_disputed' => true,
                        'dispute_notes' => $data['dispute_notes'],
                    ])),

                Tables\Actions\Action::make('resolve_dispute')
                    ->label('حل الاعتراض')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => $record->is_disputed)
                    ->requiresConfirmation()
                    ->modalHeading('حل الاعتراض')
                    ->modalDescription('هل أنت متأكد من حل هذا الاعتراض وتأكيد الربح؟')
                    ->modalSubmitActionLabel('نعم، حل الاعتراض')
                    ->form([
                        Forms\Components\Placeholder::make('current_dispute_notes')
                            ->label('سبب الاعتراض الحالي')
                            ->content(fn ($record) => $record->dispute_notes ?? '-'),
                        Forms\Components\Textarea::make('resolution_notes')
                            ->label('ملاحظات الحل')
                            ->helperText('أضف ملاحظات توضح كيف تم حل الاعتراض')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $resolutionNote = $data['resolution_notes'] ?? '';
                        $previousNotes = $record->dispute_notes ?? '';

                        // Append resolution notes to dispute_notes for audit trail
                        $updatedNotes = $previousNotes;
                        if ($resolutionNote) {
                            $updatedNotes .= "\n\n--- تم الحل بتاريخ " . now()->format('Y-m-d H:i') . " ---\n" . $resolutionNote;
                        }

                        $record->update([
                            'is_disputed' => false,
                            'is_finalized' => true,
                            'dispute_notes' => $updatedNotes,
                        ]);
                    }),

                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('finalize_selected')
                        ->label('تأكيد المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_finalized' => true]));
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManagedTeacherEarnings::route('/'),
            'view' => Pages\ViewManagedTeacherEarning::route('/{record}'),
        ];
    }

    /**
     * Supervisors can view but not edit earnings
     */
    public static function canEdit($record): bool
    {
        return false;
    }
}
