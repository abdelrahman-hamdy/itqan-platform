<?php

namespace App\Observers;

use App\Models\HomeworkSubmission;
use App\Services\NotificationService;
use App\Services\ParentNotificationService;
use Illuminate\Support\Facades\Log;

/**
 * HomeworkSubmissionObserver
 *
 * Handles notifications when homework is graded
 */
class HomeworkSubmissionObserver
{
    /**
     * Handle the HomeworkSubmission "updated" event.
     */
    public function updated(HomeworkSubmission $submission): void
    {
        // Check if homework was just graded (grade field changed from null to a value)
        if ($submission->isDirty('grade') && $submission->grade !== null) {
            $this->sendGradedNotifications($submission);
        }
    }

    /**
     * Send notifications when homework is graded
     */
    private function sendGradedNotifications(HomeworkSubmission $submission): void
    {
        try {
            $student = \App\Models\User::find($submission->student_id);
            if (!$student) {
                return;
            }

            $notificationService = app(NotificationService::class);
            $parentNotificationService = app(ParentNotificationService::class);

            // Determine session type and get session
            $session = null;
            if ($submission->homeworkable_type === 'App\Models\AcademicHomework') {
                $homework = $submission->homeworkable;
                $session = $homework?->session;
                $sessionType = 'academic';
            } elseif ($submission->homeworkable_type === 'App\Models\InteractiveCourseHomework') {
                $homework = $submission->homeworkable;
                $session = $homework?->session;
                $sessionType = 'interactive';
            }

            if (!$session) {
                Log::warning('Could not find session for homework submission', [
                    'submission_id' => $submission->id,
                    'homeworkable_type' => $submission->homeworkable_type,
                ]);
                return;
            }

            // Send notification to student
            $notificationService->send(
                $student,
                \App\Enums\NotificationType::HOMEWORK_GRADED,
                [
                    'session_title' => $session->title ?? 'الجلسة',
                    'grade' => $submission->grade . ' / ' . ($submission->max_points ?? 100),
                ],
                route('student.homework.view', ['id' => $submission->id]),
                [
                    'submission_id' => $submission->id,
                    'session_id' => $session->id,
                ]
            );

            // Also notify parents
            $parents = $parentNotificationService->getParentsForStudent($student);
            foreach ($parents as $parent) {
                $notificationService->send(
                    $parent->user,
                    \App\Enums\NotificationType::HOMEWORK_GRADED,
                    [
                        'child_name' => $student->name,
                        'session_title' => $session->title ?? 'الجلسة',
                        'grade' => $submission->grade . ' / ' . ($submission->max_points ?? 100),
                    ],
                    route('parent.homework.view', ['id' => $submission->id]),
                    [
                        'child_id' => $student->id,
                        'submission_id' => $submission->id,
                        'session_id' => $session->id,
                    ]
                );
            }

            Log::info('Homework graded notifications sent', [
                'submission_id' => $submission->id,
                'student_id' => $student->id,
                'grade' => $submission->grade,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send homework graded notifications', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
