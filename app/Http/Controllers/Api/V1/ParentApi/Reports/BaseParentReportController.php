<?php

namespace App\Http\Controllers\Api\V1\ParentApi\Reports;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\ParentStudentRelationship;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * Base controller for parent report operations.
 *
 * Provides shared functionality for generating and formatting
 * progress reports for parent's linked children.
 */
abstract class BaseParentReportController extends Controller
{
    use ApiResponses;

    /**
     * Get all linked children for a parent.
     *
     * @param  int|null  $childId  Optional filter for specific child
     * @return \Illuminate\Support\Collection
     */
    protected function getChildren(int $parentProfileId, ?int $childId = null)
    {
        $query = ParentStudentRelationship::where('parent_id', $parentProfileId)
            ->with('student.user');

        if ($childId) {
            $query->where('student_id', $childId);
        }

        return $query->get();
    }

    /**
     * Get student user ID from student model.
     *
     * @param  mixed  $student
     */
    protected function getStudentUserId($student): ?int
    {
        return $student->user?->id ?? $student->id;
    }

    /**
     * Format child data for reports.
     *
     * @param  mixed  $student
     */
    protected function formatChildData($student): array
    {
        return [
            'id' => $student->id,
            'name' => $student->full_name,
            'avatar' => $student->avatar ? asset('storage/'.$student->avatar) : null,
            'grade_level' => $student->gradeLevel?->name,
        ];
    }

    /**
     * Get date range from request with defaults.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array [startDate, endDate]
     */
    protected function getDateRange($request): array
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : now()->subDays(30);

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : now();

        return [$startDate, $endDate];
    }

    /**
     * Calculate attendance rate from reports.
     *
     * @param  \Illuminate\Support\Collection  $reports
     */
    protected function calculateAttendanceRate($reports): float
    {
        $total = $reports->count();

        if ($total === 0) {
            return 0;
        }

        $attended = $reports->filter(function ($report) {
            $status = $report->attendance_status;
            if ($status instanceof \BackedEnum) {
                $status = $status->value;
            }

            return in_array($status, [AttendanceStatus::ATTENDED->value, AttendanceStatus::LATE->value]);
        })->count();

        return round(($attended / $total) * 100, 1);
    }

    /**
     * Count attended sessions from reports.
     *
     * @param  \Illuminate\Support\Collection  $reports
     */
    protected function countAttended($reports): int
    {
        return $reports->filter(function ($report) {
            $status = $report->attendance_status;
            if ($status instanceof \BackedEnum) {
                $status = $status->value;
            }

            return in_array($status, [AttendanceStatus::ATTENDED->value, AttendanceStatus::LATE->value]);
        })->count();
    }

    /**
     * Count missed sessions from reports.
     *
     * @param  \Illuminate\Support\Collection  $reports
     */
    protected function countMissed($reports): int
    {
        return $reports->filter(function ($report) {
            $status = $report->attendance_status;
            if ($status instanceof \BackedEnum) {
                $status = $status->value;
            }

            return $status === AttendanceStatus::ABSENT->value;
        })->count();
    }

    /**
     * Validate parent access.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|JsonResponse Returns [user, parentProfile] or error response
     */
    protected function validateParentAccess($request)
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, ['code' => 'PARENT_PROFILE_NOT_FOUND']);
        }

        return [$user, $parentProfile];
    }
}
