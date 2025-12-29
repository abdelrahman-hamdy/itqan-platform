<?php

namespace App\Http\Resources\Api\V1\Session;

use App\Http\Resources\Api\V1\Teacher\TeacherListResource;
use App\Models\InteractiveCourseSession;
use Illuminate\Http\Request;

/**
 * Interactive Course Session Resource
 *
 * Extends base session with interactive course-specific data:
 * - Course information
 * - Teacher information
 * - Enrollment count
 * - Homework assignments
 * - Recording information
 *
 * @mixin InteractiveCourseSession
 */
class InteractiveSessionResource extends SessionResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Note: InteractiveCourseSession uses scheduled_date + scheduled_time
        // instead of scheduled_at
        $baseArray = parent::toArray($request);

        // Replace scheduled_at with date/time fields
        unset($baseArray['scheduled_at']);

        return array_merge($baseArray, [
            // Scheduling (different from base sessions)
            'scheduled_date' => $this->scheduled_date?->format('Y-m-d'),
            'scheduled_time' => $this->scheduled_time,

            // Course
            'course' => $this->whenLoaded('course', [
                'id' => $this->course?->id,
                'title' => $this->course?->title,
                'title_en' => $this->course?->title_en,
                'course_code' => $this->course?->course_code,
                'status' => [
                    'value' => $this->course?->status?->value,
                    'label' => $this->course?->status?->label(),
                ],
                'enrollments_count' => $this->course?->enrollments_count,
            ]),

            // Teacher (via course)
            'teacher' => $this->when(
                $this->relationLoaded('course') && $this->course?->relationLoaded('assignedTeacher'),
                fn() => new TeacherListResource($this->course->assignedTeacher)
            ),

            // Session content
            'lesson_title' => $this->lesson_title,
            'lesson_description' => $this->lesson_description,
            'content_materials' => $this->content_materials,

            // Homework
            'homework' => [
                'assigned' => $this->homework_assigned,
                'description' => $this->when($this->homework_assigned, $this->homework_description),
                'due_date' => $this->when($this->homework_assigned, $this->homework_due_date?->format('Y-m-d')),
                'file_url' => $this->when(
                    $this->homework_assigned && $this->homework_file,
                    fn() => $this->getFileUrl($this->homework_file)
                ),
            ],

            // Recording
            'recording' => [
                'enabled' => $this->recording_enabled,
                'url' => $this->when($this->recording_url, $this->recording_url),
                'available_until' => $this->recording_available_until?->toISOString(),
            ],

            // Session metadata
            'session_order' => $this->session_order,
        ]);
    }

    /**
     * Get file URL
     */
    protected function getFileUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return asset('storage/' . $path);
    }
}
