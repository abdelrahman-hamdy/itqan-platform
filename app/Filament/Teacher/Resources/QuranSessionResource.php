<?php

namespace App\Filament\Teacher\Resources;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Filament\Shared\Actions\SessionStatusActions;
use App\Filament\Shared\Resources\BaseQuranSessionResource;
use App\Filament\Teacher\Resources\QuranSessionResource\Pages;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use Filament\Forms\Components\Section;
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
 * Quran Session Resource for Teacher Panel
 *
 * Teachers can view and manage their own sessions only.
 * Includes session start/complete actions.
 * Extends BaseQuranSessionResource for shared form/table definitions.
 */
class QuranSessionResource extends BaseQuranSessionResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationLabel = 'جلساتي';

    protected static ?string $navigationGroup = 'جلساتي';

    protected static ?int $navigationSort = 1;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * Filter sessions to current teacher only.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user->isQuranTeacher() || ! $user->quranTeacherProfile) {
            return $query->whereRaw('1 = 0'); // Return no results
        }

        return $query
            ->where('quran_teacher_id', $user->id)
            ->where('academy_id', $user->academy_id)
            ->with(['subscription']);
    }

    /**
     * Teachers don't need teacher/circle selection - sessions are pre-assigned.
     */
    protected static function getTeacherCircleFormSection(): ?Section
    {
        return null;
    }

    /**
     * Teacher-specific table actions with session control.
     */
    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->after(function (QuranSession $record) {
                        if ($record->individualCircle) {
                            $record->individualCircle->updateSessionCounts();
                        }
                    }),
                Tables\Actions\Action::make('start_session')
                    ->label('بدء الجلسة')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (QuranSession $record): bool => $record->status === SessionStatus::SCHEDULED->value)
                    ->action(function (QuranSession $record) {
                        $record->update([
                            'status' => SessionStatus::ONGOING->value,
                            'started_at' => now(),
                        ]);
                    }),
                Tables\Actions\Action::make('complete_session')
                    ->label('إنهاء الجلسة')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (QuranSession $record): bool => $record->status === SessionStatus::ONGOING->value)
                    ->action(function (QuranSession $record) {
                        $record->update([
                            'status' => SessionStatus::COMPLETED->value,
                            'ended_at' => now(),
                            'actual_duration_minutes' => now()->diffInMinutes($record->started_at),
                        ]);
                    }),
                SessionStatusActions::cancelSession(role: 'teacher'),
            ]),
        ];
    }

    /**
     * Bulk actions with session count update.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    ->after(function (\Illuminate\Support\Collection $records) {
                        $individualCircleIds = $records->pluck('individual_circle_id')->filter()->unique();
                        foreach ($individualCircleIds as $circleId) {
                            $circle = \App\Models\QuranIndividualCircle::find($circleId);
                            if ($circle) {
                                $circle->updateSessionCounts();
                            }
                        }
                    }),
            ]),
        ];
    }

    // ========================================
    // Table Columns Override (Teacher-specific)
    // ========================================

    /**
     * Table columns with student, attendance, and subscription tracking.
     */
    protected static function getTableColumns(): array
    {
        return [
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
                ])
                ->formatStateUsing(fn (string $state): string => static::formatSessionType($state)),

            TextColumn::make('scheduled_at')
                ->label('موعد الجلسة')
                ->dateTime('Y-m-d H:i')
                ->timezone(fn ($record) => $record->academy?->timezone?->value ?? AcademyContextService::getTimezone())
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
                    'success' => AttendanceStatus::ATTENDED->value,
                    'danger' => AttendanceStatus::ABSENT->value,
                    'warning' => AttendanceStatus::LATE->value,
                    'info' => AttendanceStatus::LEFT->value,
                    'gray' => 'pending',
                ])
                ->formatStateUsing(fn (?string $state): string => match ($state) {
                    AttendanceStatus::ATTENDED->value => 'حاضر',
                    AttendanceStatus::ABSENT->value => 'غائب',
                    AttendanceStatus::LATE->value => 'متأخر',
                    AttendanceStatus::LEFT->value => 'غادر مبكراً',
                    SessionSubscriptionStatus::PENDING->value => 'في الانتظار',
                    null => 'غير محدد',
                    default => $state,
                }),

            TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime('Y-m-d')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    // ========================================
    // Table Filters Override (Teacher-specific)
    // ========================================

    /**
     * Teacher-specific filters with attendance status.
     */
    protected static function getTableFilters(): array
    {
        return [
            SelectFilter::make('session_type')
                ->label('نوع الجلسة')
                ->options(static::getSessionTypeOptions()),

            SelectFilter::make('status')
                ->label('الحالة')
                ->options(SessionStatus::options()),

            SelectFilter::make('attendance_status')
                ->label('الحضور')
                ->options([
                    AttendanceStatus::ATTENDED->value => 'حاضر',
                    AttendanceStatus::ABSENT->value => 'غائب',
                    AttendanceStatus::LATE->value => 'متأخر',
                    AttendanceStatus::LEFT->value => 'غادر مبكراً',
                    SessionSubscriptionStatus::PENDING->value => 'في الانتظار',
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
        ];
    }

    // ========================================
    // Authorization Overrides
    // ========================================

    public static function canView(Model $record): bool
    {
        $user = Auth::user();

        return $record->quran_teacher_id === $user->id;
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        return $record->quran_teacher_id === $user->id;
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        return $record->quran_teacher_id === $user->id;
    }

    // ========================================
    // Pages
    // ========================================

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
