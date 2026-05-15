<?php

namespace App\Http\Resources\Api\V1\Homework;

use App\Enums\HomeworkSubmissionStatus;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseHomework;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Slim summary used by GET /student/homework (list view).
 *
 * Wraps a heterogeneous source ($this->resource is one of: AcademicSession,
 * QuranSession, InteractiveCourseHomework). The discriminator is set via
 * `$resource->homework_type` (string: academic|quran|interactive) by the
 * controller before resource construction.
 */
class HomeworkSummaryResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $user = $request->user();
        $type = $this->resource->homework_type;

        return match ($type) {
            'academic' => $this->forAcademic(),
            'quran' => $this->forQuran($user),
            'interactive' => $this->forInteractive(),
        };
    }

    private function forAcademic(): array
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
            'teacher' => $this->teacherPayload($teacher),
            'due_date' => null,
            'session_date' => $session->scheduled_at?->toISOString(),
            'submission_status' => $status->value,
            'can_submit' => $this->canSubmit('academic', $submission, null, true),
            'is_overdue' => false,
            'is_evaluated' => false,
            'grade_summary' => $this->academicGradeSummary($submission),
        ];
    }

    private function forQuran(User $user): array
    {
        /** @var QuranSession $session */
        $session = $this->resource;
        $hw = $session->sessionHomework;
        $report = $this->quranReportFor($session, $user->id);
        $isEvaluated = $report && $report->evaluated_at !== null;
        $status = $isEvaluated ? HomeworkSubmissionStatus::GRADED : HomeworkSubmissionStatus::PENDING;
        $dueDate = $hw?->due_date;

        return [
            'id' => $hw?->id ?? $session->id,
            'type' => 'quran',
            'session_id' => $session->id,
            'title' => $session->title ?? __('homework.quran_homework'),
            'subject' => __('homework.quran_subject'),
            'teacher' => $this->teacherPayload($session->quranTeacher),
            'due_date' => $dueDate?->toISOString(),
            'session_date' => $session->scheduled_at?->toISOString(),
            'submission_status' => $status->value,
            'can_submit' => false,
            'is_overdue' => $dueDate ? $dueDate->isPast() && ! $isEvaluated : false,
            'is_evaluated' => $isEvaluated,
            'grade_summary' => $this->quranGradeSummary($report),
        ];
    }

    private function forInteractive(): array
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
            'teacher' => $this->teacherPayload($teacher),
            'due_date' => $hw->due_date?->toISOString(),
            'session_date' => $hw->session?->scheduled_at?->toISOString(),
            'submission_status' => $status->value,
            'can_submit' => $this->canSubmit('interactive', $submission, $hw->due_date, (bool) $hw->allow_late_submissions),
            'is_overdue' => $isOverdue && ! $submission,
            'is_evaluated' => false,
            'grade_summary' => $this->interactiveGradeSummary($submission, (float) $hw->max_score),
        ];
    }

    private function teacherPayload(?User $teacher): ?array
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

    private function canSubmit(string $type, $submission, $dueDate, bool $allowLate): bool
    {
        if ($type === 'quran') {
            return false;
        }

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

    private function academicGradeSummary($submission): array
    {
        if (! $submission || $submission->score === null) {
            return ['score' => null, 'max' => null, 'label' => null];
        }

        return [
            'score' => (float) $submission->score,
            'max' => (float) ($submission->max_score ?? 10),
            'label' => null,
        ];
    }

    private function interactiveGradeSummary($submission, ?float $maxScore): array
    {
        if (! $submission || $submission->score === null) {
            return ['score' => null, 'max' => $maxScore, 'label' => null];
        }

        return [
            'score' => (float) $submission->score,
            'max' => (float) ($submission->max_score ?? $maxScore ?? 10),
            'label' => null,
        ];
    }

    private function quranGradeSummary(?StudentSessionReport $report): array
    {
        if (! $report || $report->evaluated_at === null) {
            return ['score' => null, 'max' => null, 'label' => null];
        }

        $tier = $this->quranTier($report->overall_performance);

        return [
            'score' => $report->overall_performance !== null ? round((float) $report->overall_performance, 1) : null,
            'max' => 10.0,
            'label' => $tier,
        ];
    }

    private function quranTier(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }

        return match (true) {
            $score >= 9 => 'excellent',
            $score >= 8 => 'very_good',
            $score >= 7 => 'good',
            $score >= 6 => 'acceptable',
            default => 'weak',
        };
    }

    private function quranReportFor(QuranSession $session, int $userId): ?StudentSessionReport
    {
        if ($session->relationLoaded('studentReports')) {
            return $session->studentReports->firstWhere('student_id', $userId);
        }

        return $session->studentReports()->where('student_id', $userId)->first();
    }
}
