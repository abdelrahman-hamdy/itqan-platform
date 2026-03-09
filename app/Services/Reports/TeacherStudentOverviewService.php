<?php

namespace App\Services\Reports;

use App\Enums\AttendanceStatus;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSessionReport;
use App\Models\InteractiveCourse;
use App\Models\InteractiveSessionReport;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\StudentSessionReport;
use App\Models\User;
use Illuminate\Support\Collection;

class TeacherStudentOverviewService
{
    /**
     * Get student overview rows for a teacher with aggregate stats.
     *
     * @return Collection<int, object>
     */
    public function getStudentOverviewForTeacher(
        User $user,
        ?string $type = null,
        ?int $entityId = null,
        ?string $studentSearch = null
    ): Collection {
        $rows = collect();

        if ($user->isQuranTeacher()) {
            if (! $type || $type === 'quran_individual') {
                $rows = $rows->merge($this->getQuranIndividualRows($user, $entityId, $studentSearch));
            }
            if (! $type || $type === 'quran_group') {
                $rows = $rows->merge($this->getQuranGroupRows($user, $entityId, $studentSearch));
            }
        }

        if ($user->isAcademicTeacher()) {
            $profileId = $user->academicTeacherProfile?->id;
            if ($profileId) {
                if (! $type || $type === 'academic') {
                    $rows = $rows->merge($this->getAcademicRows($profileId, $entityId, $studentSearch));
                }
                if (! $type || $type === 'interactive') {
                    $rows = $rows->merge($this->getInteractiveRows($profileId, $entityId, $studentSearch));
                }
            }
        }

        return $rows->sortBy('student_name')->values();
    }

    /**
     * Build entity dropdown options grouped by type.
     */
    public function buildEntityOptions(User $user): array
    {
        $options = [];

        if ($user->isQuranTeacher()) {
            $individualCircles = QuranIndividualCircle::where('quran_teacher_id', $user->id)
                ->where('is_active', true)
                ->with('student:id,first_name,last_name,name')
                ->get();

            foreach ($individualCircles as $circle) {
                $options['quran_individual'][] = [
                    'id' => $circle->id,
                    'name' => $circle->name ?: ($circle->student?->name ?? __('teacher.reports.unknown_student')),
                ];
            }

            $groupCircles = QuranCircle::where('quran_teacher_id', $user->id)
                ->active()
                ->get(['id', 'name']);

            foreach ($groupCircles as $circle) {
                $options['quran_group'][] = [
                    'id' => $circle->id,
                    'name' => $circle->name,
                ];
            }
        }

        if ($user->isAcademicTeacher()) {
            $profileId = $user->academicTeacherProfile?->id;
            if ($profileId) {
                $lessons = AcademicIndividualLesson::where('academic_teacher_id', $profileId)
                    ->with('student:id,first_name,last_name,name')
                    ->get(['id', 'name', 'student_id']);

                foreach ($lessons as $lesson) {
                    $options['academic'][] = [
                        'id' => $lesson->id,
                        'name' => $lesson->name ?: ($lesson->student?->name ?? __('teacher.reports.unknown_student')),
                    ];
                }

                $courses = InteractiveCourse::where('assigned_teacher_id', $profileId)
                    ->get(['id', 'title']);

                foreach ($courses as $course) {
                    $options['interactive'][] = [
                        'id' => $course->id,
                        'name' => $course->title,
                    ];
                }
            }
        }

        return $options;
    }

    /**
     * Quran Individual Circles: 1 student per circle.
     */
    private function getQuranIndividualRows(User $user, ?int $entityId, ?string $studentSearch): Collection
    {
        $query = QuranIndividualCircle::where('quran_teacher_id', $user->id)
            ->where('is_active', true)
            ->with('student:id,first_name,last_name,name,avatar');

        if ($entityId) {
            $query->where('id', $entityId);
        }

        $circles = $query->get();

        if ($studentSearch) {
            $circles = $circles->filter(fn ($c) => $c->student && str_contains(
                mb_strtolower($c->student->name),
                mb_strtolower($studentSearch)
            ));
        }

        return $circles->map(function ($circle) use ($user) {
            $reports = StudentSessionReport::where('teacher_id', $user->id)
                ->whereHas('session', fn ($q) => $q->where('individual_circle_id', $circle->id))
                ->get();

            $totalSessions = $reports->count();
            $attendedCount = $reports->whereIn('attendance_status', [
                AttendanceStatus::ATTENDED,
                AttendanceStatus::LATE,
            ])->count();

            $attendanceRate = $totalSessions > 0 ? round(($attendedCount / $totalSessions) * 100) : 0;
            $avgPerformance = $reports->whereNotNull('overall_performance')->avg('overall_performance');

            return (object) [
                'entity_type' => 'quran_individual',
                'entity_name' => $circle->name ?: ($circle->student?->name ?? __('teacher.reports.unknown_student')),
                'student_id' => $circle->student_id,
                'student_name' => $circle->student?->name ?? __('teacher.reports.unknown_student'),
                'student' => $circle->student,
                'attendance_rate' => $attendanceRate,
                'sessions_completed' => $totalSessions,
                'avg_performance' => $avgPerformance ? round($avgPerformance, 1) : null,
                'report_route' => 'teacher.individual-circles.report',
                'report_params' => ['circle' => $circle->id],
            ];
        })->values();
    }

