<?php

namespace App\Services;

use App\Models\AcademicHomework;
use App\Models\AcademicSession;
use App\Models\HomeworkSubmission;
use App\Models\InteractiveCourseHomework;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * UnifiedHomeworkService
 *
 * Provides a single interface to access all homework types:
 * - Academic homework (written assignments, file uploads)
 * - Interactive course homework (session-tied assignments)
 * - Quran homework (view-only, tracked through StudentSessionReport)
 *
 * This service normalizes data from different sources into a unified format
 * for consistent display in student homework dashboards.
 */
class UnifiedHomeworkService
{
    /**
     * Get all homework for a student across all types
     *
     * @param int $studentId
     * @param int $academyId
     * @param string|null $status Filter by status (pending, submitted, graded, overdue, etc.)
     * @param string|null $type Filter by type (academic, interactive, quran)
     * @return Collection Unified homework collection
     */
    public function getStudentHomework(
        int $studentId,
        int $academyId,
        ?string $status = null,
        ?string $type = null
    ): Collection {
        $homework = collect();

        // Get Academic homework
        if (!$type || $type === 'academic') {
            $academicHomework = $this->getAcademicHomework($studentId, $academyId, $status);
            $homework = $homework->merge($academicHomework);
        }

        // Get Interactive Course homework
        if (!$type || $type === 'interactive') {
            $interactiveHomework = $this->getInteractiveHomework($studentId, $academyId, $status);
            $homework = $homework->merge($interactiveHomework);
        }

        // Get Quran homework (view-only)
        if (!$type || $type === 'quran') {
            $quranHomework = $this->getQuranHomework($studentId, $academyId, $status);
            $homework = $homework->merge($quranHomework);
        }

        // Sort by due date (upcoming first, then past)
        return $homework->sortBy(function ($item) {
            if (!$item['due_date']) {
                return PHP_INT_MAX; // No due date goes last
            }

            $dueDate = is_string($item['due_date'])
                ? \Carbon\Carbon::parse($item['due_date'])
                : $item['due_date'];

            return $dueDate->isPast() ? $dueDate->timestamp : -$dueDate->timestamp;
        })->values();
    }

    /**
     * Get statistics for student homework dashboard
     *
     * @param int $studentId
     * @param int $academyId
     * @return array
     */
    public function getStudentHomeworkStatistics(int $studentId, int $academyId): array
    {
        $allHomework = $this->getStudentHomework($studentId, $academyId);

        $total = $allHomework->count();
        $pending = $allHomework->where('submission_status', 'pending')->count();
        $submitted = $allHomework->whereIn('submission_status', ['submitted', 'graded', 'late'])->count();
        $graded = $allHomework->where('submission_status', 'graded')->count();
        $overdue = $allHomework->where('is_overdue', true)->count();
        $late = $allHomework->where('is_late', true)->count();

        // Calculate average score (only graded homework)
        $gradedHomework = $allHomework->where('submission_status', 'graded');
        $averageScore = $gradedHomework->isNotEmpty()
            ? $gradedHomework->avg('score_percentage')
            : null;

        // Type breakdown
        $academicCount = $allHomework->where('type', 'academic')->count();
        $interactiveCount = $allHomework->where('type', 'interactive')->count();
        $quranCount = $allHomework->where('type', 'quran')->count();

        return [
            'total' => $total,
            'pending' => $pending,
            'submitted' => $submitted,
            'graded' => $graded,
            'overdue' => $overdue,
            'late' => $late,
            'average_score' => $averageScore ? round($averageScore, 1) : null,
            'completion_rate' => $total > 0 ? round(($submitted / $total) * 100, 1) : 0,
            'type_breakdown' => [
                'academic' => $academicCount,
                'interactive' => $interactiveCount,
                'quran' => $quranCount,
            ],
        ];
    }

    // ========================================
    // Private Methods - Data Fetching
    // ========================================

    /**
     * Get Academic homework for student
     */
    private function getAcademicHomework(int $studentId, int $academyId, ?string $status): Collection
    {
        $query = AcademicHomework::query()
            ->where('academy_id', $academyId)
            ->whereHas('session.academicSubscription', function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })
            ->with([
                'session.academicTeacher.user',
                'session.academicSubscription' => function ($q) use ($studentId) {
                    $q->where('student_id', $studentId);
                },
            ]);

        $homework = $query->get();

