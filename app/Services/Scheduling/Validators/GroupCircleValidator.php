<?php

namespace App\Services\Scheduling\Validators;

use App\Models\QuranCircle;
use App\Services\AcademyContextService;
use App\Services\Scheduling\ValidationResult;
use Carbon\Carbon;
use App\Enums\SessionStatus;

/**
 * Validator for Group Quran Circles (Continuous, no fixed end)
 */
class GroupCircleValidator implements ScheduleValidatorInterface
{
    public function __construct(
        private QuranCircle $circle
    ) {}

    /**
     * Validate circle capacity before scheduling
     *
     * Checks if the circle has students enrolled and if it's at capacity.
     * Warning if near capacity, error if empty (no point scheduling without students).
     */
    public function validateCapacity(): ValidationResult
    {
        $maxStudents = $this->circle->max_students ?? 20;
        $currentStudents = $this->circle->students()->count();
        $availableSlots = $maxStudents - $currentStudents;

        // If no students enrolled, warn but allow scheduling
        if ($currentStudents === 0) {
            return ValidationResult::warning(
                '⚠️ لا يوجد طلاب مسجلين في هذه الحلقة. قد ترغب في تسجيل طلاب قبل جدولة الجلسات.',
                ['max_students' => $maxStudents, 'current_students' => 0, 'available_slots' => $availableSlots]
            );
        }

        // If circle is at minimum threshold (less than 25% capacity), warn
        $minThreshold = ceil($maxStudents * 0.25);
        if ($currentStudents < $minThreshold) {
            return ValidationResult::warning(
                "⚠️ عدد الطلاب قليل ({$currentStudents} من {$maxStudents}). قد ترغب في قبول المزيد من الطلاب.",
                ['max_students' => $maxStudents, 'current_students' => $currentStudents, 'available_slots' => $availableSlots]
            );
        }

        // If circle is full, inform (not an error - can still schedule)
        if ($availableSlots <= 0) {
            return ValidationResult::success(
                "✓ الحلقة ممتلئة ({$currentStudents}/{$maxStudents} طالب)",
                ['max_students' => $maxStudents, 'current_students' => $currentStudents, 'is_full' => true]
            );
        }

        return ValidationResult::success(
            "✓ السعة مناسبة ({$currentStudents}/{$maxStudents} طالب، {$availableSlots} مقعد متاح)",
            ['max_students' => $maxStudents, 'current_students' => $currentStudents, 'available_slots' => $availableSlots]
        );
    }

    public function validateDaySelection(array $days): ValidationResult
    {
        $dayCount = count($days);

        if ($dayCount === 0) {
            return ValidationResult::error('يجب اختيار يوم واحد على الأقل');
        }

        if ($dayCount > 7) {
            return ValidationResult::error('لا يمكن اختيار أكثر من 7 أيام في الأسبوع');
        }

        // Use actual monthly_sessions_count from circle (database has default of 8)
        $monthlyTarget = $this->circle->monthly_sessions_count;
        $recommendedDaysPerWeek = ceil($monthlyTarget / 4); // 4 weeks in a month
        $maxDaysPerWeek = $recommendedDaysPerWeek + 2; // Allow flexibility

        if ($dayCount > $maxDaysPerWeek) {
            return ValidationResult::warning(
                "⚠️ اخترت {$dayCount} أيام أسبوعياً، وهو أكثر من الموصى به ({$recommendedDaysPerWeek} أيام) بناءً على الهدف الشهري ({$monthlyTarget} جلسة/شهر). سيتم إنشاء جلسات أكثر من المعتاد.",
                [
                    'selected' => $dayCount,
                    'recommended' => $recommendedDaysPerWeek,
                    'max' => $maxDaysPerWeek,
                    'monthly_target' => $monthlyTarget
                ]
            );
        }

        return ValidationResult::success(
            "✓ عدد الأيام مناسب ({$dayCount} أيام أسبوعياً)",
            ['selected' => $dayCount, 'recommended' => $recommendedDaysPerWeek]
        );
    }

    public function validateSessionCount(int $count): ValidationResult
    {
        if ($count <= 0) {
            return ValidationResult::error('يجب أن يكون عدد الجلسات أكبر من صفر');
        }

        if ($count > 100) {
            return ValidationResult::error('لا يمكن جدولة أكثر من 100 جلسة دفعة واحدة لتجنب الأخطاء');
        }

        // Use actual monthly_sessions_count from circle (database has default of 8)
        $monthlyTarget = $this->circle->monthly_sessions_count;
        $recommendedCount = $monthlyTarget; // Default to one month

        if ($count < $monthlyTarget / 2) {
            return ValidationResult::warning(
                "⚠️ عدد الجلسات ({$count}) أقل من نصف الهدف الشهري ({$monthlyTarget}). قد تحتاج لجدولة المزيد قريباً."
            );
        }

        if ($count > $monthlyTarget * 3) {
            return ValidationResult::warning(
                "⚠️ عدد الجلسات ({$count}) كبير جداً (أكثر من 3 أشهر). قد ترغب في جدولة فترة أقصر."
            );
        }

        return ValidationResult::success(
            "✓ عدد الجلسات مناسب ({$count} جلسة)",
            ['count' => $count, 'monthly_target' => $monthlyTarget]
        );
    }

