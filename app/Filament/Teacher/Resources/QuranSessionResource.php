<?php

namespace App\Filament\Teacher\Resources;

use Filament\Schemas\Components\Section;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Support\Collection;
use App\Models\QuranIndividualCircle;
use App\Filament\Teacher\Resources\QuranSessionResource\Pages\ListQuranSessions;
use App\Filament\Teacher\Resources\QuranSessionResource\Pages\CreateQuranSession;
use App\Filament\Teacher\Resources\QuranSessionResource\Pages\ViewQuranSession;
use App\Filament\Teacher\Resources\QuranSessionResource\Pages\EditQuranSession;
use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Filament\Shared\Actions\SessionStatusActions;
use App\Filament\Shared\Resources\BaseQuranSessionResource;
use App\Filament\Teacher\Resources\QuranSessionResource\Pages;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use Filament\Tables;
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

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationLabel = 'جلساتي';

    protected static string | \UnitEnum | null $navigationGroup = 'جلساتي';

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
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
                DeleteAction::make()
                    ->label('حذف')
                    ->after(function (QuranSession $record) {
                        if ($record->individualCircle) {
                            $record->individualCircle->updateSessionCounts();
                        }
                    }),
                Action::make('start_session')
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
                Action::make('complete_session')
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
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->after(function (Collection $records) {
                        $individualCircleIds = $records->pluck('individual_circle_id')->filter()->unique();
                        foreach ($individualCircleIds as $circleId) {
                            $circle = QuranIndividualCircle::find($circleId);
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

            TextColumn::make('session_type')
                ->badge()
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
                ->sortable()
                ->toggleable(),

            TextColumn::make('duration_minutes')
                ->label('المدة')
                ->suffix(' دقيقة')
                ->sortable()
                ->toggleable(),

            TextColumn::make('status')
                ->badge()
                ->label('الحالة')
                ->colors(SessionStatus::colorOptions())
                ->formatStateUsing(function ($state): string {
                    if ($state instanceof SessionStatus) {
                        return $state->label();
                    }
                    $status = SessionStatus::tryFrom($state);

                    return $status?->label() ?? $state;
                }),

            TextColumn::make('attendance_status')
                ->badge()
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
                })
                ->toggleable(),

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
            SelectFilter::make('period')
                ->label('الفترة الزمنية')
                ->options([
                    'today' => 'اليوم',
                    'this_week' => 'هذا الأسبوع',
                    'this_month' => 'هذا الشهر',
                    'last_week' => 'الأسبوع الماضي',
                    'last_month' => 'الشهر الماضي',
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return match ($data['value'] ?? null) {
                        'today' => $query->whereDate('scheduled_at', today()),
                        'this_week' => $query->whereBetween('scheduled_at', [
                            now()->startOfWeek(),
                            now()->endOfWeek(),
                        ]),
                        'this_month' => $query->whereYear('scheduled_at', now()->year)
                            ->whereMonth('scheduled_at', now()->month),
                        'last_week' => $query->whereBetween('scheduled_at', [
                            now()->subWeek()->startOfWeek(),
                            now()->subWeek()->endOfWeek(),
                        ]),
                        'last_month' => $query->whereYear('scheduled_at', now()->subMonth()->year)
                            ->whereMonth('scheduled_at', now()->subMonth()->month),
                        default => $query,
                    };
                }),

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
            'index' => ListQuranSessions::route('/'),
            'create' => CreateQuranSession::route('/create'),
            'view' => ViewQuranSession::route('/{record}'),
            'edit' => EditQuranSession::route('/{record}/edit'),
        ];
    }
}
