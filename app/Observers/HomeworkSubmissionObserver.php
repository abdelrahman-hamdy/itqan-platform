<?php

namespace App\Observers;

use App\Enums\HomeworkSubmissionStatus;
use App\Enums\NotificationType;
use App\Models\AcademicHomeworkSubmission;
use App\Models\InteractiveCourseHomeworkSubmission;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Observer for homework submission models.
 *
 * Handles notifications when homework is submitted or graded.
 * Works with both AcademicHomeworkSubmission and InteractiveCourseHomeworkSubmission.
 */
class HomeworkSubmissionObserver
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Handle the submission status change.
     */
    public function updated(Model $submission): void
    {
        if (! $submission->isDirty('submission_status')) {
            return;
        }

        $newStatus = $submission->submission_status;
        $oldStatus = HomeworkSubmissionStatus::tryFrom($submission->getOriginal('submission_status'));

        // Student submitted homework
        if (in_array($newStatus, [HomeworkSubmissionStatus::SUBMITTED, HomeworkSubmissionStatus::LATE])) {
            $this->notifyHomeworkSubmitted($submission);
        }

        // Teacher graded homework
        if ($newStatus === HomeworkSubmissionStatus::GRADED && $oldStatus !== HomeworkSubmissionStatus::GRADED) {
            $this->notifyHomeworkGraded($submission);
        }
    }

    /**
     * Send notification when student submits homework.
     * Notifies the teacher that a submission has been received.
     */
    private function notifyHomeworkSubmitted(Model $submission): void
    {
        try {
            $student = $submission->student;
            $homework = $submission->homework;
            $teacher = $this->getTeacher($submission);

            if (! $teacher || ! $student || ! $homework) {
                return;
            }

            $data = [
                'student_name' => $student->name,
                'homework_title' => $homework->title ?? __('notifications.homework.untitled'),
                'is_late' => $submission->is_late,
            ];

            $actionUrl = $this->getTeacherHomeworkUrl($submission);

            $this->notificationService->send(
                $teacher,
                NotificationType::HOMEWORK_SUBMITTED_TEACHER,
                $data,
                $actionUrl,
                ['submission_id' => $submission->id, 'homework_id' => $homework->id],
                false
            );
        } catch (\Exception $e) {
            Log::error('Failed to send homework submitted notification', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification when teacher grades homework.
     * Notifies the student (and parent) that the homework has been graded.
     */
    private function notifyHomeworkGraded(Model $submission): void
    {
        try {
            $student = $submission->student;
            $homework = $submission->homework;

            if (! $student || ! $homework) {
                return;
            }

            $data = [
                'homework_title' => $homework->title ?? __('notifications.homework.untitled'),
                'score' => $submission->score,
                'max_score' => $submission->max_score,
                'score_percentage' => $submission->score_percentage,
                'teacher_feedback' => $submission->teacher_feedback,
            ];

            $actionUrl = $this->getStudentHomeworkUrl($submission);

            // Notify student
            $this->notificationService->send(
                $student,
                NotificationType::HOMEWORK_GRADED,
                $data,
                $actionUrl,
                ['submission_id' => $submission->id, 'homework_id' => $homework->id],
                true
            );

            // Notify parent if exists
            $this->notifyParent($student, NotificationType::HOMEWORK_GRADED, $data, $actionUrl, [
                'submission_id' => $submission->id,
                'homework_id' => $homework->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send homework graded notification', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the teacher user for this submission.
     */
    private function getTeacher(Model $submission): ?User
    {
        if ($submission instanceof AcademicHomeworkSubmission) {
            $session = $submission->session;

            return $session?->academicTeacher?->user ?? null;
        }

        if ($submission instanceof InteractiveCourseHomeworkSubmission) {
            $session = $submission->session;
            $course = $session?->course;

            return $course?->assignedTeacher?->user ?? null;
        }

        return null;
    }

    /**
     * Get teacher-facing homework URL.
     */
    private function getTeacherHomeworkUrl(Model $submission): string
    {
        if ($submission instanceof AcademicHomeworkSubmission) {
            return "/academic-teacher-panel/homework-submissions/{$submission->id}";
        }

        return "/academic-teacher-panel/homework-submissions/{$submission->id}";
    }

    /**
     * Get student-facing homework URL.
     */
    private function getStudentHomeworkUrl(Model $submission): string
    {
        return "/homework/{$submission->id}/view";
    }

    /**
     * Notify the parent of a student.
     */
    private function notifyParent(User $student, NotificationType $type, array $data, string $actionUrl, array $metadata): void
    {
        try {
            $parents = $student->parents ?? collect();

            foreach ($parents as $parent) {
                if ($parent->user) {
                    $this->notificationService->send(
                        $parent->user,
                        $type,
                        array_merge($data, ['student_name' => $student->name]),
                        $actionUrl,
                        $metadata,
                        false
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send parent notification', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