    public function validateDateRange(?Carbon $startDate, int $weeksAhead): ValidationResult
    {
        // Use academy timezone for accurate time comparison
        $timezone = AcademyContextService::getTimezone();
        $requestedStart = $startDate ?? Carbon::now($timezone);

        // Group circles are continuous, no end date restriction
        // Allow scheduling from today onwards (actual time validation happens during scheduling)
        $now = Carbon::now($timezone)->startOfDay();
        if ($requestedStart->startOfDay()->lessThan($now)) {
            return ValidationResult::error('لا يمكن جدولة جلسات في الماضي');
        }

        if ($weeksAhead > 52) {
            return ValidationResult::warning(
                "⚠️ تجاوزت سنة من الجدولة ({$weeksAhead} أسبوع). قد ترغب في جدولة فترة أقصر."
            );
        }

        return ValidationResult::success(
            "✓ نطاق التاريخ صحيح (ابتداءً من {$requestedStart->format('Y/m/d')})"
        );
    }

    public function validateWeeklyPacing(array $days, int $weeksAhead): ValidationResult
    {
        $daysPerWeek = count($days);
        $totalSessions = $daysPerWeek * $weeksAhead;

        // Use actual monthly_sessions_count from circle (database has default of 8)
        $monthlyTarget = $this->circle->monthly_sessions_count;
        $expectedMonths = ceil($weeksAhead / 4);
        $expectedTotal = $monthlyTarget * $expectedMonths;

        if ($totalSessions < $expectedTotal * 0.7) {
            return ValidationResult::warning(
                "⚠️ عدد الجلسات المجدولة ({$totalSessions}) أقل من المتوقع ({$expectedTotal}) لمدة {$expectedMonths} شهر."
            );
        }

        if ($totalSessions > $expectedTotal * 1.3) {
            return ValidationResult::warning(
                "⚠️ عدد الجلسات المجدولة ({$totalSessions}) أكثر من المتوقع ({$expectedTotal}) لمدة {$expectedMonths} شهر."
            );
        }

        return ValidationResult::success("✓ الجدول الزمني مناسب ({$totalSessions} جلسة)");
    }

    public function getRecommendations(): array
    {
        // Use actual monthly_sessions_count from circle (database has default of 8)
        $monthlyTarget = $this->circle->monthly_sessions_count;
        $recommendedDaysPerWeek = ceil($monthlyTarget / 4);

        // Include capacity information
        $maxStudents = $this->circle->max_students ?? 20;
        $currentStudents = $this->circle->students()->count();
        $availableSlots = max(0, $maxStudents - $currentStudents);

        return [
            'recommended_days' => $recommendedDaysPerWeek,
            'max_days' => $recommendedDaysPerWeek + 2,
            'monthly_target' => $monthlyTarget,
            'max_students' => $maxStudents,
            'current_students' => $currentStudents,
            'available_slots' => $availableSlots,
            'is_full' => $availableSlots <= 0,
            'reason' => "موصى به {$recommendedDaysPerWeek} أيام أسبوعياً لتحقيق {$monthlyTarget} جلسة شهرياً",
        ];
    }

    public function getSchedulingStatus(): array
    {
        $timezone = AcademyContextService::getTimezone();
        $now = Carbon::now($timezone);
        $oneMonthAhead = $now->copy()->addMonth();

        $futureSessionsCount = $this->circle->sessions()
            ->where('scheduled_at', '>', $now)
            ->where('scheduled_at', '<=', $oneMonthAhead)
            ->count();

        // Use actual monthly_sessions_count from circle (database has default of 8)
        $monthlyTarget = $this->circle->monthly_sessions_count;

        if ($futureSessionsCount === 0) {
            return [
                'status' => 'not_scheduled',
                'message' => 'لا توجد جلسات مجدولة في الشهر القادم',
                'color' => 'red',
                'can_schedule' => true,
                'urgent' => true,
            ];
        } elseif ($futureSessionsCount < $monthlyTarget * 0.5) {
            return [
                'status' => 'needs_scheduling',
                'message' => "جلسات قليلة ({$futureSessionsCount} فقط في الشهر القادم)",
                'color' => 'yellow',
                'can_schedule' => true,
                'urgent' => true,
            ];
        } else {
            return [
                'status' => 'actively_scheduled',
                'message' => "{$futureSessionsCount} جلسة مجدولة في الشهر القادم",
                'color' => 'green',
                'can_schedule' => true,
                'urgent' => false,
            ];
        }
    }

    /**
     * Get the maximum date that can be scheduled
     * Group circles are continuous, so return null (no limit)
     */
    public function getMaxScheduleDate(): ?Carbon
    {
        return null; // No end date for group circles
    }
}
