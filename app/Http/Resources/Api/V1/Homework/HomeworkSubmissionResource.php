<?php

namespace App\Http\Resources\Api\V1\Homework;

use App\Models\AcademicHomeworkSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Homework Submission Resource
 *
 * Student homework submission data for API responses.
 * Works with AcademicHomeworkSubmission model.
 *
 * @mixin AcademicHomeworkSubmission
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
            'homework_id' => $this->resource->academic_homework_id,

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
            'attachments' => $this->resource->student_files ?? [],

            // Status
            'status' => [
                'value' => $this->resource->submission_status?->value ?? $this->resource->submission_status,
                'label' => method_exists($this->resource->submission_status ?? '', 'label')
                    ? $this->resource->submission_status->label()
                    : ($this->resource->submission_status ?? 'unknown'),
                'color' => method_exists($this->resource->submission_status ?? '', 'color')
                    ? $this->resource->submission_status->color()
                    : 'gray',
            ],

            // Grading
            'score' => $this->resource->score,
            'max_score' => $this->resource->homework?->max_score ?? 100,
            'feedback' => $this->resource->teacher_feedback,
            'graded_at' => $this->resource->graded_at?->toISOString(),
            'graded_by' => $this->whenLoaded('gradedBy', [
                'id' => $this->resource->gradedBy?->id,
                'name' => $this->resource->gradedBy?->name,
            ]),

            // Timing
            'submitted_at' => $this->resource->submitted_at?->toISOString(),
            'is_late' => $this->resource->is_late,
            'days_late' => $this->resource->days_late,

            // Timestamps
            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
