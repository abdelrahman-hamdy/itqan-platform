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
        $baseArray = parent::toArray($request);

        return array_merge($baseArray, [
            // Scheduling - keep API keys for backward compatibility
            'scheduled_date' => $this->resource->scheduled_at?->format('Y-m-d'),
            'scheduled_time' => $this->resource->scheduled_at?->format('H:i'),

            // Course
            'course' => $this->whenLoaded('course', [
                'id' => $this->resource->course?->id,
                'title' => $this->resource->course?->title,
                'title_en' => $this->resource->course?->title_en,
                'course_code' => $this->resource->course?->course_code,
                'status' => [
                    'value' => $this->resource->course?->status?->value,
                    'label' => $this->resource->course?->status?->label(),
                ],
                'enrollments_count' => $this->resource->course?->enrollments_count,
            ]),

            // Teacher (via course)
            'teacher' => $this->when(
                $this->relationLoaded('course') && $this->resource->course?->relationLoaded('assignedTeacher'),
                fn () => new TeacherListResource($this->resource->course->assignedTeacher)
            ),

            // Session content
            'lesson_title' => $this->resource->lesson_title,
            'lesson_description' => $this->resource->lesson_description,
            'content_materials' => $this->resource->content_materials,

            // Homework
            'homework' => [
                'assigned' => $this->resource->homework_assigned,
                'description' => $this->when($this->resource->homework_assigned, $this->resource->homework_description),
                'due_date' => $this->when($this->resource->homework_assigned, $this->resource->homework_due_date?->format('Y-m-d')),
                'file_url' => $this->when(
                    $this->resource->homework_assigned && $this->resource->homework_file,
                    fn () => $this->getFileUrl($this->resource->homework_file)
                ),
            ],

            // Session metadata
            'session_order' => $this->resource->session_order,
        ]);
    }

    /**
     * Get file URL
     */
    protected function getFileUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return asset('storage/'.$path);
    }
}
