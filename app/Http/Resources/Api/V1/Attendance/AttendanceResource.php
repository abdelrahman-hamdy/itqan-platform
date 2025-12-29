<?php

namespace App\Http\Resources\Api\V1\Attendance;

use App\Models\MeetingAttendance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Attendance Resource
 *
 * Meeting attendance record for all session types.
 *
 * @mixin MeetingAttendance
 */
class AttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // Session reference
            'session_id' => $this->session_id,
            'session_type' => $this->session_type,

            // User
            'user' => $this->whenLoaded('user', [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'user_type' => $this->user_type,
            ]),

            // Attendance timing
            'first_join_time' => $this->first_join_time?->toISOString(),
            'last_leave_time' => $this->last_leave_time?->toISOString(),
            'last_heartbeat_at' => $this->last_heartbeat_at?->toISOString(),

            // Duration
            'total_duration_minutes' => $this->total_duration_minutes,
            'session_duration_minutes' => $this->session_duration_minutes,

            // Status
            'attendance_status' => $this->attendance_status ? [
                'value' => $this->attendance_status,
                'label' => $this->getStatusLabel(),
            ] : null,
            'attendance_percentage' => $this->attendance_percentage ? (float) $this->attendance_percentage : null,

            // Join/leave tracking
            'join_count' => $this->join_count,
            'leave_count' => $this->leave_count,
            'join_leave_cycles' => $this->join_leave_cycles,

            // Session timing
            'session_start_time' => $this->session_start_time?->toISOString(),
            'session_end_time' => $this->session_end_time?->toISOString(),

            // Calculation
            'is_calculated' => $this->is_calculated,
            'attendance_calculated_at' => $this->attendance_calculated_at?->toISOString(),

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    /**
     * Get status label
     */
    protected function getStatusLabel(): ?string
    {
        if (!$this->attendance_status) {
            return null;
        }

        return match ($this->attendance_status) {
            'attended' => __('enums.attendance_status.attended'),
            'late' => __('enums.attendance_status.late'),
            'left' => __('enums.attendance_status.left'),
            'absent' => __('enums.attendance_status.absent'),
            default => $this->attendance_status,
        };
    }
}