    /**
     * Quran Group Circles: multiple students per circle.
     */
    private function getQuranGroupRows(User $user, ?int $entityId, ?string $studentSearch): Collection
    {
        $query = QuranCircle::where('quran_teacher_id', $user->id)
            ->active()
            ->with('students:id,first_name,last_name,name,avatar');

        if ($entityId) {
            $query->where('id', $entityId);
        }

        $circles = $query->get();
        $rows = collect();

        foreach ($circles as $circle) {
            $students = $circle->students;

            if ($studentSearch) {
                $students = $students->filter(fn ($s) => str_contains(
                    mb_strtolower($s->name),
                    mb_strtolower($studentSearch)
                ));
            }

            foreach ($students as $student) {
                $reports = StudentSessionReport::where('teacher_id', $user->id)
                    ->where('student_id', $student->id)
                    ->whereHas('session', fn ($q) => $q->where('circle_id', $circle->id))
                    ->get();

                $totalSessions = $reports->count();
                $attendedCount = $reports->whereIn('attendance_status', [
                    AttendanceStatus::ATTENDED,
                    AttendanceStatus::LATE,
                ])->count();

                $attendanceRate = $totalSessions > 0 ? round(($attendedCount / $totalSessions) * 100) : 0;
                $avgPerformance = $reports->whereNotNull('overall_performance')->avg('overall_performance');

                $rows->push((object) [
                    'entity_type' => 'quran_group',
                    'entity_name' => $circle->name,
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'student' => $student,
                    'attendance_rate' => $attendanceRate,
                    'sessions_completed' => $totalSessions,
                    'avg_performance' => $avgPerformance ? round($avgPerformance, 1) : null,
                    'report_route' => 'teacher.group-circles.student-report',
                    'report_params' => ['circle' => $circle->id, 'student' => $student->id],
                ]);
            }
        }

        return $rows;
    }

    /**
     * Academic Individual Lessons: 1 student per lesson.
     */
    private function getAcademicRows(int $profileId, ?int $entityId, ?string $studentSearch): Collection
    {
        $query = AcademicIndividualLesson::where('academic_teacher_id', $profileId)
            ->with(['student:id,first_name,last_name,name,avatar', 'academicSubscription:id']);

        if ($entityId) {
            $query->where('id', $entityId);
        }

        $lessons = $query->get();

        if ($studentSearch) {
            $lessons = $lessons->filter(fn ($l) => $l->student && str_contains(
                mb_strtolower($l->student->name),
                mb_strtolower($studentSearch)
            ));
        }

        return $lessons->map(function ($lesson) {
            $reports = AcademicSessionReport::whereHas(
                'session',
                fn ($q) => $q->where('academic_individual_lesson_id', $lesson->id)
            )->get();

            $totalSessions = $reports->count();
            $attendedCount = $reports->whereIn('attendance_status', [
                AttendanceStatus::ATTENDED,
                AttendanceStatus::LATE,
            ])->count();

            $attendanceRate = $totalSessions > 0 ? round(($attendedCount / $totalSessions) * 100) : 0;
            $avgPerformance = $reports->whereNotNull('overall_performance')->avg('overall_performance');

            // Academic report route uses subscription ID
            $subscriptionId = $lesson->academicSubscription?->id;

            return (object) [
                'entity_type' => 'academic',
                'entity_name' => $lesson->name ?: ($lesson->student?->name ?? __('teacher.reports.unknown_student')),
                'student_id' => $lesson->student_id,
                'student_name' => $lesson->student?->name ?? __('teacher.reports.unknown_student'),
                'student' => $lesson->student,
                'attendance_rate' => $attendanceRate,
                'sessions_completed' => $totalSessions,
                'avg_performance' => $avgPerformance ? round($avgPerformance, 1) : null,
                'report_route' => $subscriptionId ? 'teacher.academic-subscriptions.report' : null,
                'report_params' => $subscriptionId ? ['subscription' => $subscriptionId] : [],
            ];
        })->values();
    }

    /**
     * Interactive Courses: multiple students via enrollments.
     */
    private function getInteractiveRows(int $profileId, ?int $entityId, ?string $studentSearch): Collection
    {
        $query = InteractiveCourse::where('assigned_teacher_id', $profileId)
            ->with(['enrolledStudents.student.user:id,first_name,last_name,name,avatar']);

        if ($entityId) {
            $query->where('id', $entityId);
        }

        $courses = $query->get();
        $rows = collect();

        foreach ($courses as $course) {
            foreach ($course->enrolledStudents as $enrollment) {
                $studentUser = $enrollment->student?->user;
                if (! $studentUser) {
                    continue;
                }

                if ($studentSearch && ! str_contains(
                    mb_strtolower($studentUser->name),
                    mb_strtolower($studentSearch)
                )) {
                    continue;
                }

                $reports = InteractiveSessionReport::where('student_id', $studentUser->id)
                    ->whereHas('session', fn ($q) => $q->where('interactive_course_id', $course->id))
                    ->get();

                $totalSessions = $reports->count();
                $attendedCount = $reports->whereIn('attendance_status', [
                    AttendanceStatus::ATTENDED,
                    AttendanceStatus::LATE,
                ])->count();

                $attendanceRate = $totalSessions > 0 ? round(($attendedCount / $totalSessions) * 100) : 0;
                $avgPerformance = $reports->whereNotNull('overall_performance')->avg('overall_performance');

                $rows->push((object) [
                    'entity_type' => 'interactive',
                    'entity_name' => $course->title,
                    'student_id' => $studentUser->id,
                    'student_name' => $studentUser->name,
                    'student' => $studentUser,
                    'attendance_rate' => $attendanceRate,
                    'sessions_completed' => $totalSessions,
                    'avg_performance' => $avgPerformance ? round($avgPerformance, 1) : null,
                    'report_route' => 'teacher.interactive-courses.student-report',
                    'report_params' => ['course' => $course->id, 'student' => $studentUser->id],
                ]);
            }
        }

        return $rows;
    }
}
