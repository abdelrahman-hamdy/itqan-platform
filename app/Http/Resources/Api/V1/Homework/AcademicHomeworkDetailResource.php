<?php

namespace App\Http\Resources\Api\V1\Homework;

use App\Enums\HomeworkSubmissionStatus;
use App\Models\AcademicSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Detail payload for an academic homework item.
 *
 * Academic homework is currently shipped via AcademicSession.homework_description
 * (the column-on-session system). The richer AcademicHomework model is not
 * student-facing yet, so this resource only exposes the session-column shape.
 *
 * @property-read AcademicSession $resource
 */
class AcademicHomeworkDetailResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        /** @var AcademicSession $session */
        $session = $this->resource;
        $submission = $session->homeworkSubmissions->first();
        $status = $this->statusFromSubmission($submission);
        $teacher = $session->academicTeacher?->user;

        return [
            'id' => $session->id,
            'type' => 'academic',
            'session_id' => $session->id,
            'title' => $session->title ?? __('homework.default_title'),
            'subject' => $session->academicSubscription?->subject_name,
            'description' => $session->homework_description,
            'instructions' => null,
            'homework_file' => $session->homework_file
                ? asset('storage/'.$session->homework_file)
                : null,
            'max_grade' => 10,
            'due_date' => null,
            'session_date' => $session->scheduled_at?->toISOString(),
            'submission_status' => $status->value,
            'can_submit' => $this->canSubmit($submission),
            'is_overdue' => false,
            'teacher' => $this->teacherPayload($teacher),
            'attachments_config' => $this->attachmentsConfig(),
            'submission' => $submission ? [
                'id' => $submission->id,
                'content' => $submission->submission_text,
                'attachments' => $submission->submission_files ?? [],
                'submitted_at' => $submission->submitted_at?->toISOString(),
                'grade' => $submission->score !== null ? (float) $submission->score : null,
                'max_grade' => (float) ($submission->max_score ?? 10),
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

    private function canSubmit($submission): bool
    {
        if ($submission === null) {
            return true;
        }

        $status = $this->statusFromSubmission($submission);

        return $status === HomeworkSubmissionStatus::DRAFT
            || $status === HomeworkSubmissionStatus::REVISION_REQUESTED;
    }

    private function attachmentsConfig(): array
    {
        $config = config('homework.attachments');

        return [
            'max_files' => (int) $config['max_files'],
            'max_file_size_mb' => (int) $config['max_file_size_mb'],
            'allowed_extensions' => $config['allowed_extensions'],
            'submission_types' => $config['submission_types'],
        ];
    }
}
