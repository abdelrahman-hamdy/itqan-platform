<?php

namespace App\Http\Resources\Api\V1\Attendance;

use App\Enums\AttendanceStatus;
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

            // Duration
            'total_duration_minutes' => $this->total_duration_minutes,
            'display_duration_minutes' => $this->display_duration_minutes,
            'session_duration_minutes' => $this->session_duration_minutes,

            // Status
            'attendance_status' => $this->attendance_status ? [
                'value' => $this->attendance_status,
                'label' => AttendanceStatus::tryFrom($this->attendance_status)?->label() ?? $this->attendance_status,
            ] : null,
            'attendance_percentage' => $this->attendance_percentage ? (float) $this->attendance_percentage : null,

            // Join/leave tracking
            'join_count' => $this->join_count,
            'leave_count' => $this->leave_count,
            'join_leave_cycles' => $this->join_leave_cycles,

            // Session timing
            'session_start_time' => $this->session_start_time?->toISOString(),
            'session_end_time' => $this->session_end_time?->toISOString(),

            // Subscription counting
            'counts_for_subscription' => $this->counts_for_subscription,
            'subscription_counted_at' => $this->subscription_counted_at?->toISOString(),

            // Calculation
            'is_calculated' => $this->is_calculated,
            'attendance_calculated_at' => $this->attendance_calculated_at?->toISOString(),

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
