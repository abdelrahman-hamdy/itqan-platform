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
            'id' => $this->id,
            'type' => $isIndividual ? 'individual' : 'group',
            'circle_name' => $this->circle_name,
            'description' => $this->description,

            // Teacher
            'teacher' => $this->whenLoaded('quranTeacher', function () {
                return new TeacherListResource($this->quranTeacher);
            }),

            // Student (for individual circles)
            'student' => $this->when(
                $isIndividual && $this->relationLoaded('student'),
                fn() => [
                    'id' => $this->student?->id,
                    'name' => $this->student?->user?->name,
                    'student_code' => $this->student?->student_code,
                ]
            ),

            // Schedule
            'schedule' => $this->when(!$isIndividual, [
                'weekly_days' => $this->weekly_days,
                'session_time' => $this->session_time,
                'session_duration' => $this->session_duration,
            ]),

            // Capacity (for group circles)
            'capacity' => $this->when(!$isIndividual, [
                'max_students' => $this->max_students,
                'current_students' => $this->current_students,
                'available_spots' => $this->max_students - $this->current_students,
            ]),

            // Status
            'is_active' => $this->is_active,
            'status' => $this->when(!$isIndividual, [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
            ]),

            // Dates
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),

            // Subscription (for individual circles)
            'subscription' => $this->when(
                $isIndividual && $this->relationLoaded('subscription'),
                fn() => [
                    'id' => $this->subscription?->id,
                    'subscription_code' => $this->subscription?->subscription_code,
                    'status' => [
                        'value' => $this->subscription?->status?->value,
                        'label' => $this->subscription?->status?->label(),
                    ],
                ]
            ),

            // Sessions count
            'sessions_count' => $this->when(
                $this->relationLoaded('sessions'),
                fn() => $this->sessions->count()
            ),
            'completed_sessions_count' => $this->when(
                $this->relationLoaded('sessions'),
                fn() => $this->sessions->where('status', 'completed')->count()
            ),

            // Academy
            'academy' => $this->whenLoaded('academy', [
                'id' => $this->academy?->id,
                'name' => $this->academy?->name,
                'subdomain' => $this->academy?->subdomain,
            ]),

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
