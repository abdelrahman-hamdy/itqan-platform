<?php

namespace App\Http\Resources\Api\V1\Attendance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Attendance Collection Resource
 *
 * Collection wrapper for attendance resources with statistics
 */
class AttendanceCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->collection->count(),
                'statuses' => $this->getStatusBreakdown(),
                'statistics' => [
                    'average_duration' => $this->getAverageDuration(),
                    'total_duration' => $this->getTotalDuration(),
                    'attendance_rate' => $this->getAttendanceRate(),
                ],
            ],
        ];
    }

    /**
     * Get breakdown of attendance by status
     */
    protected function getStatusBreakdown(): array
    {
        return $this->collection->groupBy(fn ($attendance) => $attendance->attendance_status ?? 'N/A')
            ->map(fn ($group) => $group->count())
            ->toArray();
    }

    /**
     * Get average duration in minutes
     */
    protected function getAverageDuration(): ?float
    {
        $durations = $this->collection->pluck('total_duration_minutes')->filter();

        if ($durations->isEmpty()) {
            return null;
        }

        return round($durations->average(), 2);
    }

    /**
     * Get total duration in minutes
     */
    protected function getTotalDuration(): int
    {
        return $this->collection->sum(fn ($attendance) => $attendance->total_duration_minutes ?? 0);
    }

    /**
     * Get attendance rate percentage
     */
    protected function getAttendanceRate(): ?float
    {
        $total = $this->collection->count();

        if ($total === 0) {
            return null;
        }

        $attended = $this->collection->whereIn('attendance_status', ['attended', 'late'])->count();

        return round(($attended / $total) * 100, 2);
    }
}
