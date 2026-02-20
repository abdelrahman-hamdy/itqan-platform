<?php

namespace App\Filament\Teacher\Resources;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Filament\Shared\Actions\MeetingActions;
use App\Filament\Shared\Resources\BaseQuranSessionResource;
use App\Filament\Teacher\Resources\QuranSessionResource\Pages;
use App\Filament\Teacher\Resources\QuranSessionResource\Pages\CreateQuranSession;
use App\Filament\Teacher\Resources\QuranSessionResource\Pages\EditQuranSession;
use App\Filament\Teacher\Resources\QuranSessionResource\Pages\ListQuranSessions;
use App\Filament\Teacher\Resources\QuranSessionResource\Pages\ViewQuranSession;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\AcademyContextService;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
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

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationLabel = 'جلساتي';

    protected static string|\UnitEnum|null $navigationGroup = 'جلساتي';

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
            ->with(['subscription', 'student', 'academy']);
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
                MeetingActions::viewMeeting('quran'),
                DeleteAction::make()
                    ->label('حذف')
                    ->after(function (QuranSession $record) {
                        if ($record->individualCircle) {
                            $record->individualCircle->updateSessionCounts();
                        }
                    }),
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
                ->limit(30)
                ->toggleable(),

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
                ->formatStateUsing(fn (string $state): string => static::formatSessionType($state))
                ->toggleable(),

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
            SelectFilter::make('status')
                ->label('الحالة')
                ->options(SessionStatus::options()),

            SelectFilter::make('session_type')
                ->label('نوع الجلسة')
                ->options(static::getSessionTypeOptions()),

            SelectFilter::make('individual_circle_id')
                ->label('الحلقة الفردية')
                ->options(fn () => QuranIndividualCircle::where('quran_teacher_id', Auth::id())
                    ->with(['student'])
                    ->get()
                    ->mapWithKeys(fn ($ic) => [
                        $ic->id => trim(($ic->student?->first_name ?? '').' '.($ic->student?->last_name ?? ''))
                            ?: 'حلقة #'.$ic->id,
                    ])
                )
                ->searchable(),

            SelectFilter::make('circle_id')
                ->label('الحلقة الجماعية')
                ->options(fn () => QuranCircle::where('quran_teacher_id', Auth::id())
                    ->pluck('name', 'id')
                )
                ->searchable(),

            SelectFilter::make('student_id')
                ->label('الطالب')
                ->options(fn () => User::where('user_type', 'student')
                    ->whereIn('id', function ($query) {
                        $query->select('student_id')
                            ->from('quran_sessions')
                            ->where('quran_teacher_id', Auth::id())
                            ->whereNotNull('student_id')
                            ->distinct();
                    })
                    ->get()
                    ->mapWithKeys(fn ($u) => [
                        $u->id => trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: 'طالب #'.$u->id,
                    ])
                )
                ->searchable(),

            Filter::make('date_range')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            DatePicker::make('from')
                                ->label('من تاريخ'),
                            DatePicker::make('until')
                                ->label('إلى تاريخ'),
                        ]),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('scheduled_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('scheduled_at', '<=', $date));
                })
                ->columnSpan(2),
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
