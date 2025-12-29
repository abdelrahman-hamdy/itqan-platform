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
     *
     * @param  array  $data
     * @return AcademicHomework
     */
    public function createAcademicHomework(array $data): AcademicHomework;

    /**
     * Submit academic homework.
     *
     * @param  int  $homeworkId
     * @param  int  $studentId
     * @param  array  $submissionData
     * @return AcademicHomeworkSubmission
     */
    public function submitAcademicHomework(int $homeworkId, int $studentId, array $submissionData): AcademicHomeworkSubmission;

    /**
     * Save academic homework draft.
     *
     * @param  int  $homeworkId
     * @param  int  $studentId
     * @param  array  $submissionData
     * @return AcademicHomeworkSubmission
     */
    public function saveDraft(int $homeworkId, int $studentId, array $submissionData): AcademicHomeworkSubmission;

    /**
     * Grade academic homework submission.
     *
     * @param  int  $submissionId
     * @param  float  $score
     * @param  string|null  $feedback
     * @param  array|null  $qualityScores
     * @param  int|null  $gradedBy
     * @return AcademicHomeworkSubmission
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
     *
     * @param  int  $studentId
     * @param  int  $academyId
     * @param  string|null  $status
     * @param  string|null  $type
     * @return array
     */
    public function getStudentHomework(int $studentId, int $academyId, ?string $status = null, ?string $type = null): array;

    /**
     * Get pending homework for a student.
     *
     * @param  int  $studentId
     * @param  int  $academyId
     * @return array
     */
    public function getPendingHomework(int $studentId, int $academyId): array;

    /**
     * Get homework statistics for a student.
     *
     * @param  int  $studentId
     * @param  int  $academyId
     * @return array
     */
    public function getStudentHomeworkStatistics(int $studentId, int $academyId): array;

    /**
     * Get teacher homework for grading.
     *
     * @param  int  $teacherId
     * @param  int  $academyId
     * @param  bool  $needsGrading
     * @return Collection
     */
    public function getTeacherHomework(int $teacherId, int $academyId, bool $needsGrading = false): Collection;

    /**
     * Get submissions needing grading for a teacher.
     *
     * @param  int  $teacherId
     * @param  int  $academyId
     * @return Collection
     */
    public function getSubmissionsNeedingGrading(int $teacherId, int $academyId): Collection;

    /**
     * Get homework statistics for a teacher.
     *
     * @param  int  $teacherId
     * @param  int  $academyId
     * @return array
     */
    public function getTeacherHomeworkStatistics(int $teacherId, int $academyId): array;

    /**
     * Delete homework submission files.
     *
     * @param  AcademicHomeworkSubmission  $submission
     * @return void
     */
    public function deleteSubmissionFiles(AcademicHomeworkSubmission $submission): void;

    /**
     * Return graded homework to student.
     *
     * @param  int  $submissionId
     * @return AcademicHomeworkSubmission
     */
    public function returnHomeworkToStudent(int $submissionId): AcademicHomeworkSubmission;

    /**
     * Request revision for homework.
     *
     * @param  int  $submissionId
     * @param  string  $reason
     * @return AcademicHomeworkSubmission
     */
    public function requestRevision(int $submissionId, string $reason): AcademicHomeworkSubmission;
}
