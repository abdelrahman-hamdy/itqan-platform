<?php

namespace App\Filament\Supervisor\Resources;

use App\Enums\TrialRequestStatus;
use App\Filament\Shared\Resources\BaseQuranTrialRequestResource;
use App\Filament\Supervisor\Resources\MonitoredTrialRequestsResource\Pages;
use App\Models\QuranTeacherProfile;
use App\Models\QuranTrialRequest;
use App\Models\SupervisorProfile;
use App\Services\AcademyContextService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Monitored Trial Requests Resource for Supervisor Panel
 *
 * Supervisors can view and manage trial requests for their assigned Quran teachers.
 * Extends BaseQuranTrialRequestResource for shared form/table definitions.
 */
class MonitoredTrialRequestsResource extends BaseQuranTrialRequestResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationLabel = 'طلبات الجلسات التجريبية';

    protected static ?string $navigationGroup = 'إدارة القرآن';

    protected static ?int $navigationSort = 3;

    // ========================================
    // Supervisor Helper Methods
    // ========================================

    /**
     * Get current supervisor's profile.
     */
    protected static function getCurrentSupervisorProfile(): ?SupervisorProfile
    {
        $user = Auth::user();

        if (! $user || ! $user->supervisorProfile) {
            return null;
        }

        return $user->supervisorProfile;
    }

    /**
     * Get assigned Quran teacher profile IDs.
     */
    protected static function getAssignedQuranTeacherProfileIds(): array
    {
        $profile = static::getCurrentSupervisorProfile();
        if (! $profile) {
            return [];
        }

        $teacherUserIds = $profile->getAssignedQuranTeacherIds();
        if (empty($teacherUserIds)) {
            return [];
        }

        return QuranTeacherProfile::whereIn('user_id', $teacherUserIds)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Check if supervisor has any assigned Quran teachers.
     */
    protected static function hasAssignedQuranTeachers(): bool
    {
        $profile = static::getCurrentSupervisorProfile();

        return ! empty($profile?->getAssignedQuranTeacherIds());
    }

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * Filter to assigned teachers' trial requests only.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        $teacherProfileIds = static::getAssignedQuranTeacherProfileIds();

        if (empty($teacherProfileIds)) {
            return $query->whereRaw('1 = 0'); // Return no results
        }

        $user = Auth::user();

        return $query
            ->whereIn('teacher_id', $teacherProfileIds)
            ->where('academy_id', $user->academy_id);
    }

    /**
     * Get form schema for Supervisor - comprehensive view.
     */
    protected static function getFormSchema(): array
    {
        return [
            static::getRequestInfoFormSection()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('request_code')
                                ->label('رقم الطلب')
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('status')
                                ->label('حالة الطلب')
                                ->options(TrialRequestStatus::options())
                                ->required()
                                ->native(false)
                                ->helperText('يمكن للمشرف تحديث حالة الطلب'),
                        ]),
                ]),

            Section::make('معلومات الطالب')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('student_name')
                                ->label('اسم الطالب')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('student_age')
                                ->label('عمر الطالب')
                                ->disabled()
                                ->dehydrated(false)
                                ->suffix(' سنة'),

                            TextInput::make('phone')
                                ->label('رقم هاتف الطالب')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('email')
                                ->label('البريد الإلكتروني')
                                ->disabled()
                                ->dehydrated(false),
                        ]),
                ]),

            Section::make('المعلم المسؤول')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('teacher_id')
                                ->label('المعلم')
                                ->options(function () {
                                    $teacherProfileIds = static::getAssignedQuranTeacherProfileIds();
                                    if (empty($teacherProfileIds)) {
                                        return ['0' => 'لا توجد معلمين مُسندين'];
                                    }

                                    return QuranTeacherProfile::whereIn('id', $teacherProfileIds)
                                        ->get()
                                        ->mapWithKeys(function ($teacher) {
                                            return [$teacher->id => $teacher->display_name ?? $teacher->full_name ?? 'معلم غير محدد'];
                                        })->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->disabled()
                                ->dehydrated(false),
                        ]),
                ]),

            Section::make('تفاصيل التعلم')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('current_level')
                                ->label('المستوى الحالي')
                                ->options(QuranTrialRequest::LEVELS)
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('preferred_time')
                                ->label('الوقت المفضل')
                                ->options(QuranTrialRequest::TIMES)
                                ->disabled()
                                ->dehydrated(false),
                        ]),
                ]),

            Section::make('ملاحظات')
                ->schema([
                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(4)
                        ->helperText('ملاحظات حول الطالب والجلسة التجريبية')
                        ->columnSpanFull(),
                ]),

            static::getSessionEvaluationFormSection(),
        ];
    }

    /**
     * Table actions for supervisors - includes schedule action.
     */
    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                Tables\Actions\ViewAction::make()
                    ->label('عرض')
                    ->icon('heroicon-m-eye'),

                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-m-pencil')
                    ->visible(function (QuranTrialRequest $record): bool {
                        $teacherProfileIds = static::getAssignedQuranTeacherProfileIds();
                        if (empty($teacherProfileIds)) {
                            return false;
                        }

                        $isTeacherAssigned = in_array($record->teacher_id, $teacherProfileIds);
                        if (! $isTeacherAssigned) {
                            return false;
                        }

                        // Show edit for PENDING, SCHEDULED, and COMPLETED
                        return in_array($record->status, [
                            TrialRequestStatus::PENDING,
                            TrialRequestStatus::SCHEDULED,
                            TrialRequestStatus::COMPLETED,
                        ]);
                    }),

                Tables\Actions\Action::make('schedule_session')
                    ->label('جدولة الجلسة')
                    ->icon('heroicon-m-calendar')
                    ->color('info')
                    ->visible(fn (QuranTrialRequest $record): bool => $record->status === TrialRequestStatus::PENDING)
                    ->form([
                        DateTimePicker::make('scheduled_at')
                            ->label('موعد الجلسة التجريبية')
                            ->required()
                            ->native(false)
                            ->timezone(AcademyContextService::getTimezone())
                            ->minDate(now())
                            ->helperText('سيتم إنشاء غرفة اجتماع LiveKit تلقائياً'),

                        Textarea::make('teacher_message')
                            ->label('رسالة للطالب (اختياري)')
                            ->rows(3)
                            ->placeholder('اكتب رسالة ترحيبية أو تعليمات للطالب...'),
                    ])
                    ->action(function (QuranTrialRequest $record, array $data) {
                        $data['teacher_response'] = $data['teacher_message'] ?? null;
                        static::executeScheduleAction($record, $data);
                    })
                    ->successNotificationTitle('تم جدولة الجلسة بنجاح'),

                Tables\Actions\Action::make('cancel_request')
                    ->label('إلغاء الطلب')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (QuranTrialRequest $record): bool => in_array($record->status, [TrialRequestStatus::PENDING, TrialRequestStatus::SCHEDULED]))
                    ->requiresConfirmation()
                    ->modalHeading('إلغاء طلب الجلسة التجريبية')
                    ->modalDescription('هل أنت متأكد من إلغاء هذا الطلب؟')
                    ->action(fn (QuranTrialRequest $record) => $record->update(['status' => TrialRequestStatus::CANCELLED])),

                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ]),
        ];
    }

    /**
     * Bulk actions for supervisors.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\BulkAction::make('bulk_cancel')
                    ->label('إلغاء جماعي')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            if (in_array($record->status, [TrialRequestStatus::PENDING, TrialRequestStatus::SCHEDULED])) {
                                $record->update(['status' => TrialRequestStatus::CANCELLED]);
                            }
                        }
                    }),
                Tables\Actions\DeleteBulkAction::make()
                    ->label('حذف المحدد'),
            ]),
        ];
    }

    // ========================================
    // Table Columns Override (Supervisor-specific)
    // ========================================

    /**
     * Table columns with teacher and student details.
     */
    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('request_code')
                ->label('رقم الطلب')
                ->searchable()
                ->copyable()
                ->weight(FontWeight::Bold),

            TextColumn::make('student_name')
                ->label('اسم الطالب')
                ->searchable()
                ->sortable(),

            TextColumn::make('teacher.full_name')
                ->label('المعلم')
                ->searchable()
                ->sortable(),

            TextColumn::make('student_age')
                ->label('العمر')
                ->formatStateUsing(fn (?int $state): string => $state ? $state.' سنة' : 'غير محدد')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('phone')
                ->label('الهاتف')
                ->searchable()
                ->copyable()
                ->toggleable(isToggledHiddenByDefault: true),

            BadgeColumn::make('status')
                ->label('الحالة')
                ->formatStateUsing(fn (TrialRequestStatus $state): string => $state->label())
                ->color(fn (TrialRequestStatus $state): string => $state->color()),

            TextColumn::make('current_level')
                ->label('المستوى')
                ->formatStateUsing(fn (string $state): string => QuranTrialRequest::LEVELS[$state] ?? $state)
                ->badge()
                ->color('info')
                ->toggleable(),

            TextColumn::make('trialSession.scheduled_at')
                ->label('موعد الجلسة')
                ->dateTime('d/m/Y H:i')
                ->timezone(fn ($record) => $record->academy?->timezone?->value ?? 'Asia/Riyadh')
                ->sortable()
                ->placeholder('غير مجدول'),

            TextColumn::make('rating')
                ->label('التقييم')
                ->formatStateUsing(fn ($state) => $state ? "{$state}/10" : '-')
                ->badge()
                ->color(fn ($state) => match (true) {
                    $state >= 8 => 'success',
                    $state >= 5 => 'warning',
                    default => 'danger',
                })
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('created_at')
                ->label('تاريخ الطلب')
                ->dateTime('d/m/Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    // ========================================
    // Table Filters Override (Supervisor-specific)
    // ========================================

    /**
     * Supervisor-specific filters.
     */
    protected static function getTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label('حالة الطلب')
                ->options(TrialRequestStatus::options()),

            SelectFilter::make('teacher_id')
                ->label('المعلم')
                ->options(function () {
                    $teacherProfileIds = static::getAssignedQuranTeacherProfileIds();

                    return QuranTeacherProfile::whereIn('id', $teacherProfileIds)
                        ->get()
                        ->mapWithKeys(fn ($teacher) => [$teacher->id => $teacher->full_name ?? $teacher->display_name]);
                })
                ->searchable()
                ->preload(),

            SelectFilter::make('current_level')
                ->label('المستوى')
                ->options(QuranTrialRequest::LEVELS),

            Filter::make('needs_action')
                ->label('يتطلب إجراء')
                ->query(fn (Builder $query): Builder => $query->where('status', TrialRequestStatus::PENDING->value)
                    ->whereDoesntHave('trialSession')
                ),

            Filter::make('scheduled_today')
                ->label('مجدول اليوم')
                ->query(fn (Builder $query): Builder => $query->whereHas('trialSession', fn ($q) => $q->whereDate('scheduled_at', now()->toDateString())
                )
                ),
        ];
    }

    // ========================================
    // Navigation Visibility
    // ========================================

    /**
     * Only show navigation if supervisor has assigned Quran teachers.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::hasAssignedQuranTeachers();
    }

    // ========================================
    // Authorization Overrides
    // ========================================

    /**
     * Supervisors cannot create new trial requests.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        $teacherProfileIds = static::getAssignedQuranTeacherProfileIds();

        return in_array($record->teacher_id, $teacherProfileIds);
    }

    public static function canEdit(Model $record): bool
    {
        $teacherProfileIds = static::getAssignedQuranTeacherProfileIds();

        if (empty($teacherProfileIds)) {
            return false;
        }

        // Check if teacher is assigned to this supervisor
        $isTeacherAssigned = in_array($record->teacher_id, $teacherProfileIds);
        if (! $isTeacherAssigned) {
            return false;
        }

        // Allow editing PENDING, SCHEDULED, and COMPLETED (for rating/feedback)
        // Use enum cases directly for proper comparison
        $editableStatuses = [
            TrialRequestStatus::PENDING,
            TrialRequestStatus::SCHEDULED,
            TrialRequestStatus::COMPLETED,
        ];

        return in_array($record->status, $editableStatuses);
    }

    public static function canDelete(Model $record): bool
    {
        $teacherProfileIds = static::getAssignedQuranTeacherProfileIds();

        return in_array($record->teacher_id, $teacherProfileIds);
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonitoredTrialRequests::route('/'),
            'view' => Pages\ViewMonitoredTrialRequest::route('/{record}'),
            'edit' => Pages\EditMonitoredTrialRequest::route('/{record}/edit'),
        ];
    }
}
