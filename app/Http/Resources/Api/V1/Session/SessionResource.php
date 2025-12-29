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
            'id' => $this->id,
            'type' => $this->getMorphClass(),
            'session_code' => $this->session_code,
            'title' => $this->title,
            'description' => $this->description,

            // Status
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
                'icon' => $this->status->icon(),
            ],

            // Scheduling
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'duration_minutes' => $this->duration_minutes,
            'actual_duration_minutes' => $this->actual_duration_minutes,

            // Meeting data
            'meeting' => $this->when($this->meeting_link, [
                'link' => $this->meeting_link,
                'room_name' => $this->meeting_room_name,
                'platform' => $this->meeting_platform,
                'password' => $this->when($this->meeting_password, $this->meeting_password),
                'expires_at' => $this->meeting_expires_at?->toISOString(),
                'auto_generated' => $this->meeting_auto_generated,
            ]),

            // Attendance
            'attendance_status' => $this->attendance_status,
            'participants_count' => $this->participants_count,

            // Feedback
            'session_notes' => $this->session_notes,
            'teacher_feedback' => $this->teacher_feedback,

            // Cancellation
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancellation_reason' => $this->when($this->cancelled_at, $this->cancellation_reason),
            'cancellation_type' => $this->when($this->cancelled_at, $this->cancellation_type),

            // Rescheduling
            'rescheduled_from' => $this->rescheduled_from?->toISOString(),
            'rescheduled_to' => $this->rescheduled_to?->toISOString(),
            'reschedule_reason' => $this->when($this->rescheduled_from, $this->reschedule_reason),

            // Academy
            'academy' => $this->whenLoaded('academy', [
                'id' => $this->academy?->id,
                'name' => $this->academy?->name,
                'subdomain' => $this->academy?->subdomain,
            ]),

            // Attendances (polymorphic)
            'attendances' => $this->whenLoaded('attendances', fn() =>
                \App\Http\Resources\Api\V1\Attendance\AttendanceResource::collection($this->attendances)
            ),

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
