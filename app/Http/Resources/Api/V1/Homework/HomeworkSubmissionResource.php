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
            'id' => $this->id,

            // Homework reference
            'homework_id' => $this->homework_id,

            // Student
            'student' => $this->whenLoaded('student', [
                'id' => $this->student?->id,
                'name' => $this->student?->user?->name,
                'student_code' => $this->student?->student_code,
            ]),

            // Submission content
            'content' => $this->content,
            'notes' => $this->notes,

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

            // Grading
            'grade' => $this->grade,
            'feedback' => $this->feedback,
            'graded_at' => $this->graded_at?->toISOString(),
            'graded_by' => $this->whenLoaded('gradedBy', [
                'id' => $this->gradedBy?->id,
                'name' => $this->gradedBy?->name,
            ]),

            // Timing
            'submitted_at' => $this->submitted_at?->toISOString(),
            'is_late' => $this->is_late,

            // Timestamps
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