        return $homework->map(function ($hw) use ($studentId) {
            // Get or create submission record
            $submission = $this->getOrCreateSubmission(
                $hw,
                $studentId,
                'academic'
            );

            return $this->formatAcademicHomework($hw, $submission);
        })->filter(function ($item) use ($status) {
            return $this->matchesStatus($item, $status);
        });
    }

    /**
     * Get Interactive Course homework for student
     */
    private function getInteractiveHomework(int $studentId, int $academyId, ?string $status): Collection
    {
        $query = InteractiveCourseHomework::query()
            ->whereHas('session.course', function ($q) use ($academyId) {
                $q->where('academy_id', $academyId);
            })
            ->whereHas('session.course.enrollments', function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })
            ->with([
                'session.course.assignedTeacher',
            ]);

        $homework = $query->get();

        return $homework->map(function ($hw) use ($studentId) {
            // Get or create submission record
            $submission = $this->getOrCreateSubmission(
                $hw,
                $studentId,
                'interactive'
            );

            return $this->formatInteractiveHomework($hw, $submission);
        })->filter(function ($item) use ($status) {
            return $this->matchesStatus($item, $status);
        });
    }

    /**
     * Get Quran homework for student (view-only from sessions)
     */
    private function getQuranHomework(int $studentId, int $academyId, ?string $status): Collection
    {
        // Quran homework is tracked through sessions with homework assigned
        $sessions = QuranSession::query()
            ->where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->where('homework_assigned', true) // Use the correct column
            ->whereNotNull('homework_details') // Ensure homework details exist
            ->with([
                'quranTeacher',
                'studentReport',
                'sessionHomework', // Load the homework details relationship
            ])
            ->get();

        return $sessions->map(function ($session) use ($studentId) {
            return $this->formatQuranHomework($session, $studentId);
        })->filter(function ($item) use ($status) {
            return $this->matchesStatus($item, $status);
        });
    }

    // ========================================
    // Private Methods - Data Formatting
    // ========================================

    /**
     * Format Academic homework to unified structure
     */
    private function formatAcademicHomework(AcademicHomework $homework, HomeworkSubmission $submission): array
    {
        $teacher = $homework->session?->academicTeacher?->user;

        return [
            // Identification
            'id' => $homework->id,
            'type' => 'academic',
            'submission_id' => $submission->id,

            // Content
            'title' => $homework->title,
            'description' => $homework->description,
            'instructions' => $homework->instructions ?? null,

            // Timing
            'due_date' => $homework->due_date,
            'created_at' => $homework->created_at,

            // Submission Info
            'submission_status' => $submission->submission_status,
            'submission_status_text' => $submission->status_text,
            'submitted_at' => $submission->submitted_at,
            'is_late' => $submission->is_late,
            'days_late' => $submission->days_late,
            'is_overdue' => $submission->isOverdue(),

            // Grading
            'score' => $submission->score,
            'max_score' => $submission->max_score,
            'score_percentage' => $submission->score_percentage,
            'grade_letter' => $submission->grade_letter,
            'performance_level' => $submission->performance_level,
            'teacher_feedback' => $submission->teacher_feedback,
            'graded_at' => $submission->graded_at,

            // Progress
            'progress_percentage' => $submission->progress_percentage,
            'can_submit' => $submission->canSubmit(),
            'hours_until_due' => $homework->due_date ? now()->diffInHours($homework->due_date, false) : null,

            // Session/Teacher Info
            'session_id' => $homework->session_id,
            'session_title' => $homework->session?->title ?? 'جلسة أكاديمية',
            'teacher_name' => $teacher?->name ?? 'غير محدد',
            'teacher_avatar' => $teacher?->avatar ?? null,
            'teacher_gender' => $teacher?->gender ?? 'male',
            'teacher_type' => 'academic_teacher',

            // Links
            'view_url' => route('student.homework.view', [
                'id' => $homework->id,
                'type' => 'academic',
            ]),
            'submit_url' => $submission->canSubmit()
                ? route('student.homework.submit', ['id' => $homework->id, 'type' => 'academic'])
                : null,
        ];
    }

    /**
     * Format Interactive homework to unified structure
     */
    private function formatInteractiveHomework(
        InteractiveCourseHomework $homework,
        HomeworkSubmission $submission
    ): array {
        $teacher = $homework->session?->course?->assignedTeacher;

        return [
            // Identification
            'id' => $homework->id,
            'type' => 'interactive',
            'submission_id' => $submission->id,

            // Content
            'title' => $homework->title,
            'description' => $homework->description,
            'instructions' => null,

            // Timing
            'due_date' => $homework->due_date,
            'created_at' => $homework->created_at,

            // Submission Info
            'submission_status' => $submission->submission_status,
            'submission_status_text' => $submission->status_text,
            'submitted_at' => $submission->submitted_at,
            'is_late' => $submission->is_late,
            'days_late' => $submission->days_late,
            'is_overdue' => $submission->isOverdue(),

            // Grading
            'score' => $submission->score,
            'max_score' => $submission->max_score,
            'score_percentage' => $submission->score_percentage,
            'grade_letter' => $submission->grade_letter,
            'performance_level' => $submission->performance_level,
            'teacher_feedback' => $submission->teacher_feedback,
            'graded_at' => $submission->graded_at,

            // Progress
            'progress_percentage' => $submission->progress_percentage,
            'can_submit' => $submission->canSubmit(),
            'hours_until_due' => $homework->due_date ? now()->diffInHours($homework->due_date, false) : null,

            // Course/Session/Teacher Info
            'session_id' => $homework->session_id,
            'session_title' => $homework->session?->title ?? 'محاضرة',
            'course_title' => $homework->session?->course?->title ?? 'دورة تفاعلية',
            'teacher_name' => $teacher?->name ?? 'غير محدد',
            'teacher_avatar' => $teacher?->avatar ?? null,
            'teacher_gender' => $teacher?->gender ?? 'male',
            'teacher_type' => 'academic_teacher',

            // Links
            'view_url' => route('student.homework.view', [
                'id' => $homework->id,
                'type' => 'interactive',
            ]),
            'submit_url' => $submission->canSubmit()
                ? route('student.homework.submit', ['id' => $homework->id, 'type' => 'interactive'])
                : null,
        ];
    }

    /**
     * Format Quran homework to unified structure (view-only)
     */
    private function formatQuranHomework(QuranSession $session, int $studentId): array
    {
        $teacher = $session->quranTeacher?->user;
        $report = $session->studentReport;
        $homework = $session->sessionHomework; // Get homework details from relationship

        // Determine submission status based on report
        $submissionStatus = 'pending'; // Default for Quran homework (always view-only)
        $scorePercentage = null;
        $gradeLetter = null;

        if ($report) {
            $submissionStatus = 'graded'; // If there's a report, consider it "graded"

            // Calculate score from evaluation
            if ($report->evaluation) {
                $scorePercentage = match($report->evaluation) {
                    'excellent' => 95,
                    'very_good' => 85,
                    'good' => 75,
                    'acceptable' => 65,
                    'weak' => 50,
                    default => null,
                };

                $gradeLetter = match($report->evaluation) {
                    'excellent' => 'A+',
                    'very_good' => 'A',
                    'good' => 'B',
                    'acceptable' => 'C',
                    'weak' => 'D',
                    default => null,
                };
            }
        }

        // Build homework types array using sessionHomework relationship
        $homeworkTypes = [];
        if ($homework?->has_new_memorization) $homeworkTypes[] = 'حفظ';
        if ($homework?->has_review) $homeworkTypes[] = 'مراجعة';
        if ($homework?->has_comprehensive_review) $homeworkTypes[] = 'مراجعة شاملة';

        $homeworkTypesText = implode(' + ', $homeworkTypes) ?: 'واجب قرآني';

        return [
            // Identification
            'id' => $session->id,
            'type' => 'quran',
            'submission_id' => null, // No submission for Quran homework

            // Content
            'title' => "واجب قرآني: {$homeworkTypesText}",
            'description' => $this->buildQuranHomeworkDescription($session, $homework),
            'instructions' => 'سيتم تقييم هذا الواجب خلال الجلسة القادمة',

            // Timing
            'due_date' => $session->next_session_date ?? $session->scheduled_at?->addWeek(),
            'created_at' => $session->created_at,

            // Submission Info (view-only)
            'submission_status' => $submissionStatus,
            'submission_status_text' => $submissionStatus === 'graded' ? 'تم التقييم' : 'قيد الانتظار',
            'submitted_at' => null, // No submission for oral homework
            'is_late' => false,
            'days_late' => 0,
            'is_overdue' => false,
            'hours_until_due' => $session->scheduled_at ? now()->diffInHours($session->scheduled_at, false) : null,

            // Grading (from StudentSessionReport)
            'score' => null,
            'max_score' => 100,
            'score_percentage' => $scorePercentage,
            'grade_letter' => $gradeLetter,
            'performance_level' => $report?->evaluation_text ?? null,
            'teacher_feedback' => $report?->teacher_notes ?? null,
            'graded_at' => $report?->created_at ?? null,

            // Progress
            'progress_percentage' => $report ? 100 : 0,
            'can_submit' => false, // Quran homework is view-only
            'is_view_only' => true, // Flag for UI

            // Session/Teacher Info
            'session_id' => $session->id,
            'session_title' => $session->title ?? 'جلسة قرآنية',
            'teacher_name' => $teacher?->name ?? 'غير محدد',
            'teacher_avatar' => $teacher?->avatar ?? null,
            'teacher_gender' => $teacher?->gender ?? 'male',
            'teacher_type' => 'quran_teacher',

            // Quran-specific info (from sessionHomework)
            'homework_details' => [
                'has_new_memorization' => $homework?->has_new_memorization ?? false,
                'new_memorization_pages' => $homework?->new_memorization_pages ?? null,
                'new_memorization_range' => $homework?->new_memorization_formatted_range ?? null,
                'has_review' => $homework?->has_review ?? false,
                'review_pages' => $homework?->review_pages ?? null,
                'review_range' => $homework?->review_formatted_range ?? null,
                'has_comprehensive_review' => $homework?->has_comprehensive_review ?? false,
                'difficulty_level' => $homework?->difficulty_level_arabic ?? null,
            ],

            // Links
            'view_url' => route('student.sessions.show', ['subdomain' => $session->academy->subdomain ?? 'itqan-academy', 'sessionId' => $session->id]),
            'submit_url' => null, // No submit for Quran homework
        ];
    }

    /**
     * Build description for Quran homework
     */
    private function buildQuranHomeworkDescription(QuranSession $session, $homework): string
    {
        $parts = [];

        // New memorization
        if ($homework?->has_new_memorization) {
            $range = $homework->new_memorization_formatted_range ?? '';
            $pages = $homework->new_memorization_pages ? "({$homework->new_memorization_pages} صفحات)" : '';
            $parts[] = "الحفظ الجديد: {$range} {$pages}";
        }

        // Review
        if ($homework?->has_review) {
            $range = $homework->review_formatted_range ?? '';
            $pages = $homework->review_pages ? "({$homework->review_pages} صفحات)" : '';
            $parts[] = "المراجعة: {$range} {$pages}";
        }

        // Comprehensive review
        if ($homework?->has_comprehensive_review && $homework->comprehensive_review_surahs) {
            $surahs = is_array($homework->comprehensive_review_surahs)
                ? implode(', ', $homework->comprehensive_review_surahs)
                : $homework->comprehensive_review_surahs;
            $parts[] = "المراجعة الشاملة: {$surahs}";
        }

        // Additional instructions
        if ($homework?->additional_instructions) {
            $parts[] = "ملاحظات: {$homework->additional_instructions}";
        }

        return implode("\n", $parts) ?: $session->homework_details ?? 'واجب قرآني للجلسة القادمة';
    }

    // ========================================
    // Private Methods - Helpers
    // ========================================

    /**
     * Get or create submission record for homework
     */
    private function getOrCreateSubmission(
        $homework,
        int $studentId,
        string $type
    ): HomeworkSubmission {
        $submission = HomeworkSubmission::firstOrCreate(
            [
                'submitable_type' => get_class($homework),
                'submitable_id' => $homework->id,
                'student_id' => $studentId,
            ],
            [
                'academy_id' => $homework->academy_id ?? $homework->session->course->academy_id,
                'homework_type' => $type,
                'due_date' => $homework->due_date,
                'submission_status' => 'not_started',
                'status' => 'pending',
                'max_score' => $homework->max_score ?? 100,
            ]
        );

        return $submission;
    }

    /**
     * Check if homework matches status filter
     */
    private function matchesStatus(array $item, ?string $status): bool
    {
        if (!$status) {
            return true;
        }

        return match($status) {
            'pending' => in_array($item['submission_status'], ['not_started', 'draft']),
            'submitted' => in_array($item['submission_status'], ['submitted', 'late', 'resubmitted']),
            'graded' => $item['submission_status'] === 'graded',
            'overdue' => $item['is_overdue'] === true,
            'late' => $item['is_late'] === true,
            default => true,
        };
    }
}
