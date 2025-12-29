<?php

namespace App\Http\Resources\Api\V1\Homework;

use App\Models\HomeworkSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Homework Submission Resource
 *
 * Student homework submission data.
 *
 * @mixin HomeworkSubmission
 */
class HomeworkSubmissionResource extends JsonResource
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

            // Homework reference
            'homework_id' => $this->resource->homework_id,

            // Student
            'student' => $this->whenLoaded('student', [
                'id' => $this->resource->student?->id,
                'name' => $this->resource->student?->user?->name,
                'student_code' => $this->resource->student?->student_code,
            ]),

            // Submission content
            'content' => $this->resource->content,
            'notes' => $this->resource->notes,

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

            // Grading
            'grade' => $this->resource->grade,
            'feedback' => $this->resource->feedback,
            'graded_at' => $this->resource->graded_at?->toISOString(),
            'graded_by' => $this->whenLoaded('gradedBy', [
                'id' => $this->resource->gradedBy?->id,
                'name' => $this->resource->gradedBy?->name,
            ]),

            // Timing
            'submitted_at' => $this->resource->submitted_at?->toISOString(),
            'is_late' => $this->resource->is_late,

            // Timestamps
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
