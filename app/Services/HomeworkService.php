<?php

namespace App\Services;

use App\Models\AcademicHomework;
use App\Models\AcademicHomeworkSubmission;
use App\Models\InteractiveCourseHomework;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HomeworkService
{
    /**
     * Create academic homework
     */
    public function createAcademicHomework(array $data): AcademicHomework
    {
        $homework = AcademicHomework::create($data);

        // Create submissions for all students in the subscription if it's for a subscription
        if (isset($data['academic_subscription_id']) && $data['academic_subscription_id']) {
            $this->createSubmissionsForSubscription($homework);
        }

        return $homework;
    }

    /**
     * Create submissions for all students in a subscription
     */
    private function createSubmissionsForSubscription(AcademicHomework $homework): void
    {
        $subscription = $homework->subscription;
        if (!$subscription) {
            return;
        }

        // For individual academic sessions, student_id is directly on subscription
        if ($subscription->student_id) {
            AcademicHomeworkSubmission::createForHomework($homework, $subscription->student_id);
            $homework->update(['total_students' => 1]);
        }
    }

    /**
     * Submit academic homework
     */
    public function submitAcademicHomework(
        int $homeworkId,
        int $studentId,
        array $submissionData
    ): AcademicHomeworkSubmission {
        $homework = AcademicHomework::findOrFail($homeworkId);

        // Get or create submission
        $submission = $homework->getSubmissionForStudent($studentId);
        if (!$submission) {
            $submission = AcademicHomeworkSubmission::createForHomework($homework, $studentId);
        }

        // Handle file uploads
        $files = [];
        if (isset($submissionData['files']) && is_array($submissionData['files'])) {
            foreach ($submissionData['files'] as $file) {
                $path = $file->store('homework/academic', 'public');
                $files[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'uploaded_at' => now()->toDateTimeString(),
                ];
            }
        }

        // Submit
        $submission->submit(
            $submissionData['text'] ?? null,
            !empty($files) ? $files : null
        );

        return $submission->fresh();
    }

    /**
     * Save academic homework draft
     */
    public function saveDraft(
        int $homeworkId,
        int $studentId,
        array $submissionData
    ): AcademicHomeworkSubmission {
        $homework = AcademicHomework::findOrFail($homeworkId);

        // Get or create submission
        $submission = $homework->getSubmissionForStudent($studentId);
        if (!$submission) {
            $submission = AcademicHomeworkSubmission::createForHomework($homework, $studentId);
        }

        // Handle file uploads for draft
        $files = [];
        if (isset($submissionData['files']) && is_array($submissionData['files'])) {
            foreach ($submissionData['files'] as $file) {
                $path = $file->store('homework/academic/drafts', 'public');
                $files[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'uploaded_at' => now()->toDateTimeString(),
                ];
            }
        }

        // Save draft
        $submission->saveDraft(
            $submissionData['text'] ?? null,
            !empty($files) ? $files : null
        );

        return $submission->fresh();
    }

    /**
     * Grade academic homework submission
     */
    public function gradeAcademicHomework(
        int $submissionId,
        float $score,
        ?string $feedback = null,
        ?array $qualityScores = null,
        ?int $gradedBy = null
    ): AcademicHomeworkSubmission {
        $submission = AcademicHomeworkSubmission::findOrFail($submissionId);

        $submission->grade($score, $feedback, $qualityScores, $gradedBy);

        return $submission->fresh();
    }

    /**
     * Get all homework for a student (all types)
     */
    public function getStudentHomework(
        int $studentId,
        int $academyId,
        ?string $status = null,
        ?string $type = null
    ): array {
        $homework = [];

        if (!$type || $type === 'academic') {
            $academicSubmissions = AcademicHomeworkSubmission::getForStudent($studentId, $academyId, $status);
            foreach ($academicSubmissions as $submission) {
                $homework[] = [
                    'type' => 'academic',
                    'id' => $submission->id,
                    'homework_id' => $submission->academic_homework_id,
                    'title' => $submission->homework->title ?? 'واجب أكاديمي',
                    'description' => $submission->homework->description ?? '',
                    'due_date' => $submission->homework->due_date ?? null,
                    'status' => $submission->submission_status,
                    'status_text' => $submission->submission_status_text,
                    'is_late' => $submission->is_late,
                    'score' => $submission->score,
                    'max_score' => $submission->max_score,
                    'score_percentage' => $submission->score_percentage,
                    'grade_performance' => $submission->grade_performance,
                    'teacher_feedback' => $submission->teacher_feedback,
                    'submitted_at' => $submission->submitted_at,
                    'graded_at' => $submission->graded_at,
                    'homework' => $submission->homework,
                    'submission' => $submission,
                ];
            }
        }

        // Note: Quran homework is now tracked through QuranSession model fields
        // and graded through student session reports (oral evaluation)
        // See migration: 2025_11_17_190605_drop_quran_homework_tables.php

        if (!$type || $type === 'interactive') {
            $interactiveHomework = InteractiveCourseHomework::forStudent($studentId)
                ->forAcademy($academyId)
                ->when($status, function ($query) use ($status) {
                    $query->where('submission_status', $status);
                })
                ->with(['session.interactiveCourse'])
                ->get();

            foreach ($interactiveHomework as $hw) {
                $homework[] = [
                    'type' => 'interactive',
                    'id' => $hw->id,
                    'homework_id' => $hw->id,
                    'title' => $hw->session->interactiveCourse->title ?? 'واجب دورة تفاعلية',
                    'description' => $hw->session->homework_description ?? '',
                    'due_date' => $hw->session->homework_due_date,
                    'status' => $hw->submission_status,
                    'status_text' => $hw->submission_status_in_arabic,
                    'is_late' => $hw->is_late,
                    'score' => $hw->score,
                    'max_score' => $hw->session->homework_max_score ?? 100,
                    'score_percentage' => $hw->score_percentage,
                    'grade_performance' => $hw->grade_letter ? "درجة {$hw->grade_letter}" : null,
                    'teacher_feedback' => $hw->teacher_feedback,
                    'submitted_at' => $hw->submitted_at,
                    'graded_at' => $hw->graded_at,
                    'homework' => $hw,
                    'submission' => $hw,
                ];
            }
        }

        // Sort by due date
        usort($homework, function ($a, $b) {
            if (!$a['due_date']) return 1;
            if (!$b['due_date']) return -1;
            return $b['due_date'] <=> $a['due_date'];
        });

        return $homework;
    }

    /**
     * Get pending homework for a student
     */
    public function getPendingHomework(int $studentId, int $academyId): array
    {
        return $this->getStudentHomework($studentId, $academyId, 'pending');
    }

    /**
     * Get homework statistics for a student
     */
    public function getStudentHomeworkStatistics(int $studentId, int $academyId): array
    {
        $allHomework = $this->getStudentHomework($studentId, $academyId);

        $totalCount = count($allHomework);
        $submittedCount = count(array_filter($allHomework, function ($hw) {
            return in_array($hw['status'], ['submitted', 'late', 'graded', 'returned']);
        }));
        $gradedCount = count(array_filter($allHomework, function ($hw) {
            return in_array($hw['status'], ['graded', 'returned']);
        }));
        $overdueCount = count(array_filter($allHomework, function ($hw) {
            return $hw['due_date'] && Carbon::parse($hw['due_date'])->isPast() &&
                   !in_array($hw['status'], ['submitted', 'late', 'graded', 'returned']);
        }));
        $lateCount = count(array_filter($allHomework, fn($hw) => $hw['is_late']));

        $scores = array_filter(array_map(fn($hw) => $hw['score_percentage'], $allHomework));
        $averageScore = !empty($scores) ? array_sum($scores) / count($scores) : 0;

        return [
            'total' => $totalCount,
            'submitted' => $submittedCount,
            'graded' => $gradedCount,
            'overdue' => $overdueCount,
            'late' => $lateCount,
            'pending' => $totalCount - $submittedCount,
            'submission_rate' => $totalCount > 0 ? round(($submittedCount / $totalCount) * 100, 2) : 0,
            'average_score' => round($averageScore, 2),
        ];
    }

    /**
     * Get teacher homework for grading
     */
    public function getTeacherHomework(
        int $teacherId,
        int $academyId,
        bool $needsGrading = false
    ): Collection {
        $query = AcademicHomework::forTeacher($teacherId)
            ->forAcademy($academyId)
            ->with(['submissions.student']);

        if ($needsGrading) {
            $query->needsGrading();
        }

        return $query->orderBy('due_date', 'desc')->get();
    }

    /**
     * Get submissions needing grading for a teacher
     */
    public function getSubmissionsNeedingGrading(int $teacherId, int $academyId): Collection
    {
        return AcademicHomeworkSubmission::query()
            ->whereHas('homework', function ($query) use ($teacherId, $academyId) {
                $query->where('teacher_id', $teacherId)
                    ->where('academy_id', $academyId);
            })
            ->pendingGrading()
            ->with(['homework', 'student', 'session'])
            ->orderBy('submitted_at', 'asc')
            ->get();
    }

    /**
     * Get homework statistics for a teacher
     */
    public function getTeacherHomeworkStatistics(int $teacherId, int $academyId): array
    {
        $homework = $this->getTeacherHomework($teacherId, $academyId);

        $totalHomework = $homework->count();
        $totalSubmissions = $homework->sum('submitted_count');
        $totalGraded = $homework->sum('graded_count');
        $pendingGrading = $totalSubmissions - $totalGraded;
        $averageScore = $homework->avg('average_score') ?? 0;

        return [
            'total_homework' => $totalHomework,
            'total_submissions' => $totalSubmissions,
            'graded' => $totalGraded,
            'pending_grading' => $pendingGrading,
            'average_score' => round($averageScore, 2),
        ];
    }

    /**
     * Delete homework submission files
     */
    public function deleteSubmissionFiles(AcademicHomeworkSubmission $submission): void
    {
        if ($submission->submission_files && is_array($submission->submission_files)) {
            foreach ($submission->submission_files as $file) {
                if (isset($file['path'])) {
                    Storage::disk('public')->delete($file['path']);
                }
            }
        }
    }

    /**
     * Return graded homework to student
     */
    public function returnHomeworkToStudent(int $submissionId): AcademicHomeworkSubmission
    {
        $submission = AcademicHomeworkSubmission::findOrFail($submissionId);
        $submission->returnToStudent();

        return $submission->fresh();
    }

    /**
     * Request revision for homework
     */
    public function requestRevision(int $submissionId, string $reason): AcademicHomeworkSubmission
    {
        $submission = AcademicHomeworkSubmission::findOrFail($submissionId);
        $submission->requestRevision($reason);

        return $submission->fresh();
    }
}
