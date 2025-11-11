<?php

namespace App\Services;

use App\Models\QuranHomeworkAssignment;
use App\Models\QuranSession;
use App\Models\QuranSessionHomework;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuranHomeworkService
{
    /**
     * Create session homework with assignments for all students
     */
    public function createSessionHomework(QuranSession $session, array $homeworkData): QuranSessionHomework
    {
        return DB::transaction(function () use ($session, $homeworkData) {
            // Create the session homework
            $homework = QuranSessionHomework::create([
                'session_id' => $session->id,
                'created_by' => auth()->id(),
                ...$homeworkData,
            ]);

            // Auto-create assignments for all students in the session
            $this->createAssignmentsForAllStudents($homework);

            Log::info('Session homework created', [
                'session_id' => $session->id,
                'homework_id' => $homework->id,
                'total_pages' => $homework->total_pages,
                'created_by' => auth()->id(),
            ]);

            return $homework;
        });
    }

    /**
     * Update session homework
     */
    public function updateSessionHomework(QuranSessionHomework $homework, array $homeworkData): QuranSessionHomework
    {
        return DB::transaction(function () use ($homework, $homeworkData) {
            $homework->update($homeworkData);

            // If new students were added to the session, create assignments for them
            $this->createAssignmentsForAllStudents($homework);

            Log::info('Session homework updated', [
                'homework_id' => $homework->id,
                'session_id' => $homework->session_id,
                'updated_by' => auth()->id(),
            ]);

            return $homework;
        });
    }

    /**
     * Create assignments for all students in the session
     */
    public function createAssignmentsForAllStudents(QuranSessionHomework $homework): void
    {
        $session = $homework->session;
        $students = $this->getSessionStudents($session);

        foreach ($students as $student) {
            QuranHomeworkAssignment::firstOrCreate([
                'session_homework_id' => $homework->id,
                'student_id' => $student->id,
                'session_id' => $session->id,
            ]);
        }
    }

    /**
     * Update homework assignment with student results
     */
    public function updateHomeworkAssignment(QuranHomeworkAssignment $assignment, array $data): QuranHomeworkAssignment
    {
        return DB::transaction(function () use ($assignment, $data) {
            $assignment->update($data);

            // Auto-calculate completion status based on pages completed
            $this->updateCompletionStatus($assignment);

            // Mark as evaluated by teacher
            $assignment->markAsEvaluated();

            Log::info('Homework assignment updated', [
                'assignment_id' => $assignment->id,
                'student_id' => $assignment->student_id,
                'completion_status' => $assignment->completion_status,
                'overall_score' => $assignment->overall_score,
                'evaluated_by' => auth()->id(),
            ]);

            return $assignment;
        });
    }

    /**
     * Auto-calculate completion status based on pages completed
     */
    protected function updateCompletionStatus(QuranHomeworkAssignment $assignment): void
    {
        $homework = $assignment->sessionHomework;
        if (! $homework) {
            return;
        }

        $totalRequired = $homework->total_pages;
        $totalCompleted = $assignment->total_completed_pages;

        $completionPercentage = $totalRequired > 0 ? ($totalCompleted / $totalRequired) * 100 : 0;

        if ($completionPercentage >= 100) {
            $assignment->completion_status = 'completed';
        } elseif ($completionPercentage >= 50) {
            $assignment->completion_status = 'partially_completed';
        } elseif ($completionPercentage > 0) {
            $assignment->completion_status = 'in_progress';
        } else {
            $assignment->completion_status = 'not_started';
        }

        $assignment->save();
    }

    /**
     * Get students for a session based on session type
     */
    protected function getSessionStudents(QuranSession $session): Collection
    {
        if ($session->session_type === 'group' && $session->circle) {
            return $session->circle->students;
        } elseif ($session->session_type === 'individual' && $session->student_id) {
            return collect([User::find($session->student_id)])->filter();
        }

        return collect();
    }

    /**
     * Get comprehensive homework statistics for a session
     */
    public function getSessionHomeworkStats(QuranSession $session): array
    {
        $homework = $session->sessionHomework;
        if (! $homework) {
            return [
                'has_homework' => false,
                'total_students' => 0,
                'assignments' => collect(),
            ];
        }

        $assignments = $homework->assignments()->with('student')->get();

        $stats = [
            'has_homework' => true,
            'homework_id' => $homework->id,
            'total_pages' => $homework->total_pages,
            'new_memorization_pages' => $homework->new_memorization_pages,
            'review_pages' => $homework->review_pages,
            'new_memorization_range' => $homework->new_memorization_range,
            'review_range' => $homework->review_range,
            'difficulty_level' => $homework->difficulty_level_arabic,
            'due_date' => $homework->due_date,
            'is_overdue' => $homework->is_overdue,
            'total_students' => $assignments->count(),
            'completed_count' => $assignments->where('completion_status', 'completed')->count(),
            'in_progress_count' => $assignments->where('completion_status', 'in_progress')->count(),
            'partially_completed_count' => $assignments->where('completion_status', 'partially_completed')->count(),
            'not_started_count' => $assignments->where('completion_status', 'not_started')->count(),
            'average_completion' => $assignments->avg('completion_percentage') ?? 0,
            'average_score' => $assignments->whereNotNull('overall_score')->avg('overall_score') ?? 0,
            'assignments' => $assignments,
        ];

        // Add quality statistics
        $stats['quality_stats'] = [
            'new_memorization' => [
                'excellent' => $assignments->where('new_memorization_quality', 'excellent')->count(),
                'good' => $assignments->where('new_memorization_quality', 'good')->count(),
                'needs_improvement' => $assignments->where('new_memorization_quality', 'needs_improvement')->count(),
                'not_completed' => $assignments->where('new_memorization_quality', 'not_completed')->count(),
            ],
            'review' => [
                'excellent' => $assignments->where('review_quality', 'excellent')->count(),
                'good' => $assignments->where('review_quality', 'good')->count(),
                'needs_improvement' => $assignments->where('review_quality', 'needs_improvement')->count(),
                'not_completed' => $assignments->where('review_quality', 'not_completed')->count(),
            ],
        ];

        return $stats;
    }

    /**
     * Get homework assignments for a specific student
     */
    public function getStudentHomeworkAssignments(User $student, array $filters = []): Collection
    {
        $query = QuranHomeworkAssignment::where('student_id', $student->id)
            ->with(['sessionHomework', 'session', 'evaluator']);

        if (isset($filters['completion_status'])) {
            $query->where('completion_status', $filters['completion_status']);
        }

        if (isset($filters['date_from'])) {
            $query->whereHas('session', function ($q) use ($filters) {
                $q->where('scheduled_at', '>=', $filters['date_from']);
            });
        }

        if (isset($filters['date_to'])) {
            $query->whereHas('session', function ($q) use ($filters) {
                $q->where('scheduled_at', '<=', $filters['date_to']);
            });
        }

        if (isset($filters['overdue_only']) && $filters['overdue_only']) {
            $query->overdue();
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get homework assignments for a teacher's sessions
     */
    public function getTeacherHomeworkAssignments(User $teacher, array $filters = []): Collection
    {
        $query = QuranHomeworkAssignment::whereHas('session', function ($q) use ($teacher) {
            $q->where('quran_teacher_id', $teacher->id);
        })->with(['sessionHomework', 'session', 'student']);

        if (isset($filters['completion_status'])) {
            $query->where('completion_status', $filters['completion_status']);
        }

        if (isset($filters['session_id'])) {
            $query->where('session_id', $filters['session_id']);
        }

        if (isset($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        if (isset($filters['needs_evaluation']) && $filters['needs_evaluation']) {
            $query->whereNull('evaluated_by_teacher_at');
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Delete session homework and all related assignments
     */
    public function deleteSessionHomework(QuranSessionHomework $homework): bool
    {
        return DB::transaction(function () use ($homework) {
            // Delete all assignments first
            $homework->assignments()->delete();

            // Delete the homework
            $deleted = $homework->delete();

            Log::info('Session homework deleted', [
                'homework_id' => $homework->id,
                'session_id' => $homework->session_id,
                'deleted_by' => auth()->id(),
            ]);

            return $deleted;
        });
    }

    /**
     * Generate homework progress report for a session
     */
    public function generateHomeworkProgressReport(QuranSession $session): array
    {
        $stats = $this->getSessionHomeworkStats($session);

        if (! $stats['has_homework']) {
            return ['error' => 'No homework assigned for this session'];
        }

        $report = [
            'session_info' => [
                'id' => $session->id,
                'title' => $session->title,
                'scheduled_at' => $session->scheduled_at,
                'session_type' => $session->session_type,
            ],
            'homework_overview' => [
                'total_pages' => $stats['total_pages'],
                'new_memorization_pages' => $stats['new_memorization_pages'],
                'review_pages' => $stats['review_pages'],
                'due_date' => $stats['due_date'],
                'is_overdue' => $stats['is_overdue'],
            ],
            'completion_summary' => [
                'total_students' => $stats['total_students'],
                'completed_count' => $stats['completed_count'],
                'in_progress_count' => $stats['in_progress_count'],
                'not_started_count' => $stats['not_started_count'],
                'completion_rate' => $stats['total_students'] > 0
                    ? round(($stats['completed_count'] / $stats['total_students']) * 100, 1)
                    : 0,
                'average_completion' => round($stats['average_completion'], 1),
                'average_score' => round($stats['average_score'], 1),
            ],
            'quality_analysis' => $stats['quality_stats'],
            'student_details' => $stats['assignments']->map(function ($assignment) {
                return [
                    'student_name' => $assignment->student->name,
                    'completion_status' => $assignment->completion_status_arabic,
                    'completion_percentage' => round($assignment->completion_percentage, 1),
                    'overall_score' => $assignment->overall_score,
                    'performance_grade' => $assignment->performance_grade,
                    'new_memorization_completed' => $assignment->new_memorization_completed_pages,
                    'review_completed' => $assignment->review_completed_pages,
                    'is_evaluated' => $assignment->is_evaluated,
                    'is_overdue' => $assignment->is_overdue,
                ];
            })->toArray(),
        ];

        return $report;
    }

    /**
     * Mark student homework as submitted
     */
    public function markHomeworkAsSubmitted(QuranHomeworkAssignment $assignment): QuranHomeworkAssignment
    {
        $assignment->update([
            'submitted_by_student' => true,
            'submitted_at' => now(),
        ]);

        Log::info('Homework marked as submitted', [
            'assignment_id' => $assignment->id,
            'student_id' => $assignment->student_id,
            'submitted_at' => now(),
        ]);

        return $assignment;
    }

    /**
     * Get overdue homework assignments across the academy
     */
    public function getOverdueHomeworkAssignments(int $academyId): Collection
    {
        return QuranHomeworkAssignment::overdue()
            ->whereHas('session', function ($q) use ($academyId) {
                $q->where('academy_id', $academyId);
            })
            ->with(['sessionHomework', 'session', 'student'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
