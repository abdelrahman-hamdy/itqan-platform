<?php

namespace App\Http\Controllers\Api\V1\ParentApi\Reports;

use App\Enums\SessionSubscriptionStatus;
use App\Enums\EnrollmentStatus;
use App\Models\CourseSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles Interactive Course progress reports for parents.
 *
 * Provides progress tracking and enrollment details for
 * children's interactive course subscriptions.
 */
class ParentInteractiveReportController extends BaseParentReportController
{
    /**
     * Get Interactive Course progress report for children.
     *
     * @param Request $request
     * @param int|null $childId
     * @return JsonResponse
     */
    public function progress(Request $request, ?int $childId = null): JsonResponse
    {
        $result = $this->validateParentAccess($request);
        if ($result instanceof JsonResponse) {
            return $result;
        }

        [$user, $parentProfile] = $result;

        $children = $this->getChildren($parentProfile->id, $childId);

        if ($children->isEmpty()) {
            return $this->notFound(__('No children found.'));
        }

        $reports = [];

        foreach ($children as $relationship) {
            $student = $relationship->student;
            $studentUserId = $this->getStudentUserId($student);

            $progress = $this->getCourseProgress($studentUserId);

            $reports[] = [
                'child' => $this->formatChildData($student),
                'courses' => $progress,
            ];
        }

        return $this->success([
            'reports' => $childId ? ($reports[0] ?? null) : $reports,
        ], __('Interactive course progress report retrieved successfully'));
    }

    /**
     * Get course subscription (enrollment) report.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function subscription(Request $request, int $id): JsonResponse
    {
        $result = $this->validateParentAccess($request);
        if ($result instanceof JsonResponse) {
            return $result;
        }

        [$user, $parentProfile] = $result;

        // Get all linked children's user IDs
        $childUserIds = $this->getChildren($parentProfile->id)
            ->map(fn($r) => $this->getStudentUserId($r->student))
            ->filter()
            ->toArray();

        $subscription = CourseSubscription::where('id', $id)
            ->whereIn('student_id', $childUserIds)
            ->with(['interactiveCourse', 'student.user'])
            ->first();

        if (!$subscription) {
            return $this->notFound(__('Course enrollment not found.'));
        }

        $report = $this->buildEnrollmentReport($subscription);

        return $this->success([
            'report' => $report,
        ], __('Course enrollment report retrieved successfully'));
    }

    /**
     * Get Course progress for a student.
     *
     * @param int $studentId
     * @return array
     */
    protected function getCourseProgress(int $studentId): array
    {
        $enrollments = CourseSubscription::where('student_id', $studentId)
            ->with(['interactiveCourse', 'recordedCourse'])
            ->get();

        $activeEnrollments = $enrollments->where('status', EnrollmentStatus::ENROLLED->value)->count();
        $completedEnrollments = $enrollments->where('status', EnrollmentStatus::COMPLETED->value)->count();

        $completedSessions = $enrollments->sum('completed_sessions');
        $totalSessions = $enrollments->sum(fn($e) => $e->interactiveCourse?->total_sessions ?? $e->recordedCourse?->total_lessons ?? 0);

        return [
            'active_enrollments' => $activeEnrollments,
            'completed_enrollments' => $completedEnrollments,
            'total_enrollments' => $enrollments->count(),
            'completed_sessions' => $completedSessions,
            'total_sessions' => $totalSessions,
            'average_progress' => $enrollments->count() > 0
                ? round($enrollments->avg('progress_percentage') ?? 0, 1)
                : 0,
        ];
    }

    /**
     * Build detailed enrollment report.
     *
     * @param CourseSubscription $subscription
     * @return array
     */
    protected function buildEnrollmentReport(CourseSubscription $subscription): array
    {
        $course = $subscription->interactiveCourse ?? $subscription->recordedCourse;
        $isInteractive = $subscription->interactiveCourse !== null;

        return [
            'enrollment' => [
                'id' => $subscription->id,
                'type' => $isInteractive ? 'interactive' : 'recorded',
                'status' => $subscription->status,
                'enrolled_at' => $subscription->created_at?->toDateString(),
                'expires_at' => $subscription->expires_at?->toDateString(),
            ],
            'course' => $course ? [
                'id' => $course->id,
                'title' => $course->title,
                'thumbnail' => $course->thumbnail
                    ? asset('storage/' . $course->thumbnail)
                    : null,
                'total_sessions' => $isInteractive ? $course->total_sessions : $course->total_lessons,
            ] : null,
            'child' => [
                'id' => $subscription->student?->id,
                'name' => $subscription->student?->user?->name ?? $subscription->student?->full_name,
            ],
            'progress' => [
                'completed_sessions' => $subscription->completed_sessions ?? 0,
                'total_sessions' => $isInteractive ? $course?->total_sessions : $course?->total_lessons,
                'progress_percentage' => $subscription->progress_percentage ?? 0,
                'last_accessed_at' => $subscription->last_accessed_at?->toISOString(),
            ],
            'certificate' => $subscription->certificate_issued ? [
                'issued' => true,
                'issued_at' => $subscription->certificate_issued_at?->toDateString(),
            ] : [
                'issued' => false,
                'eligible' => $subscription->progress_percentage >= 80,
            ],
        ];
    }
}
