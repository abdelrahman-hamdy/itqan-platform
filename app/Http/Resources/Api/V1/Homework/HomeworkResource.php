<?php

namespace App\Http\Resources\Api\V1\Homework;

use App\Models\Homework;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Homework Resource
 *
 * Homework assignment data for all session types.
 *
 * @mixin Homework
 */
class HomeworkResource extends JsonResource
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

            // Assignment details
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'instructions' => $this->resource->instructions,

            // Files
            'attachment_url' => $this->when(
                $this->resource->attachment_path,
                fn() => $this->getFileUrl($this->resource->attachment_path)
            ),

            // Status
            'status' => [
                'value' => $this->resource->status->value,
                'label' => $this->resource->status->label(),
                'color' => $this->resource->status->color(),
            ],

            // Assignable (polymorphic - session)
            'session' => [
                'type' => $this->resource->assignable_type,
                'id' => $this->resource->assignable_id,
            ],

            // Due date
            'due_date' => $this->resource->due_date?->toISOString(),

            // Student
            'student' => $this->whenLoaded('student', [
                'id' => $this->resource->student?->id,
                'name' => $this->resource->student?->user?->name,
                'student_code' => $this->resource->student?->student_code,
            ]),

            // Teacher
            'teacher' => $this->whenLoaded('assignedBy', [
                'id' => $this->resource->assignedBy?->id,
                'name' => $this->resource->assignedBy?->name,
            ]),

            // Submission
            'submission' => $this->whenLoaded('submission', function () {
                return new HomeworkSubmissionResource($this->resource->submission);
            }),

            // Grading
            'grade' => $this->resource->grade,
            'max_grade' => $this->resource->max_grade,
            'feedback' => $this->resource->feedback,
            'graded_at' => $this->resource->graded_at?->toISOString(),
            'graded_by' => $this->whenLoaded('gradedBy', [
                'id' => $this->resource->gradedBy?->id,
                'name' => $this->resource->gradedBy?->name,
            ]),

            // Timestamps
            'assigned_at' => $this->resource->assigned_at?->toISOString(),
            'created_at' => $this->resource->created_at->toISOString(),
            'updated_at' => $this->resource->updated_at->toISOString(),
        ];
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
