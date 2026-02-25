<?php

namespace App\Observers;

use App\Enums\HomeworkSubmissionStatus;
use App\Jobs\SendHomeworkNotificationJob;
use App\Models\AcademicHomeworkSubmission;
use App\Models\InteractiveCourseHomeworkSubmission;
use Illuminate\Database\Eloquent\Model;

/**
 * Observer for homework submission models.
 *
 * Dispatches queued jobs for notifications when homework is submitted or graded.
 * Works with both AcademicHomeworkSubmission and InteractiveCourseHomeworkSubmission.
 *
 * Notifications are dispatched as queued jobs (SendHomeworkNotificationJob) to avoid
 * blocking the HTTP response and to allow retries on transient failures.
 */
class HomeworkSubmissionObserver
{
    /**
     * Handle the submission status change.
     * Dispatches a queued notification job instead of sending synchronously.
     */
    public function updated(Model $submission): void
    {
        if (! $submission->wasChanged('submission_status')) {
            return;
        }

        $newStatus = $submission->submission_status;
        $oldStatus = HomeworkSubmissionStatus::tryFrom($submission->getOriginal('submission_status'));

        $submissionType = $this->resolveSubmissionType($submission);

        // Student submitted homework — notify teacher
        if (in_array($newStatus, [HomeworkSubmissionStatus::SUBMITTED, HomeworkSubmissionStatus::LATE])) {
            SendHomeworkNotificationJob::dispatch($submission->id, $submissionType, 'submitted');
        }

        // Teacher graded homework — notify student and parent
        if ($newStatus === HomeworkSubmissionStatus::GRADED && $oldStatus !== HomeworkSubmissionStatus::GRADED) {
            SendHomeworkNotificationJob::dispatch($submission->id, $submissionType, 'graded');
        }
    }

    /**
     * Resolve the submission type string used by SendHomeworkNotificationJob.
     */
    private function resolveSubmissionType(Model $submission): string
    {
        return match (true) {
            $submission instanceof AcademicHomeworkSubmission          => 'academic',
            $submission instanceof InteractiveCourseHomeworkSubmission => 'interactive',
            default                                                    => 'academic',
        };
    }
}
