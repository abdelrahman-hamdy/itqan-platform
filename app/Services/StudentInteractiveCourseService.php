<?php

namespace App\Services;

use Exception;
use App\Enums\HomeworkSubmissionStatus;
use App\Enums\SessionStatus;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseHomework;
use App\Models\InteractiveCourseHomeworkSubmission;
use App\Models\InteractiveCourseSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing student's interactive course enrollments and interactions.
 *
 * Extracted from StudentProfileController to reduce controller complexity
 * and enable reuse across different contexts (web, API, etc.).
 */
class StudentInteractiveCourseService
{
    public function __construct(
        private readonly UnifiedHomeworkService $homeworkService,
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Get all interactive courses for a student.
     *
     * @param  User  $user  The student user
     * @param  string|null  $status  Filter by status ('active', 'completed', 'all')
     */
    public function getStudentCourses(User $user, ?string $status = 'active'): Collection
    {
        $query = InteractiveCourse::whereHas('enrollments', function ($q) use ($user, $status) {
            $q->where('student_id', $user->id);
            if ($status && $status !== 'all') {
                $q->where('status', $status);
            }
        })
            ->with([
                'assignedTeacher.user',
                'academy',
                'enrollments' => function ($q) use ($user) {
                    $q->where('student_id', $user->id);
                },
            ]);

        return $query->get();
    }

    /**
     * Get detailed course information for a student.
     *
     * @param  User  $user  The student user
     * @param  int  $courseId  The course ID
     * @return array|null Course data or null if not enrolled
     */
    public function getCourseDetails(User $user, int $courseId): ?array
    {
        $course = InteractiveCourse::with([
            'assignedTeacher.user',
            'academy',
            'sessions' => function ($q) {
                $q->orderBy('session_number');
            },
        ])->find($courseId);

        if (! $course) {
            return null;
        }

        // Check enrollment
        $enrollment = $course->enrollments()
            ->where('student_id', $user->id)
            ->first();

        if (! $enrollment) {
            return null;
        }

        // Get session progress
        $sessions = $course->sessions()
            ->with(['interactiveSessionReports' => function ($q) use ($user) {
                $q->where('student_id', $user->id);
            }])
            ->get();

        // Calculate progress
        $stats = $this->calculateCourseProgress($user, $course, $sessions);

        return [
            'course' => $course,
            'enrollment' => $enrollment,
            'sessions' => $sessions,
            'stats' => $stats,
        ];
    }

    /**
     * Get session details for a student.
     *
     * @param  User  $user  The student user
     * @param  int  $sessionId  The session ID
     * @return array|null Session data or null if not enrolled
     */
    public function getSessionDetails(User $user, int $sessionId): ?array
    {
        $session = InteractiveCourseSession::with([
            'course.assignedTeacher.user',
            'course.academy',
            'interactiveSessionReports' => function ($q) use ($user) {
                $q->where('student_id', $user->id);
            },
            'homeworkSubmissions' => function ($q) use ($user) {
                $q->where('student_id', $user->id);
            },
        ])->find($sessionId);

        if (! $session) {
            return null;
        }

        // Check enrollment
        $enrollment = $session->course->enrollments()
            ->where('student_id', $user->id)
            ->first();

        if (! $enrollment) {
            return null;
        }

        return [
            'session' => $session,
            'enrollment' => $enrollment,
            'report' => $session->interactiveSessionReports->first(),
            'homework_submission' => $session->homeworkSubmissions->first(),
        ];
    }

    /**
     * Submit homework for a session.
     *
     * @param  User  $user  The student user
     * @param  int  $sessionId  The session ID
     * @param  string  $content  The homework content
     * @param  UploadedFile|null  $file  Optional file attachment
     * @return array{success: bool, message: string, submission?: InteractiveCourseHomeworkSubmission}
     */
    public function submitHomework(
        User $user,
        int $sessionId,
        string $content,
        ?UploadedFile $file = null
    ): array {
        try {
            $session = InteractiveCourseSession::find($sessionId);

            if (! $session) {
                return ['success' => false, 'message' => 'الجلسة غير موجودة'];
            }

            // Verify enrollment
            $enrollment = $session->course->enrollments()
                ->where('student_id', $user->id)
                ->first();

            if (! $enrollment) {
                return ['success' => false, 'message' => 'غير مسجل في هذا الكورس'];
            }

            // Check if homework exists
            if (empty($session->homework_description) && empty($session->homework_file)) {
                return ['success' => false, 'message' => 'لا يوجد واجب لهذه الجلسة'];
            }

            return DB::transaction(function () use ($user, $session, $content, $file) {
                // Handle file upload
                $studentFiles = [];
                if ($file) {
                    $filePath = $file->store('homework_submissions/'.date('Y/m'), 'public');
                    $studentFiles[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $filePath,
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType(),
                    ];
                }

                // Get or create homework assignment
                $homework = $session->homework()->first();
                if (! $homework) {
                    // Create a homework assignment from session data
                    $homework = InteractiveCourseHomework::create([
                        'academy_id' => $session->academy_id ?? $session->course?->academy_id,
                        'interactive_course_session_id' => $session->id,
                        'teacher_id' => $session->course?->assigned_teacher_id,
                        'title' => 'واجب الجلسة '.$session->session_number,
                        'description' => $session->homework_description,
                        'due_date' => $session->homework_due_date,
                    ]);
                }

                // Create or update submission using InteractiveCourseHomeworkSubmission
                $submission = InteractiveCourseHomeworkSubmission::updateOrCreate(
                    [
                        'interactive_course_homework_id' => $homework->id,
                        'student_id' => $user->id,
                    ],
                    [
                        'academy_id' => $homework->academy_id,
                        'interactive_course_session_id' => $session->id,
                        'submission_text' => $content,
                        'submission_files' => $studentFiles,
                        'submitted_at' => now(),
                        'submission_status' => HomeworkSubmissionStatus::SUBMITTED,
                        'max_score' => 10,
                    ]
                );

                // Update homework statistics
                $homework->updateStatistics();

                // Notify teacher
                $this->notificationService->sendHomeworkSubmittedNotification(
                    $session->course->assignedTeacher?->user,
                    $session,
                    $user
                );

                return [
                    'success' => true,
                    'message' => 'تم تسليم الواجب بنجاح',
                    'submission' => $submission,
                ];
            });
        } catch (Exception $e) {
            Log::error('Failed to submit homework', [
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تسليم الواجب',
            ];
        }
    }

    /**
     * Add session feedback from student.
     *
     * @param  User  $user  The student user
     * @param  int  $sessionId  The session ID
     * @param  int  $rating  Rating (1-5)
     * @param  string|null  $comment  Optional comment
     * @return array{success: bool, message: string}
     */
    public function addSessionFeedback(
        User $user,
        int $sessionId,
        int $rating,
        ?string $comment = null
    ): array {
        try {
            $session = InteractiveCourseSession::find($sessionId);

            if (! $session) {
                return ['success' => false, 'message' => 'الجلسة غير موجودة'];
            }

            // Verify enrollment
            $enrollment = $session->course->enrollments()
                ->where('student_id', $user->id)
                ->first();

            if (! $enrollment) {
                return ['success' => false, 'message' => 'غير مسجل في هذا الكورس'];
            }

            // Validate rating
            if ($rating < 1 || $rating > 5) {
                return ['success' => false, 'message' => 'التقييم يجب أن يكون بين 1 و 5'];
            }

            // Update or create session report
            $session->interactiveSessionReports()->updateOrCreate(
                ['student_id' => $user->id],
                [
                    'student_rating' => $rating,
                    'student_feedback' => $comment,
                    'evaluated_at' => now(),
                ]
            );

            return [
                'success' => true,
                'message' => 'تم إضافة التقييم بنجاح',
            ];
        } catch (Exception $e) {
            Log::error('Failed to add session feedback', [
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة التقييم',
            ];
        }
    }

    /**
     * Get course report for a student.
     *
     * @param  User  $user  The student user
     * @param  int  $courseId  The course ID
     * @return array|null Report data or null if not enrolled
     */
    public function getCourseReport(User $user, int $courseId): ?array
    {
        $courseData = $this->getCourseDetails($user, $courseId);

        if (! $courseData) {
            return null;
        }

        $sessions = $courseData['sessions'];
        $completedSessions = $sessions->filter(fn ($s) => in_array($s->status, [SessionStatus::COMPLETED->value, SessionStatus::COMPLETED])
        );

        // Calculate detailed statistics
        $attendedSessions = $completedSessions->filter(function ($session) use ($user) {
            $report = $session->interactiveSessionReports
                ->where('student_id', $user->id)
                ->first();

            return $report && $report->attendance_status === 'attended';
        });

        $homeworkSubmissions = InteractiveCourseHomeworkSubmission::where('student_id', $user->id)
            ->whereIn('interactive_course_session_id', $sessions->pluck('id'))
            ->get();

        return [
            'course' => $courseData['course'],
            'enrollment' => $courseData['enrollment'],
            'total_sessions' => $sessions->count(),
            'completed_sessions' => $completedSessions->count(),
            'attended_sessions' => $attendedSessions->count(),
            'attendance_rate' => $completedSessions->count() > 0
                ? round(($attendedSessions->count() / $completedSessions->count()) * 100, 1)
                : 0,
            'homework_submissions' => $homeworkSubmissions->count(),
            'sessions_with_homework' => $sessions->filter(fn ($s) => ! empty($s->homework_description) || ! empty($s->homework_file)
            )->count(),
            'session_details' => $sessions->map(function ($session) use ($user) {
                $report = $session->interactiveSessionReports
                    ->where('student_id', $user->id)
                    ->first();

                return [
                    'id' => $session->id,
                    'session_number' => $session->session_number,
                    'title' => $session->title,
                    'status' => $session->status,
                    'scheduled_at' => $session->scheduled_at,
                    'attendance_status' => $report?->attendance_status ?? 'pending',
                    'has_homework' => ! empty($session->homework_description),
                ];
            }),
        ];
    }

    /**
     * Calculate course progress for a student.
     */
    private function calculateCourseProgress(User $user, InteractiveCourse $course, $sessions): array
    {
        $totalSessions = $sessions->count();
        $completedSessions = $sessions->filter(fn ($s) => in_array($s->status, [SessionStatus::COMPLETED->value, SessionStatus::COMPLETED])
        )->count();

        $attendedSessions = $sessions->filter(function ($session) use ($user) {
            $report = $session->interactiveSessionReports
                ->where('student_id', $user->id)
                ->first();

            return $report && $report->attendance_status === 'attended';
        })->count();

        $upcomingSessions = $sessions->filter(fn ($s) => in_array($s->status, [SessionStatus::SCHEDULED->value, SessionStatus::SCHEDULED])
        )->count();

        return [
            'total_sessions' => $totalSessions,
            'completed_sessions' => $completedSessions,
            'attended_sessions' => $attendedSessions,
            'upcoming_sessions' => $upcomingSessions,
            'progress_percentage' => $totalSessions > 0
                ? round(($completedSessions / $totalSessions) * 100, 1)
                : 0,
            'attendance_rate' => $completedSessions > 0
                ? round(($attendedSessions / $completedSessions) * 100, 1)
                : 0,
        ];
    }
}
