<?php

namespace App\Services\Student;

use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\InteractiveCourseSession;
use App\Models\RecordedCourse;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Service for managing student's course enrollments and access.
 *
 * Handles:
 * - Interactive course queries
 * - Recorded course access
 * - Course progress tracking
 */
class StudentCourseService
{
    /**
     * Get interactive courses with enrollment status for a student.
     *
     * @param User $user
     * @param Request $request
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getInteractiveCourses(User $user, Request $request, int $perPage = 12): LengthAwarePaginator
    {
        $academy = $user->academy;

        // Ensure user has a student profile
        if (!$user->studentProfile) {
            throw new \Exception('يجب إكمال الملف الشخصي للطالب أولاً');
        }

        $studentId = $user->studentProfile->id;

        // Get all interactive courses with student enrollment data
        $query = InteractiveCourse::where('academy_id', $academy->id)
            ->where('is_published', true)
            ->where('enrollment_deadline', '>=', now()->toDateString())
            ->with(['assignedTeacher', 'subject', 'gradeLevel', 'enrollments' => function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            }]);

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%'.$request->search.'%')
                    ->orWhere('description', 'LIKE', '%'.$request->search.'%');
            });
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('grade_level_id')) {
            $query->where('grade_level_id', $request->grade_level_id);
        }

        // Order by enrollment status (enrolled courses first), then by creation date
        $allCourses = $query->get()->sortByDesc(function ($course) {
            return $course->enrollments->isNotEmpty() ? 1 : 0;
        })->values();

        // Paginate manually
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $allCourses->slice($offset, $perPage),
            $allCourses->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    /**
     * Get enrolled courses count for a student.
     *
     * @param User $user
     * @return int
     */
    public function getEnrolledCoursesCount(User $user): int
    {
        $academy = $user->academy;

        if (!$user->studentProfile) {
            return 0;
        }

        $studentId = $user->studentProfile->id;

        return InteractiveCourse::where('academy_id', $academy->id)
            ->whereHas('enrollments', function ($query) use ($studentId) {
                $query->where('student_id', $studentId)
                      ->whereIn('enrollment_status', ['enrolled', 'completed']);
            })
            ->count();
    }

    /**
     * Get filter options for interactive courses.
     *
     * @param User $user
     * @return array
     */
    public function getCourseFilterOptions(User $user): array
    {
        $academy = $user->academy;

        $subjects = AcademicSubject::where('academy_id', $academy->id)
            ->whereHas('interactiveCourses', function ($query) {
                $query->where('is_published', true)
                      ->where('enrollment_deadline', '>=', now()->toDateString());
            })
            ->orderBy('name')
            ->get();

        $gradeLevels = AcademicGradeLevel::where('academy_id', $academy->id)
            ->whereHas('interactiveCourses', function ($query) {
                $query->where('is_published', true)
                      ->where('enrollment_deadline', '>=', now()->toDateString());
            })
            ->orderBy('name')
            ->get();

        return [
            'subjects' => $subjects,
            'gradeLevels' => $gradeLevels,
        ];
    }

