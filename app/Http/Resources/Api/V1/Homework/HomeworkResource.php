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
            'id' => $this->id,

            // Assignment details
            'title' => $this->title,
            'description' => $this->description,
            'instructions' => $this->instructions,

            // Files
            'attachment_url' => $this->when(
                $this->attachment_path,
                fn() => $this->getFileUrl($this->attachment_path)
            ),

            // Status
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],

            // Assignable (polymorphic - session)
            'session' => [
                'type' => $this->assignable_type,
                'id' => $this->assignable_id,
            ],

            // Due date
            'due_date' => $this->due_date?->toISOString(),

            // Student
            'student' => $this->whenLoaded('student', [
                'id' => $this->student?->id,
                'name' => $this->student?->user?->name,
                'student_code' => $this->student?->student_code,
            ]),

            // Teacher
            'teacher' => $this->whenLoaded('assignedBy', [
                'id' => $this->assignedBy?->id,
                'name' => $this->assignedBy?->name,
            ]),

            // Submission
            'submission' => $this->whenLoaded('submission', function () {
                return new HomeworkSubmissionResource($this->submission);
            }),

            // Grading
            'grade' => $this->grade,
            'max_grade' => $this->max_grade,
            'feedback' => $this->feedback,
            'graded_at' => $this->graded_at?->toISOString(),
            'graded_by' => $this->whenLoaded('gradedBy', [
                'id' => $this->gradedBy?->id,
                'name' => $this->gradedBy?->name,
            ]),

            // Timestamps
            'assigned_at' => $this->assigned_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
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
