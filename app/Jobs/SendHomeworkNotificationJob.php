<?php

namespace App\Jobs;

use App\Constants\DefaultAcademy;
use App\Enums\NotificationType;
use App\Models\AcademicHomeworkSubmission;
use App\Models\InteractiveCourseHomeworkSubmission;
use App\Models\User;
use App\Services\NotificationService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendHomeworkNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @param  int     $submissionId    Primary key of the submission record
     * @param  string  $submissionType  'academic' or 'interactive'
     * @param  string  $event           'submitted' or 'graded'
     */
    public function __construct(
        public readonly int $submissionId,
        public readonly string $submissionType,
        public readonly string $event
    ) {
        $this->onQueue('notifications');
    }

    public function handle(NotificationService $notificationService): void
    {
        $submission = $this->resolveSubmission();

        if (! $submission) {
            Log::warning('SendHomeworkNotificationJob: submission not found', [
                'submission_id' => $this->submissionId,
                'submission_type' => $this->submissionType,
                'event' => $this->event,
            ]);

            return;
        }

        match ($this->event) {
            'submitted' => $this->notifySubmitted($submission, $notificationService),
            'graded'    => $this->notifyGraded($submission, $notificationService),
            default     => null,
        };
    }

    private function resolveSubmission(): ?Model
    {
        return match ($this->submissionType) {
            'academic'    => AcademicHomeworkSubmission::find($this->submissionId),
            'interactive' => InteractiveCourseHomeworkSubmission::find($this->submissionId),
            default       => null,
        };
    }

    /**
     * Notify the teacher that a student submitted homework.
     */
    private function notifySubmitted(Model $submission, NotificationService $notificationService): void
    {
        try {
            $student  = $submission->student;
            $homework = $submission->homework;
            $teacher  = $this->getTeacher($submission);

            if (! $teacher || ! $student || ! $homework) {
                return;
            }

            $data = [
                'student_name'   => $student->name,
                'homework_title' => $homework->title ?? __('notifications.homework.untitled'),
                'is_late'        => $submission->is_late,
            ];

            $actionUrl = $this->getTeacherHomeworkUrl($submission);

            $notificationService->send(
                $teacher,
                NotificationType::HOMEWORK_SUBMITTED_TEACHER,
                $data,
                $actionUrl,
                ['submission_id' => $submission->id, 'homework_id' => $homework->id],
                false
            );
        } catch (Exception $e) {
            Log::error('SendHomeworkNotificationJob: failed to send submitted notification', [
                'submission_id' => $this->submissionId,
                'error'         => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Notify the student (and parent) that the teacher graded homework.
     */
    private function notifyGraded(Model $submission, NotificationService $notificationService): void
    {
        try {
            $student  = $submission->student;
            $homework = $submission->homework;

            if (! $student || ! $homework) {
                return;
            }

            $data = [
                'homework_title'   => $homework->title ?? __('notifications.homework.untitled'),
                'score'            => $submission->score,
                'max_score'        => $submission->max_score,
                'score_percentage' => $submission->score_percentage,
                'teacher_feedback' => $submission->teacher_feedback,
            ];

            $actionUrl = $this->getStudentHomeworkUrl($submission);

            // Notify student
            $notificationService->send(
                $student,
                NotificationType::HOMEWORK_GRADED,
                $data,
                $actionUrl,
                ['submission_id' => $submission->id, 'homework_id' => $homework->id],
                true
            );

            // Notify parent if exists
            $parents = $student->parents ?? collect();
            foreach ($parents as $parent) {
                if ($parent->user) {
                    $notificationService->send(
                        $parent->user,
                        NotificationType::HOMEWORK_GRADED,
                        array_merge($data, ['student_name' => $student->name]),
                        $actionUrl,
                        ['submission_id' => $submission->id, 'homework_id' => $homework->id],
                        false
                    );
                }
            }
        } catch (Exception $e) {
            Log::error('SendHomeworkNotificationJob: failed to send graded notification', [
                'submission_id' => $this->submissionId,
                'error'         => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Resolve the teacher responsible for reviewing this submission.
     */
    private function getTeacher(Model $submission): ?User
    {
        if ($submission instanceof AcademicHomeworkSubmission) {
            return $submission->session?->academicTeacher?->user ?? null;
        }

        if ($submission instanceof InteractiveCourseHomeworkSubmission) {
            return $submission->session?->course?->assignedTeacher?->user ?? null;
        }

        return null;
    }

    /**
     * Get teacher-facing URL for the homework submission.
     * Uses the appropriate panel path based on submission type.
     */
    private function getTeacherHomeworkUrl(Model $submission): string
    {
        if ($submission instanceof InteractiveCourseHomeworkSubmission) {
            // Interactive course homework is managed through the course panel path
            return "/academic-teacher-panel/interactive-course-homework-submissions/{$submission->id}";
        }

        // Academic homework submissions (default)
        return "/academic-teacher-panel/homework-submissions/{$submission->id}";
    }

    /**
     * Get student-facing URL for viewing graded homework.
     */
    private function getStudentHomeworkUrl(Model $submission): string
    {
        $student   = $submission->student;
        $subdomain = $student?->academy?->subdomain ?? DefaultAcademy::subdomain();

        $type = match (true) {
            $submission instanceof AcademicHomeworkSubmission            => 'academic',
            $submission instanceof InteractiveCourseHomeworkSubmission   => 'interactive',
            default                                                      => 'academic',
        };

        return route('student.homework.view', [
            'subdomain' => $subdomain,
            'id'        => $submission->homework_id ?? $submission->id,
            'type'      => $type,
        ]);
    }
}
