<?php

namespace App\Observers;

use App\Models\AcademicHomework;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\HomeworkSubmission;
use App\Models\InteractiveCourseSession;
use App\Models\InteractiveSessionReport;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Services\NotificationService;
use App\Services\ParentNotificationService;
use Illuminate\Support\Facades\Log;
use App\Enums\HomeworkSubmissionStatus;

/**
 * HomeworkSubmissionObserver
 *
 * Handles:
 * 1. Syncing homework grades to session reports (auto-sync)
 * 2. Notifications when homework is graded
 *
 * CRITICAL: This observer syncs data from HomeworkSubmission (unified system)
 * to the session report models, ensuring grade consistency across the system.
 *
 * Grade Scale Conversion:
 * - HomeworkSubmission uses 0-100 scale (score) with 0-10 backward compat (grade)
 * - Session reports use 0-10 scale (homework_degree)
 * - Conversion: homework_degree = (score / max_score) * 10
 */
class HomeworkSubmissionObserver
{
    /**
     * Handle the HomeworkSubmission "created" event.
     * Syncs submission timestamp to session report.
     */
    public function created(HomeworkSubmission $submission): void
    {
        // If submission is created with a submitted status, sync to report
        if ($submission->isSubmitted()) {
            $this->syncSubmissionToReport($submission);
        }
    }

    /**
     * Handle the HomeworkSubmission "updated" event.
     */
    public function updated(HomeworkSubmission $submission): void
    {
        // Check if homework was just submitted
        if ($submission->isDirty('submission_status') && $submission->isSubmitted()) {
            $this->syncSubmissionToReport($submission);
        }

        // Check if homework was just graded
        if ($this->wasJustGraded($submission)) {
            $this->syncGradeToReport($submission);
            $this->sendGradedNotifications($submission);
        }
    }

    /**
     * Check if the submission was just graded
     */
    private function wasJustGraded(HomeworkSubmission $submission): bool
    {
        // Check if submission_status changed to 'graded'
        if ($submission->isDirty('submission_status') && $submission->submission_status === HomeworkSubmissionStatus::GRADED->value) {
            return true;
        }

        // Check if grade/score was just set (from null to a value)
        if ($submission->isDirty('score') && $submission->score !== null) {
            return true;
        }

        if ($submission->isDirty('grade') && $submission->grade !== null) {
            return true;
        }

        return false;
    }

