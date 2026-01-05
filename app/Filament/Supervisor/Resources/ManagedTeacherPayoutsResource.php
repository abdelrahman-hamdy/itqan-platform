<?php

namespace App\Filament\Supervisor\Resources;

use App\Enums\PayoutStatus;
use App\Filament\Supervisor\Resources\ManagedTeacherPayoutsResource\Pages;
use App\Models\TeacherPayout;
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
 * Managed Teacher Payouts Resource for Supervisor Panel
 * Allows supervisors to view payouts of assigned teachers
 * Only visible when can_manage_teachers = true
 */
class ManagedTeacherPayoutsResource extends BaseSupervisorResource
{
    protected static ?string $model = TeacherPayout::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'مدفوعات المعلمين';

    protected static ?string $modelLabel = 'دفعة معلم';

    protected static ?string $pluralModelLabel = 'مدفوعات المعلمين';

    protected static ?string $navigationGroup = 'إدارة المعلمين';

    protected static ?int $navigationSort = 3;

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

        $query = TeacherPayout::query()
            ->with(['teacher.user']);

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
                Forms\Components\Section::make('معلومات الدفعة')
                    ->schema([
                        Forms\Components\TextInput::make('payout_code')
                            ->label('رقم الدفعة')
                            ->disabled(),

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

                        Forms\Components\TextInput::make('total_amount')
                            ->label('المبلغ الإجمالي')
                            ->numeric()
                            ->prefix('ر.س')
                            ->disabled(),

                        Forms\Components\TextInput::make('sessions_count')
                            ->label('عدد الجلسات')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\DatePicker::make('payout_month')
                            ->label('شهر الدفع')
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(PayoutStatus::options())
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('تفاصيل الموافقة')
                    ->schema([
                        Forms\Components\Placeholder::make('approver_name')
                            ->label('تمت الموافقة بواسطة')
                            ->content(fn ($record) => $record?->approver?->name ?? '-'),

                        Forms\Components\Placeholder::make('approved_at_display')
                            ->label('تاريخ الموافقة')
                            ->content(fn ($record) => $record?->approved_at?->format('Y-m-d H:i') ?? '-'),

                        Forms\Components\Placeholder::make('approval_notes_display')
                            ->label('ملاحظات الموافقة')
                            ->content(fn ($record) => $record?->approval_notes ?? '-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record?->approved_at !== null),

                Forms\Components\Section::make('تفاصيل الرفض')
                    ->schema([
                        Forms\Components\Placeholder::make('rejector_name')
                            ->label('تم الرفض بواسطة')
                            ->content(fn ($record) => $record?->rejector?->name ?? '-'),

                        Forms\Components\Placeholder::make('rejected_at_display')
                            ->label('تاريخ الرفض')
                            ->content(fn ($record) => $record?->rejected_at?->format('Y-m-d H:i') ?? '-'),

                        Forms\Components\Placeholder::make('rejection_reason_display')
                            ->label('سبب الرفض')
                            ->content(fn ($record) => $record?->rejection_reason ?? '-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record?->rejected_at !== null),

                Forms\Components\Section::make('تفاصيل الأرباح')
                    ->schema([
                        Forms\Components\Placeholder::make('breakdown_display')
                            ->label('تفصيل الأرباح')
                            ->content(fn ($record) => $record?->formatted_breakdown ?? '-'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payout_code')
                    ->label('رقم الدفعة')
                    ->searchable()
                    ->sortable(),

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
                        AcademicTeacherProfile::class => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('total_amount')
                    ->label('المبلغ')
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('sessions_count')
                    ->label('الجلسات')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('payout_month')
                    ->label('الشهر')
                    ->date('Y-m')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors(PayoutStatus::colorOptions())
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof PayoutStatus) {
                            return $state->label();
                        }
                        $status = PayoutStatus::tryFrom($state);
                        return $status?->label() ?? $state;
                    }),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('teacher_type')
                    ->label('نوع المعلم')
                    ->options([
                        QuranTeacherProfile::class => 'معلم قرآن',
                        AcademicTeacherProfile::class => 'معلم أكاديمي',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(PayoutStatus::options()),

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
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('موافقة')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->canApprove())
                    ->form([
                        Forms\Components\Textarea::make('approval_notes')
                            ->label('ملاحظات الموافقة')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => PayoutStatus::APPROVED,
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'approval_notes' => $data['approval_notes'] ?? null,
                        ]);

                        // Mark all related earnings as finalized
                        $record->earnings()->update(['is_finalized' => true]);
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->canReject())
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('سبب الرفض')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => PayoutStatus::REJECTED,
                            'rejected_by' => auth()->id(),
                            'rejected_at' => now(),
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                    }),

                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('موافقة المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->canApprove()) {
                                    $record->update([
                                        'status' => PayoutStatus::APPROVED,
                                        'approved_by' => auth()->id(),
                                        'approved_at' => now(),
                                    ]);
                                }
                            });
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
            'index' => Pages\ListManagedTeacherPayouts::route('/'),
            'view' => Pages\ViewManagedTeacherPayout::route('/{record}'),
        ];
    }

    /**
     * Supervisors can view but not edit payouts
     */
    public static function canEdit($record): bool
    {
        return false;
    }
}