    /**
     * Get interactive course details with enrollment info.
     *
     * @param User $user
     * @param string $courseId
     * @return array|null
     */
    public function getInteractiveCourseDetails(User $user, string $courseId): ?array
    {
        $academy = $user->academy;

        $course = InteractiveCourse::where('id', $courseId)
            ->where('academy_id', $academy->id)
            ->first();

        if (!$course) {
            return null;
        }

        if (!$user->studentProfile) {
            throw new \Exception('يجب إكمال الملف الشخصي للطالب أولاً');
        }

        $studentId = $user->studentProfile->id;

        // Load course relationships with student-specific session data
        $course->load([
            'assignedTeacher.user',
            'subject',
            'gradeLevel',
            'enrollments.student.user',
            'sessions' => function ($query) use ($user) {
                $query->with([
                    'attendances' => function ($q) use ($user) {
                        if ($user->studentProfile) {
                            $q->where('student_id', $user->studentProfile->id);
                        }
                    },
                    'homework.submissions' => function ($q) use ($user) {
                        if ($user->studentProfile) {
                            $q->where('student_id', $user->studentProfile->id);
                        }
                    },
                ])->orderBy('scheduled_at');
            },
        ]);

        // Check enrollment
        $enrollment = $course->enrollments->where('student_id', $studentId)->first();
        $isEnrolled = $enrollment && in_array($enrollment->enrollment_status, ['enrolled', 'completed']);

        // Enrollment stats
        $enrollmentStats = [
            'total_enrolled' => $course->enrollments->count(),
            'available_spots' => max(0, $course->max_students - $course->enrollments->count()),
            'enrollment_deadline' => $course->enrollment_deadline,
        ];

        // Separate sessions
        $now = now();
        $upcomingSessions = $course->sessions
            ->filter(function ($session) use ($now) {
                $scheduledDateTime = $session->scheduled_at;
                return $scheduledDateTime && ($scheduledDateTime->gte($now) || $session->status === 'in-progress');
            })
            ->values();

        $pastSessions = $course->sessions
            ->filter(function ($session) use ($now) {
                $scheduledDateTime = $session->scheduled_at;
                return $scheduledDateTime && $scheduledDateTime->lt($now) && $session->status !== 'in-progress';
            })
            ->sortByDesc(function ($session) {
                return $session->scheduled_at ? $session->scheduled_at->timestamp : 0;
            })
            ->values();

        return [
            'course' => $course,
            'isEnrolled' => $isEnrolled,
            'enrollment' => $enrollment,
            'enrollmentStats' => $enrollmentStats,
            'upcomingSessions' => $upcomingSessions,
            'pastSessions' => $pastSessions,
        ];
    }

    /**
     * Get interactive course session details for a student.
     *
     * @param User $user
     * @param string $sessionId
     * @return array|null
     */
    public function getInteractiveCourseSessionDetails(User $user, string $sessionId): ?array
    {
        $academy = $user->academy;

        // Find the session with relationships
        $session = InteractiveCourseSession::with([
            'course.assignedTeacher.user',
            'course.subject',
            'course.gradeLevel',
            'course.enrolledStudents.student.user',
            'homework',
            'attendances',
            'meetingAttendances',
        ])->find($sessionId);

        if (!$session) {
            return null;
        }

        // Ensure session's course belongs to the academy
        if ($session->course->academy_id !== $academy->id) {
            return null;
        }

        // Get the student profile
        $studentProfile = $user->studentProfile;
        if (!$studentProfile) {
            throw new \Exception('Student profile not found');
        }

        // Verify enrollment in the course
        $enrollment = InteractiveCourseEnrollment::where([
            'course_id' => $session->course_id,
            'student_id' => $studentProfile->id,
            'enrollment_status' => 'enrolled'
        ])->first();

        if (!$enrollment) {
            return null;
        }

        // Get attendance record for this student
        $attendance = $session->attendances->where('student_id', $studentProfile->id)->first();

        // Get homework submission if homework exists
        $homeworkSubmission = null;
        if ($session->homework()->count() > 0) {
            $homework = $session->homework()->first();
            if ($homework) {
                $homeworkSubmission = $homework->submissions()
                    ->where('student_id', $studentProfile->id)
                    ->first();
            }
        }

        return [
            'session' => $session,
            'attendance' => $attendance,
            'homeworkSubmission' => $homeworkSubmission,
            'student' => $studentProfile,
            'enrollment' => $enrollment,
        ];
    }

    /**
     * Get recorded courses for a student.
     *
     * @param User $user
     * @return Collection
     */
    public function getRecordedCourses(User $user): Collection
    {
        $academy = $user->academy;

        return RecordedCourse::where('academy_id', $academy->id)
            ->where('is_published', true)
            ->with(['instructor'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get student's enrolled courses (for enrollments list).
     *
     * @param User $user
     * @return Collection
     */
    public function getCourseEnrollments(User $user): Collection
    {
        $academy = $user->academy;

        return InteractiveCourse::where('academy_id', $academy->id)
            ->whereHas('enrollments', function ($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->with(['assignedTeacher', 'enrollments' => function ($query) use ($user) {
                $query->where('student_id', $user->id);
            }])
            ->get();
    }
}
