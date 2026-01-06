<?php

namespace App\Http\Resources\Api\V1\Session;

use App\Models\BaseSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base Session Resource
 *
 * Provides polymorphic support for all session types:
 * - QuranSession
 * - AcademicSession
 * - InteractiveCourseSession
 *
 * @mixin BaseSession
 */
class SessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'type' => $this->getMorphClass(),
            'session_code' => $this->resource->session_code,
            'title' => $this->resource->title,
            'description' => $this->resource->description,

            // Status
            'status' => [
                'value' => $this->resource->status->value,
                'label' => $this->resource->status->label(),
                'color' => $this->resource->status->color(),
                'icon' => $this->resource->status->icon(),
            ],

            // Scheduling
            'scheduled_at' => $this->resource->scheduled_at?->toISOString(),
            'started_at' => $this->resource->started_at?->toISOString(),
            'ended_at' => $this->resource->ended_at?->toISOString(),
            'duration_minutes' => $this->resource->duration_minutes,
            'actual_duration_minutes' => $this->resource->actual_duration_minutes,

            // Meeting data
            // SECURITY: meeting_password removed - use /api/v1/common/meetings/token endpoint instead
            'meeting' => $this->when($this->resource->meeting_link, [
                'link' => $this->resource->meeting_link,
                'room_name' => $this->resource->meeting_room_name,
                'platform' => $this->resource->meeting_platform,
                'expires_at' => $this->resource->meeting_expires_at?->toISOString(),
                'auto_generated' => $this->resource->meeting_auto_generated,
            ]),

            // Attendance
            'attendance_status' => $this->resource->attendance_status,
            'participants_count' => $this->resource->participants_count,

            // Feedback
            'session_notes' => $this->resource->session_notes,
            'teacher_feedback' => $this->resource->teacher_feedback,

            // Cancellation
            'cancelled_at' => $this->resource->cancelled_at?->toISOString(),
            'cancellation_reason' => $this->when($this->resource->cancelled_at, $this->resource->cancellation_reason),
            'cancellation_type' => $this->when($this->resource->cancelled_at, $this->resource->cancellation_type),

            // Rescheduling
            'rescheduled_from' => $this->resource->rescheduled_from?->toISOString(),
            'rescheduled_to' => $this->resource->rescheduled_to?->toISOString(),
            'reschedule_reason' => $this->when($this->resource->rescheduled_from, $this->resource->reschedule_reason),

            // Academy
            'academy' => $this->whenLoaded('academy', [
                'id' => $this->resource->academy?->id,
                'name' => $this->resource->academy?->name,
                'subdomain' => $this->resource->academy?->subdomain,
            ]),

            // Attendances (polymorphic)
            'attendances' => $this->whenLoaded('attendances', fn () => \App\Http\Resources\Api\V1\Attendance\AttendanceResource::collection($this->resource->attendances)
            ),

            // Timestamps
            'created_at' => $this->resource->created_at->toISOString(),
            'updated_at' => $this->resource->updated_at->toISOString(),
        ];
    }
}
