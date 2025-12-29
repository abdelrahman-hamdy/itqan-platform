<?php

namespace App\Http\Resources\Api\V1\Circle;

use App\Http\Resources\Api\V1\Teacher\TeacherListResource;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Circle Resource (Full)
 *
 * Complete Quran circle data (both group and individual).
 *
 * @mixin QuranCircle|QuranIndividualCircle
 */
class CircleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isIndividual = $this->resource instanceof QuranIndividualCircle;

        return [
            'id' => $this->resource->id,
            'type' => $isIndividual ? 'individual' : 'group',
            'circle_name' => $this->resource->circle_name,
            'description' => $this->resource->description,

            // Teacher
            'teacher' => $this->whenLoaded('quranTeacher', function () {
                return new TeacherListResource($this->resource->quranTeacher);
            }),

            // Student (for individual circles)
            'student' => $this->when(
                $isIndividual && $this->resource->relationLoaded('student'),
                fn() => [
                    'id' => $this->resource->student?->id,
                    'name' => $this->resource->student?->user?->name,
                    'student_code' => $this->resource->student?->student_code,
                ]
            ),

            // Schedule
            'schedule' => $this->when(!$isIndividual, [
                'weekly_days' => $this->resource->weekly_days,
                'session_time' => $this->resource->session_time,
                'session_duration' => $this->resource->session_duration,
            ]),

            // Capacity (for group circles)
            'capacity' => $this->when(!$isIndividual, [
                'max_students' => $this->resource->max_students,
                'current_students' => $this->resource->current_students,
                'available_spots' => $this->resource->max_students - $this->resource->current_students,
            ]),

            // Status
            'is_active' => $this->resource->is_active,
            'status' => $this->when(!$isIndividual, [
                'value' => $this->resource->status?->value,
                'label' => $this->resource->status?->label(),
            ]),

            // Dates
            'start_date' => $this->resource->start_date?->format('Y-m-d'),
            'end_date' => $this->resource->end_date?->format('Y-m-d'),

            // Subscription (for individual circles)
            'subscription' => $this->when(
                $isIndividual && $this->resource->relationLoaded('subscription'),
                fn() => [
                    'id' => $this->resource->subscription?->id,
                    'subscription_code' => $this->resource->subscription?->subscription_code,
                    'status' => [
                        'value' => $this->resource->subscription?->status?->value,
                        'label' => $this->resource->subscription?->status?->label(),
                    ],
                ]
            ),

            // Sessions count
            'sessions_count' => $this->when(
                $this->resource->relationLoaded('sessions'),
                fn() => $this->resource->sessions->count()
            ),
            'completed_sessions_count' => $this->when(
                $this->resource->relationLoaded('sessions'),
                fn() => $this->resource->sessions->where('status', 'completed')->count()
            ),

            // Academy
            'academy' => $this->whenLoaded('academy', [
                'id' => $this->resource->academy?->id,
                'name' => $this->resource->academy?->name,
                'subdomain' => $this->resource->academy?->subdomain,
            ]),

            // Timestamps
            'created_at' => $this->resource->created_at->toISOString(),
            'updated_at' => $this->resource->updated_at->toISOString(),
        ];
    }
}
