<?php

namespace App\Services\Scheduling\Validators;

use App\Models\InteractiveCourse;
use App\Services\AcademyContextService;
use App\Services\Scheduling\ValidationResult;
use Carbon\Carbon;
use App\Enums\SessionStatus;

/**
 * Validator for Interactive Courses (Fixed session count, curriculum-based)
 */
class InteractiveCourseValidator implements ScheduleValidatorInterface
{
    public function __construct(
        private InteractiveCourse $course
    ) {}

    public function validateDaySelection(array $days): ValidationResult
    {
        $dayCount = count($days);

        if ($dayCount === 0) {
            return ValidationResult::error('يجب اختيار يوم واحد على الأقل');
        }

        if ($dayCount > 5) {
            return ValidationResult::error('لا يمكن اختيار أكثر من 5 أيام في الأسبوع للدورة التفاعلية');
        }

        // Calculate recommended days per week based on course duration
        // Use actual course configuration with fallbacks to prevent division by zero
        $totalSessions = $this->course->total_sessions ?? 16;
        $durationWeeks = max(1, $this->course->duration_weeks ?? 8);
        $recommendedDaysPerWeek = ceil($totalSessions / $durationWeeks);

        if ($dayCount > $recommendedDaysPerWeek + 1) {
            return ValidationResult::warning(
                "⚠️ اخترت {$dayCount} أيام أسبوعياً، وهو أكثر من الموصى به ({$recommendedDaysPerWeek} أيام) " .
                "بناءً على الدورة ({$totalSessions} جلسة خلال {$durationWeeks} أسبوع). " .
                "قد تنتهي الدورة قبل المدة المتوقعة.",
                [
                    'selected' => $dayCount,
                    'recommended' => $recommendedDaysPerWeek,
                    'total_sessions' => $totalSessions,
                    'duration_weeks' => $durationWeeks
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

        // Use actual course configuration with fallback
        $totalSessions = $this->course->total_sessions ?? 16;
        $scheduledSessions = $this->course->sessions()
            ->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::ONGOING->value, SessionStatus::COMPLETED->value])
            ->count();

        $remainingSessions = max(0, $totalSessions - $scheduledSessions);

        if ($remainingSessions <= 0) {
            return ValidationResult::error(
                'تم جدولة جميع جلسات الدورة بالفعل (' . $totalSessions . ' جلسة)'
            );
        }

        if ($count > $remainingSessions) {
            return ValidationResult::error(
                "لا يمكن جدولة {$count} جلسة. الجلسات المتبقية: {$remainingSessions} من أصل {$totalSessions}"
            );
        }

        if ($count < $remainingSessions * 0.3) {
            return ValidationResult::warning(
                "⚠️ تجدول {$count} جلسة فقط من أصل {$remainingSessions} متبقية. " .
                "قد تحتاج لجدولة المزيد قريباً."
            );
        }

        return ValidationResult::success(
            "✓ سيتم جدولة {$count} من {$remainingSessions} جلسة متبقية",
            [
                'count' => $count,
                'remaining' => $remainingSessions,
                'total' => $totalSessions
            ]
        );
    }

    public function validateDateRange(?Carbon $startDate, int $weeksAhead): ValidationResult
    {
        $timezone = AcademyContextService::getTimezone();
        $requestedStart = $startDate ?? Carbon::now($timezone);
        $requestedEnd = $requestedStart->copy()->addWeeks($weeksAhead);

        // Check if course has start and end dates
        $courseStartDate = $this->course->start_date;
        $courseEndDate = $this->course->end_date;

        if ($courseStartDate && $requestedStart->isBefore($courseStartDate)) {
            return ValidationResult::error(
                "لا يمكن جدولة جلسات قبل تاريخ بدء الدورة ({$courseStartDate->format('Y/m/d')})"
            );
        }

        if ($courseEndDate && $requestedEnd->isAfter($courseEndDate)) {
            return ValidationResult::warning(
                "⚠️ بعض الجلسات قد تتجاوز تاريخ انتهاء الدورة ({$courseEndDate->format('Y/m/d')}). " .
                "تأكد من توزيع الجلسات بشكل مناسب."
            );
        }

        // Allow scheduling from today onwards (actual time validation happens during scheduling)
        $now = Carbon::now($timezone)->startOfDay();
        if ($requestedStart->startOfDay()->lessThan($now)) {
            return ValidationResult::error('لا يمكن جدولة جلسات في الماضي');
        }

        // Check if requested period is reasonable for course duration
        // Use actual course configuration with fallback
        $durationWeeks = max(1, $this->course->duration_weeks ?? 8);
        if ($weeksAhead > $durationWeeks * 1.5) {
            return ValidationResult::warning(
                "⚠️ فترة الجدولة ({$weeksAhead} أسبوع) أطول من مدة الدورة المتوقعة ({$durationWeeks} أسبوع)"
            );
        }

        return ValidationResult::success(
            "✓ نطاق التاريخ صحيح (من {$requestedStart->format('Y/m/d')} إلى {$requestedEnd->format('Y/m/d')})"
        );
    }

    public function validateWeeklyPacing(array $days, int $weeksAhead): ValidationResult
    {
        $daysPerWeek = count($days);
        $totalSessionsToSchedule = $daysPerWeek * $weeksAhead;

        // Use actual course configuration with fallbacks
        $totalSessions = $this->course->total_sessions ?? 16;
        $scheduledSessions = $this->course->sessions()
            ->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::ONGOING->value, SessionStatus::COMPLETED->value])
            ->count();
        $remainingSessions = max(0, $totalSessions - $scheduledSessions);

