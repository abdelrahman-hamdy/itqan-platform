<?php

namespace App\Http\Resources\Api\V1\Homework;

use App\Enums\HomeworkSubmissionStatus;
use App\Models\InteractiveCourseHomework;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Detail payload for an interactive-course homework item.
 *
 * @property-read InteractiveCourseHomework $resource
 */
class InteractiveHomeworkDetailResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        /** @var InteractiveCourseHomework $hw */
        $hw = $this->resource;
        $submission = $hw->submissions->first();
        $status = $this->statusFromSubmission($submission);
        $teacher = $hw->session?->course?->assignedTeacher?->user;
        $isOverdue = $hw->due_date ? $hw->due_date->isPast() : false;

        return [
            'id' => $hw->id,
            'type' => 'interactive',
            'session_id' => $hw->interactive_course_session_id,
            'title' => $hw->title ?? __('homework.default_title'),
            'subject' => $hw->session?->course?->title,
            'description' => $hw->description,
            'instructions' => $hw->instructions,
            'teacher_files' => $hw->teacher_files ?? [],
            'max_score' => $hw->max_score !== null ? (float) $hw->max_score : null,
            'allow_late_submissions' => (bool) $hw->allow_late_submissions,
            'due_date' => $hw->due_date?->toISOString(),
            'session_date' => $hw->session?->scheduled_at?->toISOString(),
            'submission_status' => $status->value,
            'can_submit' => $this->canSubmit($submission, $hw->due_date, (bool) $hw->allow_late_submissions),
            'is_overdue' => $isOverdue && ! $submission,
            'teacher' => $this->teacherPayload($teacher),
            'attachments_config' => $this->attachmentsConfig(),
            'submission' => $submission ? [
                'id' => $submission->id,
                'content' => $submission->submission_text,
                'attachments' => $submission->submission_files ?? [],
                'submitted_at' => $submission->submitted_at?->toISOString(),
                'grade' => $submission->score !== null ? (float) $submission->score : null,
                'max_grade' => (float) ($submission->max_score ?? $hw->max_score ?? 10),
                'feedback' => $submission->teacher_feedback,
                'status' => $status->value,
            ] : null,
        ];
    }

    private function teacherPayload($teacher): ?array
    {
        if (! $teacher) {
            return null;
        }

        return [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'avatar' => $teacher->avatar ? asset('storage/'.$teacher->avatar) : null,
        ];
    }

    private function statusFromSubmission($submission): HomeworkSubmissionStatus
    {
        if (! $submission) {
            return HomeworkSubmissionStatus::PENDING;
        }

        $raw = $submission->submission_status;

        return $raw instanceof HomeworkSubmissionStatus
            ? $raw
            : HomeworkSubmissionStatus::from($raw ?? 'pending');
    }

    private function canSubmit($submission, $dueDate, bool $allowLate): bool
    {
        if ($submission !== null) {
            $status = $this->statusFromSubmission($submission);

            return $status === HomeworkSubmissionStatus::DRAFT
                || $status === HomeworkSubmissionStatus::REVISION_REQUESTED;
        }

        if ($dueDate && $dueDate->isPast() && ! $allowLate) {
            return false;
        }

        return true;
    }

    private function attachmentsConfig(): array
    {
        /** @var InteractiveCourseHomework $hw */
        $hw = $this->resource;
        $config = config('homework.attachments');

        return [
            'max_files' => (int) ($hw->max_files ?? $config['max_files']),
            'max_file_size_mb' => (int) ($hw->max_file_size_mb ?? $config['max_file_size_mb']),
            'allowed_extensions' => $hw->allowed_file_types ?: $config['allowed_extensions'],
            'submission_types' => $config['submission_types'],
        ];
    }
}
