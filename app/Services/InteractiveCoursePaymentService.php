<?php

namespace App\Services;

use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\InteractiveCourseSession;
use Illuminate\Support\Collection;
use App\Enums\SessionStatus;

class InteractiveCoursePaymentService
{
    /**
     * Calculate teacher payout based on payment type
     *
     * @param InteractiveCourse $course
     * @param array $options Additional options (e.g., specific date range)
     * @return float
     */
    public function calculateTeacherPayout(InteractiveCourse $course, array $options = []): float
    {
        return match($course->payment_type) {
            'fixed_amount' => $this->calculateFixedAmount($course),
            'per_student' => $this->calculatePerStudentAmount($course, $options),
            'per_session' => $this->calculatePerSessionAmount($course, $options),
            default => 0.00,
        };
    }

    /**
     * Calculate fixed amount payment
     *
     * @param InteractiveCourse $course
     * @return float
     */
    protected function calculateFixedAmount(InteractiveCourse $course): float
    {
        return (float) ($course->teacher_fixed_amount ?? 0.00);
    }

    /**
     * Calculate per-student payment
     *
     * @param InteractiveCourse $course
     * @param array $options
     * @return float
     */
    protected function calculatePerStudentAmount(InteractiveCourse $course, array $options = []): float
    {
        $enrollmentCount = $this->getEnrollmentCount($course, $options);
        $amountPerStudent = (float) ($course->amount_per_student ?? 0.00);

        return $enrollmentCount * $amountPerStudent;
    }

    /**
     * Calculate per-session payment
     *
     * @param InteractiveCourse $course
     * @param array $options
     * @return float
     */
    protected function calculatePerSessionAmount(InteractiveCourse $course, array $options = []): float
    {
        $sessionsCount = $this->getCompletedSessionsCount($course, $options);
        $amountPerSession = (float) ($course->amount_per_session ?? 0.00);

        return $sessionsCount * $amountPerSession;
    }

    /**
     * Get enrollment count for a course
     *
     * @param InteractiveCourse $course
     * @param array $options
     * @return int
     */
    protected function getEnrollmentCount(InteractiveCourse $course, array $options = []): int
    {
        $query = $course->enrollments()->where('enrollment_status', 'enrolled');

        // If date range is specified, filter by enrollment date
        if (isset($options['from_date'])) {
            $query->where('enrolled_at', '>=', $options['from_date']);
        }

        if (isset($options['to_date'])) {
            $query->where('enrolled_at', '<=', $options['to_date']);
        }

        return $query->count();
    }

    /**
     * Get completed sessions count
     *
     * @param InteractiveCourse $course
     * @param array $options
     * @return int
     */
    protected function getCompletedSessionsCount(InteractiveCourse $course, array $options = []): int
    {
        $query = $course->sessions()->where('status', SessionStatus::COMPLETED->value);

        // If date range is specified, filter by session date
        if (isset($options['from_date'])) {
            $query->whereDate('scheduled_at', '>=', $options['from_date']);
        }

        if (isset($options['to_date'])) {
            $query->whereDate('scheduled_at', '<=', $options['to_date']);
        }

        return $query->count();
    }

    /**
     * Calculate total student fees for a course
     *
     * @param InteractiveCourse $course
     * @return float
     */
    public function calculateTotalStudentRevenue(InteractiveCourse $course): float
    {
        $enrollmentCount = $course->getCurrentEnrollmentCount();
        $studentPrice = (float) ($course->student_price ?? 0.00);
        $enrollmentFee = $course->is_enrollment_fee_required
            ? (float) ($course->enrollment_fee ?? 0.00)
            : 0.00;

        $totalCourseFees = $enrollmentCount * $studentPrice;
        $totalEnrollmentFees = $enrollmentCount * $enrollmentFee;

        return $totalCourseFees + $totalEnrollmentFees;
    }

    /**
     * Calculate academy profit (revenue - teacher payment)
     *
     * @param InteractiveCourse $course
     * @param array $options
     * @return float
     */
    public function calculateAcademyProfit(InteractiveCourse $course, array $options = []): float
    {
        $totalRevenue = $this->calculateTotalStudentRevenue($course);
        $teacherPayout = $this->calculateTeacherPayout($course, $options);

        return $totalRevenue - $teacherPayout;
    }

