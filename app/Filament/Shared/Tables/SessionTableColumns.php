<?php

namespace App\Filament\Shared\Tables;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Services\AcademyContextService;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;

/**
 * Shared session table column definitions for reuse across admin and supervisor panels.
 */
class SessionTableColumns
{
    /**
     * Get common Quran session columns.
     */
    public static function getQuranSessionColumns(bool $showTeacher = true, bool $showCircle = true): array
    {
        $columns = [
            TextColumn::make('session_code')
                ->label('رمز الجلسة')
                ->searchable()
                ->sortable(),

            TextColumn::make('title')
                ->label('العنوان')
                ->searchable()
                ->limit(25),
        ];

        if ($showTeacher) {
            $columns[] = TextColumn::make('quranTeacher.name')
                ->label('المعلم')
                ->searchable()
                ->placeholder('غير محدد');
        }

        if ($showCircle) {
            $columns[] = TextColumn::make('circle.name')
                ->label('الحلقة')
                ->searchable()
                ->limit(20)
                ->placeholder('جلسة فردية');
        }

        $columns[] = TextColumn::make('student.name')
            ->label('الطالب')
            ->searchable()
            ->placeholder('جماعية')
            ->toggleable();

        $columns[] = BadgeColumn::make('session_type')
            ->label('النوع')
            ->colors([
                'primary' => 'individual',
                'success' => 'group',
                'warning' => 'trial',
            ])
            ->formatStateUsing(fn (string $state): string => match ($state) {
                'individual' => 'فردية',
                'group' => 'جماعية',
                'trial' => 'تجريبية',
                default => $state,
            });

        $columns = array_merge($columns, self::getCommonSessionColumns());

        return $columns;
    }

    /**
     * Get common Academic session columns.
     */
    public static function getAcademicSessionColumns(bool $showTeacher = true, bool $showLesson = true): array
    {
        $columns = [
            TextColumn::make('session_code')
                ->label('رمز الجلسة')
                ->searchable()
                ->sortable(),

            TextColumn::make('title')
                ->label('العنوان')
                ->searchable()
                ->limit(25),
        ];

        if ($showTeacher) {
            $columns[] = TextColumn::make('academicTeacher.user.name')
                ->label('المعلم')
                ->searchable()
                ->placeholder('غير محدد');
        }

        if ($showLesson) {
            $columns[] = TextColumn::make('academicIndividualLesson.name')
                ->label('الدرس')
                ->searchable()
                ->limit(20)
                ->placeholder('غير محدد');

            $columns[] = TextColumn::make('academicIndividualLesson.academicSubject.name')
                ->label('المادة')
                ->sortable()
                ->toggleable();
        }

        $columns[] = TextColumn::make('student.name')
            ->label('الطالب')
            ->searchable()
            ->placeholder('غير محدد');

        $columns[] = BadgeColumn::make('session_type')
            ->label('النوع')
            ->colors([
                'primary' => 'individual',
                'success' => 'group',
            ])
            ->formatStateUsing(fn (string $state): string => match ($state) {
                'individual' => 'فردية',
                'group' => 'جماعية',
                default => $state,
            });

        $columns = array_merge($columns, self::getCommonSessionColumns());

        return $columns;
    }

    /**
     * Get common Interactive Course session columns.
     */
    public static function getInteractiveCourseSessionColumns(bool $showCourse = true, bool $showTeacher = true): array
    {
        $columns = [
            TextColumn::make('session_code')
                ->label('رمز الجلسة')
                ->searchable()
                ->sortable(),

            TextColumn::make('title')
                ->label('العنوان')
                ->searchable()
                ->limit(25),
        ];

        if ($showCourse) {
            $columns[] = TextColumn::make('course.title')
                ->label('الدورة')
                ->searchable()
                ->limit(25);
        }

        if ($showTeacher) {
            $columns[] = TextColumn::make('course.assignedTeacher.user.name')
                ->label('المعلم')
                ->searchable()
                ->placeholder('غير محدد');
        }

        $columns[] = TextColumn::make('session_number')
            ->label('رقم الجلسة')
            ->sortable()
            ->toggleable();

        $columns[] = TextColumn::make('attendance_count')
            ->label('الحضور')
            ->sortable()
            ->badge()
            ->color('info')
            ->toggleable();

        $columns = array_merge($columns, self::getCommonSessionColumns(false));

        return $columns;
    }

    /**
     * Get common columns for all session types.
     */
    public static function getCommonSessionColumns(bool $showAttendance = true): array
    {
        $columns = [
            TextColumn::make('scheduled_at')
                ->label('الموعد')
                ->dateTime('Y-m-d H:i')
                ->timezone(AcademyContextService::getTimezone())
                ->sortable(),

            TextColumn::make('duration_minutes')
                ->label('المدة')
                ->suffix(' د')
                ->sortable()
                ->toggleable(),

            BadgeColumn::make('status')
                ->label('الحالة')
                ->colors(SessionStatus::colorOptions())
                ->formatStateUsing(function ($state): string {
                    if ($state instanceof SessionStatus) {
                        return $state->label();
                    }
                    $status = SessionStatus::tryFrom($state);
                    return $status?->label() ?? (string) $state;
                }),
        ];

        if ($showAttendance) {
            $columns[] = BadgeColumn::make('attendance_status')
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
                    'pending' => 'في الانتظار',
                    null => 'غير محدد',
                    default => $state,
                })
                ->toggleable(isToggledHiddenByDefault: true);
        }

        return $columns;
    }
}
