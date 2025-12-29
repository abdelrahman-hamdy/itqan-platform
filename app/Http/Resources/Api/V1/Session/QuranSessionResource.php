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
            'session_type' => $this->session_type,

            // Teacher
            'teacher' => $this->whenLoaded('quranTeacher', function () {
                return new TeacherListResource($this->quranTeacher);
            }),

            // Student (for individual sessions)
            'student' => $this->when(
                $this->session_type === 'individual' && $this->relationLoaded('student'),
                fn() => new StudentListResource($this->student)
            ),

            // Circle information
            'circle' => $this->whenLoaded('circle', [
                'id' => $this->circle?->id,
                'name' => $this->circle?->circle_name,
                'type' => 'group',
            ]),
            'individual_circle' => $this->whenLoaded('individualCircle', [
                'id' => $this->individualCircle?->id,
                'name' => $this->individualCircle?->circle_name,
                'type' => 'individual',
            ]),

            // Quran progress
            'quran_progress' => [
                'current_surah' => $this->current_surah,
                'current_page' => $this->current_page,
            ],

            // Lesson content
            'lesson_content' => $this->lesson_content,

            // Homework
            'homework' => [
                'assigned' => $this->homework_assigned,
                'details' => $this->when($this->homework_assigned, $this->homework_details),
            ],

            // Subscription
            'subscription' => $this->whenLoaded('quranSubscription', [
                'id' => $this->quranSubscription?->id,
                'subscription_code' => $this->quranSubscription?->subscription_code,
                'status' => [
                    'value' => $this->quranSubscription?->status?->value,
                    'label' => $this->quranSubscription?->status?->label(),
                ],
            ]),

            // Subscription counting
            'subscription_counted' => $this->subscription_counted,
            'monthly_session_number' => $this->monthly_session_number,
        ]);
    }
}