        if ($totalSessionsToSchedule > $remainingSessions) {
            return ValidationResult::error(
                "الجدول المختار سينشئ {$totalSessionsToSchedule} جلسة، " .
                "لكن المتبقي فقط {$remainingSessions} جلسة"
            );
        }

        // Calculate optimal pacing with fallback to prevent division by zero
        $durationWeeks = max(1, $this->course->duration_weeks ?? 8);
        $recommendedPerWeek = ceil($totalSessions / $durationWeeks);

        if ($daysPerWeek > $recommendedPerWeek * 1.5) {
            return ValidationResult::warning(
                "⚠️ معدل {$daysPerWeek} جلسات أسبوعياً قد يكون سريعاً جداً. " .
                "الموصى به: {$recommendedPerWeek} جلسات أسبوعياً."
            );
        }

        if ($daysPerWeek < $recommendedPerWeek * 0.5) {
            return ValidationResult::warning(
                "⚠️ معدل {$daysPerWeek} جلسات أسبوعياً قد يكون بطيئاً. " .
                "قد تستغرق الدورة وقتاً أطول من المتوقع."
            );
        }

        return ValidationResult::success(
            "✓ الجدول الزمني مناسب ({$totalSessionsToSchedule} جلسة خلال {$weeksAhead} أسبوع)"
        );
    }

    public function getRecommendations(): array
    {
        // Use actual course configuration with fallbacks to prevent division by zero
        $totalSessions = $this->course->total_sessions ?? 16;
        $durationWeeks = max(1, $this->course->duration_weeks ?? 8);
        $scheduledSessions = $this->course->sessions()
            ->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::ONGOING->value, SessionStatus::COMPLETED->value])
            ->count();

        $remainingSessions = max(0, $totalSessions - $scheduledSessions);
        $recommendedDaysPerWeek = max(1, ceil($totalSessions / $durationWeeks));

        // Calculate weeks needed to complete remaining sessions
        $weeksNeeded = $remainingSessions > 0 ? ceil($remainingSessions / $recommendedDaysPerWeek) : 0;

        return [
            'recommended_days' => $recommendedDaysPerWeek,
            'max_days' => min($recommendedDaysPerWeek + 1, 5),
            'total_sessions' => $totalSessions,
            'remaining_sessions' => $remainingSessions,
            'duration_weeks' => $durationWeeks,
            'weeks_needed' => $weeksNeeded,
            'reason' => "موصى به {$recommendedDaysPerWeek} أيام أسبوعياً لإكمال {$remainingSessions} جلسة " .
                       "متبقية خلال {$weeksNeeded} أسبوع (من أصل {$totalSessions} جلسة في الدورة)",
        ];
    }

    public function getSchedulingStatus(): array
    {
        $totalSessions = max(1, $this->course->total_sessions ?? 16);
        $scheduledSessions = $this->course->sessions()
            ->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::ONGOING->value, SessionStatus::COMPLETED->value])
            ->count();

        $remainingSessions = max(0, $totalSessions - $scheduledSessions);
        $completionPercentage = ($scheduledSessions / $totalSessions) * 100;

        if ($remainingSessions === 0) {
            return [
                'status' => 'fully_scheduled',
                'message' => "تم جدولة جميع الجلسات ({$totalSessions}/{$totalSessions})",
                'color' => 'green',
                'can_schedule' => false,
                'urgent' => false,
                'progress' => 100,
            ];
        }

        // Check future scheduled sessions
        $futureScheduled = $this->course->sessions()
            ->where('status', SessionStatus::SCHEDULED->value)
            ->where('scheduled_at', '>', now())
            ->count();

        if ($futureScheduled === 0) {
            return [
                'status' => 'not_scheduled',
                'message' => "لا توجد جلسات مجدولة ({$scheduledSessions}/{$totalSessions} تمت)",
                'color' => 'red',
                'can_schedule' => true,
                'urgent' => true,
                'progress' => round($completionPercentage, 1),
            ];
        }

        if ($futureScheduled < $remainingSessions * 0.3) {
            return [
                'status' => 'needs_more_scheduling',
                'message' => "جلسات قليلة مجدولة ({$futureScheduled} جلسة قادمة، {$remainingSessions} متبقية)",
                'color' => 'yellow',
                'can_schedule' => true,
                'urgent' => true,
                'progress' => round($completionPercentage, 1),
            ];
        }

        return [
            'status' => 'partially_scheduled',
            'message' => "{$futureScheduled} جلسة قادمة من {$remainingSessions} متبقية",
            'color' => 'blue',
            'can_schedule' => true,
            'urgent' => false,
            'progress' => round($completionPercentage, 1),
        ];
    }

    /**
     * Get the maximum date that can be scheduled
     * Returns the course end date
     */
    public function getMaxScheduleDate(): ?Carbon
    {
        return $this->course->end_date;
    }
}
