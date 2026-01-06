<?php

namespace App\Http\Resources\Api\V1\Session;

use App\Http\Resources\Api\V1\Student\StudentListResource;
use App\Http\Resources\Api\V1\Teacher\TeacherListResource;
use App\Models\QuranSession;
use Illuminate\Http\Request;

/**
 * Quran Session Resource
 *
 * Extends base session with Quran-specific data:
 * - Teacher information
 * - Student information (for individual sessions)
 * - Quran progress (surah, page)
 * - Homework assignments
 *
 * @mixin QuranSession
 */
class QuranSessionResource extends SessionResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            // Session type
            'session_type' => $this->resource->session_type,

            // Teacher
            'teacher' => $this->whenLoaded('quranTeacher', function () {
                return new TeacherListResource($this->resource->quranTeacher);
            }),

            // Student (for individual sessions)
            'student' => $this->when(
                $this->resource->session_type === 'individual' && $this->relationLoaded('student'),
                fn () => new StudentListResource($this->resource->student)
            ),

            // Circle information
            'circle' => $this->whenLoaded('circle', [
                'id' => $this->resource->circle?->id,
                'name' => $this->resource->circle?->circle_name,
                'type' => 'group',
            ]),
            'individual_circle' => $this->whenLoaded('individualCircle', [
                'id' => $this->resource->individualCircle?->id,
                'name' => $this->resource->individualCircle?->circle_name,
                'type' => 'individual',
            ]),

            // Quran progress
            'quran_progress' => [],

            // Lesson content
            'lesson_content' => $this->resource->lesson_content,

            // Homework
            'homework' => [
                'assigned' => $this->resource->homework_assigned,
                'details' => $this->when($this->resource->homework_assigned, $this->resource->homework_details),
            ],

            // Subscription
            'subscription' => $this->whenLoaded('quranSubscription', [
                'id' => $this->resource->quranSubscription?->id,
                'subscription_code' => $this->resource->quranSubscription?->subscription_code,
                'status' => [
                    'value' => $this->resource->quranSubscription?->status?->value,
                    'label' => $this->resource->quranSubscription?->status?->label(),
                ],
            ]),

            // Subscription counting
            'subscription_counted' => $this->resource->subscription_counted,
            'monthly_session_number' => $this->resource->monthly_session_number,
        ]);
    }
}