    /**
     * Sync submission timestamp to the corresponding session report
     */
    private function syncSubmissionToReport(HomeworkSubmission $submission): void
    {
        try {
            $report = $this->findSessionReport($submission);

            if (!$report) {
                Log::warning('HomeworkSubmissionObserver: Could not find session report for submission sync', [
                    'submission_id' => $submission->id,
                    'submitable_type' => $submission->submitable_type,
                    'submitable_id' => $submission->submitable_id,
                    'student_id' => $submission->student_id,
                ]);
                return;
            }

            $report->update([
                'homework_submitted_at' => $submission->submitted_at,
                'homework_submission_id' => $submission->id,
            ]);

            Log::info('HomeworkSubmissionObserver: Synced submission to report', [
                'submission_id' => $submission->id,
                'report_id' => $report->id,
                'report_type' => get_class($report),
            ]);

        } catch (\Exception $e) {
            Log::error('HomeworkSubmissionObserver: Failed to sync submission to report', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync homework grade to the corresponding session report
     *
     * CRITICAL: Converts 0-100 score to 0-10 homework_degree
     */
    private function syncGradeToReport(HomeworkSubmission $submission): void
    {
        try {
            $report = $this->findSessionReport($submission);

            if (!$report) {
                Log::warning('HomeworkSubmissionObserver: Could not find session report for grade sync', [
                    'submission_id' => $submission->id,
                    'submitable_type' => $submission->submitable_type,
                    'submitable_id' => $submission->submitable_id,
                    'student_id' => $submission->student_id,
                ]);
                return;
            }

            // Convert score (0-100) to homework_degree (0-10)
            $homeworkDegree = $this->convertToHomeworkDegree($submission);

            // Only update if the report has homework_degree field
            if ($report instanceof AcademicSessionReport || $report instanceof InteractiveSessionReport) {
                $report->update([
                    'homework_degree' => $homeworkDegree,
                    'homework_submitted_at' => $submission->submitted_at,
                    'homework_submission_id' => $submission->id,
                ]);

                Log::info('HomeworkSubmissionObserver: Synced grade to report', [
                    'submission_id' => $submission->id,
                    'report_id' => $report->id,
                    'report_type' => get_class($report),
                    'original_score' => $submission->score,
                    'homework_degree' => $homeworkDegree,
                ]);
            } elseif ($report instanceof StudentSessionReport) {
                // Quran session reports might use different grading - just sync the link
                $report->update([
                    'homework_submitted_at' => $submission->submitted_at,
                    'homework_submission_id' => $submission->id,
                ]);

                Log::info('HomeworkSubmissionObserver: Synced submission link to Quran report', [
                    'submission_id' => $submission->id,
                    'report_id' => $report->id,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('HomeworkSubmissionObserver: Failed to sync grade to report', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Find the session report for this homework submission
     *
     * The submitable can be:
     * - AcademicSession -> AcademicSessionReport
     * - InteractiveCourseSession -> InteractiveSessionReport
     * - QuranSession -> StudentSessionReport
     * - AcademicHomework -> AcademicSession -> AcademicSessionReport
     */
    private function findSessionReport(HomeworkSubmission $submission): ?object
    {
        $submitable = $submission->submitable;

        if (!$submitable) {
            return null;
        }

        // Determine session and report based on submitable type
        if ($submitable instanceof AcademicSession) {
            return AcademicSessionReport::where('session_id', $submitable->id)
                ->where('student_id', $submission->student_id)
                ->first();
        }

        if ($submitable instanceof InteractiveCourseSession) {
            return InteractiveSessionReport::where('session_id', $submitable->id)
                ->where('student_id', $submission->student_id)
                ->first();
        }

        if ($submitable instanceof QuranSession) {
            return StudentSessionReport::where('session_id', $submitable->id)
                ->where('student_id', $submission->student_id)
                ->first();
        }

        // Handle case where submitable is AcademicHomework (homework model, not session)
        if ($submitable instanceof AcademicHomework) {
            $session = $submitable->session;
            if ($session) {
                return AcademicSessionReport::where('session_id', $session->id)
                    ->where('student_id', $submission->student_id)
                    ->first();
            }
        }

        return null;
    }

    /**
     * Convert submission score to homework_degree (0-10 scale)
     *
     * Priority:
     * 1. Use 'grade' field if already set (already 0-10)
     * 2. Convert 'score' field from 0-100 to 0-10
     */
    private function convertToHomeworkDegree(HomeworkSubmission $submission): ?float
    {
        // If grade is already set (0-10), use it directly
        if ($submission->grade !== null) {
            return min(10, max(0, (float) $submission->grade));
        }

        // Convert score (0-100) to 0-10 scale
        if ($submission->score !== null && $submission->max_score) {
            $percentage = $submission->score / $submission->max_score;
            return round($percentage * 10, 1);
        }

        if ($submission->score !== null) {
            // Assume max_score of 100 if not set
            return round(($submission->score / 100) * 10, 1);
        }

        return null;
    }

    /**
     * Get session from submission (resolving through homework if needed)
     */
    private function getSessionFromSubmission(HomeworkSubmission $submission): ?object
    {
        $submitable = $submission->submitable;

        if (!$submitable) {
            return null;
        }

        // Direct session references
        if ($submitable instanceof AcademicSession ||
            $submitable instanceof InteractiveCourseSession ||
            $submitable instanceof QuranSession) {
            return $submitable;
        }

        // Homework model - get session through relationship
        if ($submitable instanceof AcademicHomework) {
            return $submitable->session;
        }

        return null;
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

            // Get session from submission
            $session = $this->getSessionFromSubmission($submission);
            $sessionType = $this->determineSessionType($submission);

            if (!$session) {
                Log::warning('HomeworkSubmissionObserver: Could not find session for notification', [
                    'submission_id' => $submission->id,
                    'submitable_type' => $submission->submitable_type,
                ]);
                return;
            }

            // Prepare grade display
            $gradeDisplay = $submission->score !== null
                ? $submission->score . ' / ' . ($submission->max_score ?? 100)
                : $submission->grade . ' / 10';

            // Send notification to student
            $notificationService->send(
                $student,
                \App\Enums\NotificationType::HOMEWORK_GRADED,
                [
                    'session_title' => $session->title ?? 'الجلسة',
                    'grade' => $gradeDisplay,
                ],
                $this->getStudentHomeworkRoute($submission),
                [
                    'submission_id' => $submission->id,
                    'session_id' => $session->id ?? null,
                    'session_type' => $sessionType,
                ]
            );

            // Also notify parents
            $parents = $parentNotificationService->getParentsForStudent($student);
            foreach ($parents as $parent) {
                $parentUser = $parent->user ?? $parent;
                if ($parentUser) {
                    $notificationService->send(
                        $parentUser,
                        \App\Enums\NotificationType::HOMEWORK_GRADED,
                        [
                            'child_name' => $student->name,
                            'session_title' => $session->title ?? 'الجلسة',
                            'grade' => $gradeDisplay,
                        ],
                        $this->getParentHomeworkRoute($submission),
                        [
                            'child_id' => $student->id,
                            'submission_id' => $submission->id,
                            'session_id' => $session->id ?? null,
                            'session_type' => $sessionType,
                        ]
                    );
                }
            }

            Log::info('HomeworkSubmissionObserver: Graded notifications sent', [
                'submission_id' => $submission->id,
                'student_id' => $student->id,
                'grade' => $gradeDisplay,
            ]);

        } catch (\Exception $e) {
            Log::error('HomeworkSubmissionObserver: Failed to send graded notifications', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine session type from submitable
     */
    private function determineSessionType(HomeworkSubmission $submission): string
    {
        $submitable = $submission->submitable;

        if ($submitable instanceof AcademicSession || $submitable instanceof AcademicHomework) {
            return 'academic';
        }

        if ($submitable instanceof InteractiveCourseSession) {
            return 'interactive';
        }

        if ($submitable instanceof QuranSession) {
            return 'quran';
        }

        return $submission->homework_type ?? 'unknown';
    }

    /**
     * Get student homework view route
     */
    private function getStudentHomeworkRoute(HomeworkSubmission $submission): ?string
    {
        try {
            // Generic homework view route
            if (\Route::has('student.homework.view')) {
                return route('student.homework.view', ['id' => $submission->id]);
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get parent homework view route
     */
    private function getParentHomeworkRoute(HomeworkSubmission $submission): ?string
    {
        try {
            if (\Route::has('parent.homework.view')) {
                return route('parent.homework.view', ['id' => $submission->id]);
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
