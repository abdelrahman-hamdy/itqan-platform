<?php

namespace App\Contracts;

use App\Models\AcademicHomework;
use App\Models\AcademicHomeworkSubmission;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface for homework management service.
 *
 * Handles homework creation, submission, grading, and statistics
 * for academic and interactive course homework.
 */
interface HomeworkServiceInterface
{
    /**
     * Create academic homework.
     */
    public function createAcademicHomework(array $data): AcademicHomework;

    /**
     * Submit academic homework.
     */
    public function submitAcademicHomework(int $homeworkId, int $studentId, array $submissionData): AcademicHomeworkSubmission;

    /**
     * Save academic homework draft.
     */
    public function saveDraft(int $homeworkId, int $studentId, array $submissionData): AcademicHomeworkSubmission;

    /**
     * Grade academic homework submission.
     */
    public function gradeAcademicHomework(
        int $submissionId,
        float $score,
        ?string $feedback = null,
        ?array $qualityScores = null,
        ?int $gradedBy = null
    ): AcademicHomeworkSubmission;

    /**
     * Get all homework for a student (all types).
     */
    public function getStudentHomework(int $studentId, int $academyId, ?string $status = null, ?string $type = null): array;

    /**
     * Get pending homework for a student.
     */
    public function getPendingHomework(int $studentId, int $academyId): array;

    /**
     * Get homework statistics for a student.
     */
    public function getStudentHomeworkStatistics(int $studentId, int $academyId): array;

    /**
     * Get teacher homework for grading.
     */
    public function getTeacherHomework(int $teacherId, int $academyId, bool $needsGrading = false): Collection;

    /**
     * Get submissions needing grading for a teacher.
     */
    public function getSubmissionsNeedingGrading(int $teacherId, int $academyId): Collection;

    /**
     * Get homework statistics for a teacher.
     */
    public function getTeacherHomeworkStatistics(int $teacherId, int $academyId): array;

    /**
     * Delete homework submission files.
     */
    public function deleteSubmissionFiles(AcademicHomeworkSubmission $submission): void;

    /**
     * Return graded homework to student.
     */
    public function returnHomeworkToStudent(int $submissionId): AcademicHomeworkSubmission;

    /**
     * Request revision for homework.
     */
    public function requestRevision(int $submissionId, string $reason): AcademicHomeworkSubmission;
}