    /**
     * Get payment breakdown for a course
     *
     * @param InteractiveCourse $course
     * @param array $options
     * @return array
     */
    public function getPaymentBreakdown(InteractiveCourse $course, array $options = []): array
    {
        $enrollmentCount = $this->getEnrollmentCount($course, $options);
        $completedSessionsCount = $this->getCompletedSessionsCount($course, $options);
        $totalRevenue = $this->calculateTotalStudentRevenue($course);
        $teacherPayout = $this->calculateTeacherPayout($course, $options);
        $academyProfit = $totalRevenue - $teacherPayout;

        return [
            'course_id' => $course->id,
            'course_code' => $course->course_code,
            'course_title' => $course->title,
            'payment_type' => $course->payment_type,
            'payment_type_arabic' => $course->payment_type_in_arabic,

            // Student metrics
            'enrolled_students_count' => $enrollmentCount,
            'student_price' => (float) ($course->student_price ?? 0.00),
            'enrollment_fee' => (float) ($course->enrollment_fee ?? 0.00),
            'enrollment_fee_required' => $course->is_enrollment_fee_required,

            // Session metrics
            'total_sessions' => $course->total_sessions,
            'completed_sessions' => $completedSessionsCount,
            'scheduled_sessions' => $course->sessions()->where('status', SessionStatus::SCHEDULED->value)->count(),

            // Teacher payment details
            'teacher_payment_config' => [
                'fixed_amount' => (float) ($course->teacher_fixed_amount ?? 0.00),
                'amount_per_student' => (float) ($course->amount_per_student ?? 0.00),
                'amount_per_session' => (float) ($course->amount_per_session ?? 0.00),
            ],

            // Financial summary
            'total_student_revenue' => round($totalRevenue, 2),
            'teacher_payout' => round($teacherPayout, 2),
            'academy_profit' => round($academyProfit, 2),
            'profit_margin_percentage' => $totalRevenue > 0
                ? round(($academyProfit / $totalRevenue) * 100, 2)
                : 0.00,

            // Date range (if provided)
            'calculation_period' => [
                'from_date' => $options['from_date'] ?? null,
                'to_date' => $options['to_date'] ?? null,
            ],

            'calculated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Calculate total enrollment cost for a student
     *
     * @param InteractiveCourse $course
     * @return float
     */
    public function calculateStudentEnrollmentCost(InteractiveCourse $course): float
    {
        $courseFee = (float) ($course->student_price ?? 0.00);
        $enrollmentFee = $course->is_enrollment_fee_required
            ? (float) ($course->enrollment_fee ?? 0.00)
            : 0.00;

        return $courseFee + $enrollmentFee;
    }

    /**
     * Check if course is financially viable
     * (Returns true if academy will make profit based on current enrollments)
     *
     * @param InteractiveCourse $course
     * @return bool
     */
    public function isCourseViable(InteractiveCourse $course): bool
    {
        $profit = $this->calculateAcademyProfit($course);
        return $profit > 0;
    }

    /**
     * Calculate minimum students needed for profitability
     *
     * @param InteractiveCourse $course
     * @return int|null
     */
    public function calculateMinimumStudentsForProfit(InteractiveCourse $course): ?int
    {
        if ($course->payment_type !== 'per_student') {
            return null; // Only applicable for per-student payment
        }

        $studentPrice = (float) ($course->student_price ?? 0.00);
        $enrollmentFee = $course->is_enrollment_fee_required
            ? (float) ($course->enrollment_fee ?? 0.00)
            : 0.00;
        $amountPerStudent = (float) ($course->amount_per_student ?? 0.00);

        $revenuePerStudent = $studentPrice + $enrollmentFee;

        if ($revenuePerStudent <= $amountPerStudent) {
            return null; // Will never be profitable
        }

        // For break-even: total_revenue = teacher_payout
        // students * revenue_per_student = students * amount_per_student
        // Minimum for profit is 1 student (assuming revenue > payout per student)

        return 1;
    }

    /**
     * Get payment summary for teacher dashboard
     *
     * @param int $teacherId
     * @param array $options
     * @return Collection
     */
    public function getTeacherPaymentSummary(int $teacherId, array $options = []): Collection
    {
        $courses = InteractiveCourse::where('assigned_teacher_id', $teacherId)
            ->when(isset($options['status']), function ($query) use ($options) {
                return $query->where('status', $options['status']);
            })
            ->get();

        return $courses->map(function ($course) use ($options) {
            return $this->getPaymentBreakdown($course, $options);
        });
    }
}
