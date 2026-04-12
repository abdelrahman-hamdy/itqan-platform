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

class StudentOverviewService
{
    /**
     * Resolve teacher ID arrays from a User model (teacher context).
     *
     * @return array{0: int[], 1: int[]} [$quranTeacherIds, $academicProfileIds]
     */
    public function forTeacher(User $user): array
    {
        $quranTeacherIds = $user->isQuranTeacher() ? [$user->id] : [];

        $academicProfileIds = [];
        if ($user->isAcademicTeacher()) {
            $profileId = $user->academicTeacherProfile?->id;
            if ($profileId) {
                $academicProfileIds = [$profileId];
            }
        }

        return [$quranTeacherIds, $academicProfileIds];
    }

    /**
     * Get student overview rows with aggregate stats.
     *
     * @param  int[]  $quranTeacherIds
     * @param  int[]  $academicProfileIds
     * @param  string  $routePrefix  'teacher' or 'manage'
     * @return Collection<int, object>
     */
    public function getStudentOverview(
        array $quranTeacherIds,
        array $academicProfileIds,
        ?string $type = null,
        ?int $entityId = null,
        ?string $studentSearch = null,
        string $routePrefix = 'teacher',
    ): Collection {
        $rows = collect();

        if (! empty($quranTeacherIds)) {
            if (! $type || $type === 'quran_individual') {
                $rows = $rows->merge($this->getQuranIndividualRows($quranTeacherIds, $entityId, $studentSearch, $routePrefix));
            }
            if (! $type || $type === 'quran_group') {
                $rows = $rows->merge($this->getQuranGroupRows($quranTeacherIds, $entityId, $studentSearch, $routePrefix));
            }
        }

        if (! empty($academicProfileIds)) {
            if (! $type || $type === 'academic') {
                $rows = $rows->merge($this->getAcademicRows($academicProfileIds, $entityId, $studentSearch, $routePrefix));
            }
            if (! $type || $type === 'interactive') {
                $rows = $rows->merge($this->getInteractiveRows($academicProfileIds, $entityId, $studentSearch, $routePrefix));
            }
        }

        return $rows->sortBy('student_name')->values();
    }

    /**
     * Build entity dropdown options grouped by type.
     *
     * @param  int[]  $quranTeacherIds
     * @param  int[]  $academicProfileIds
     */
    public function buildEntityOptions(array $quranTeacherIds, array $academicProfileIds): array
    {
        $options = [];

        if (! empty($quranTeacherIds)) {
            $individualCircles = QuranIndividualCircle::whereIn('quran_teacher_id', $quranTeacherIds)
                ->where('is_active', true)
                ->with('student:id,first_name,last_name,name')
                ->get();

            foreach ($individualCircles as $circle) {
                $options['quran_individual'][] = [
                    'id' => $circle->id,
                    'name' => $circle->name ?: ($circle->student?->name ?? __('reports.unknown_student')),
                ];
            }

            $groupCircles = QuranCircle::whereIn('quran_teacher_id', $quranTeacherIds)
                ->active()
                ->get(['id', 'name']);

            foreach ($groupCircles as $circle) {
                $options['quran_group'][] = [
                    'id' => $circle->id,
                    'name' => $circle->name,
                ];
            }
        }

        if (! empty($academicProfileIds)) {
            $lessons = AcademicIndividualLesson::whereIn('academic_teacher_id', $academicProfileIds)
                ->with('student:id,first_name,last_name,name')
                ->get(['id', 'name', 'student_id', 'academic_teacher_id']);

            foreach ($lessons as $lesson) {
                $options['academic'][] = [
                    'id' => $lesson->id,
                    'name' => $lesson->name ?: ($lesson->student?->name ?? __('reports.unknown_student')),
                ];
            }

            $courses = InteractiveCourse::whereIn('assigned_teacher_id', $academicProfileIds)
                ->get(['id', 'title']);

            foreach ($courses as $course) {
                $options['interactive'][] = [
                    'id' => $course->id,
                    'name' => $course->title,
                ];
            }
        }

        return $options;
    }

    /**
     * Quran Individual Circles: 1 student per circle.
     */
    private function getQuranIndividualRows(array $teacherIds, ?int $entityId, ?string $studentSearch, string $routePrefix): Collection
    {
        $query = QuranIndividualCircle::whereIn('quran_teacher_id', $teacherIds)
            ->where('is_active', true)
            ->with(['student:id,first_name,last_name,name,avatar', 'quranTeacher:id,first_name,last_name,name']);

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

        return $circles->map(function ($circle) use ($routePrefix) {
            $reports = StudentSessionReport::whereIn('teacher_id', [$circle->quran_teacher_id])
                ->whereHas('session', fn ($q) => $q->where('individual_circle_id', $circle->id))
                ->get();

            $totalSessions = $reports->count();
            $attendedCount = $reports->whereIn('attendance_status', AttendanceStatus::presentValues())->count();

            $attendanceRate = $totalSessions > 0 ? round(($attendedCount / $totalSessions) * 100) : 0;
            $avgPerformance = $reports->whereNotNull('overall_performance')->avg('overall_performance');

            return (object) [
                'entity_type' => 'quran_individual',
                'entity_name' => $circle->name ?: ($circle->student?->name ?? __('reports.unknown_student')),
                'student_id' => $circle->student_id,
                'student_name' => $circle->student?->name ?? __('reports.unknown_student'),
                'student' => $circle->student,
                'teacher_name' => $circle->quranTeacher?->name ?? '',
                'attendance_rate' => $attendanceRate,
                'sessions_completed' => $totalSessions,
                'avg_performance' => $avgPerformance ? round($avgPerformance, 1) : null,
                'report_route' => $routePrefix.'.individual-circles.report',
                'report_params' => ['circle' => $circle->id],
            ];
        })->values();
    }

    /**
     * Quran Group Circles: multiple students per circle.
     */
    private function getQuranGroupRows(array $teacherIds, ?int $entityId, ?string $studentSearch, string $routePrefix): Collection
    {
        $query = QuranCircle::whereIn('quran_teacher_id', $teacherIds)
            ->active()
            ->with(['students:id,first_name,last_name,name,avatar', 'quranTeacher:id,first_name,last_name,name']);

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
                $reports = StudentSessionReport::where('teacher_id', $circle->quran_teacher_id)
                    ->where('student_id', $student->id)
                    ->whereHas('session', fn ($q) => $q->where('circle_id', $circle->id))
                    ->get();

                $totalSessions = $reports->count();
                $attendedCount = $reports->whereIn('attendance_status', AttendanceStatus::presentValues())->count();

                $attendanceRate = $totalSessions > 0 ? round(($attendedCount / $totalSessions) * 100) : 0;
                $avgPerformance = $reports->whereNotNull('overall_performance')->avg('overall_performance');

                $rows->push((object) [
                    'entity_type' => 'quran_group',
                    'entity_name' => $circle->name,
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'student' => $student,
                    'teacher_name' => $circle->quranTeacher?->name ?? '',
                    'attendance_rate' => $attendanceRate,
                    'sessions_completed' => $totalSessions,
                    'avg_performance' => $avgPerformance ? round($avgPerformance, 1) : null,
                    'report_route' => $routePrefix.'.group-circles.student-report',
                    'report_params' => ['circle' => $circle->id, 'student' => $student->id],
                ]);
            }
        }

        return $rows;
    }

    /**
     * Academic Individual Lessons: 1 student per lesson.
     */
    private function getAcademicRows(array $profileIds, ?int $entityId, ?string $studentSearch, string $routePrefix): Collection
    {
        $query = AcademicIndividualLesson::whereIn('academic_teacher_id', $profileIds)
            ->with(['student:id,first_name,last_name,name,avatar', 'academicSubscription:id', 'academicTeacher.user:id,first_name,last_name,name']);

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

        return $lessons->map(function ($lesson) use ($routePrefix) {
            $reports = AcademicSessionReport::whereHas(
                'session',
                fn ($q) => $q->where('academic_individual_lesson_id', $lesson->id)
            )->get();

            $totalSessions = $reports->count();
            $attendedCount = $reports->whereIn('attendance_status', AttendanceStatus::presentValues())->count();

            $attendanceRate = $totalSessions > 0 ? round(($attendedCount / $totalSessions) * 100) : 0;
            $avgPerformance = $reports->whereNotNull('overall_performance')->avg('overall_performance');

            $subscriptionId = $lesson->academicSubscription?->id;

            return (object) [
                'entity_type' => 'academic',
                'entity_name' => $lesson->name ?: ($lesson->student?->name ?? __('reports.unknown_student')),
                'student_id' => $lesson->student_id,
                'student_name' => $lesson->student?->name ?? __('reports.unknown_student'),
                'student' => $lesson->student,
                'teacher_name' => $lesson->academicTeacher?->user?->name ?? '',
                'attendance_rate' => $attendanceRate,
                'sessions_completed' => $totalSessions,
                'avg_performance' => $avgPerformance ? round($avgPerformance, 1) : null,
                'report_route' => $subscriptionId ? $routePrefix.'.academic-subscriptions.report' : null,
                'report_params' => $subscriptionId ? ['subscription' => $subscriptionId] : [],
            ];
        })->values();
    }

    /**
     * Interactive Courses: multiple students via enrollments.
     */
    private function getInteractiveRows(array $profileIds, ?int $entityId, ?string $studentSearch, string $routePrefix): Collection
    {
        $query = InteractiveCourse::whereIn('assigned_teacher_id', $profileIds)
            ->with(['enrolledStudents.student.user:id,first_name,last_name,name,avatar', 'assignedTeacher.user:id,first_name,last_name,name']);

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
                $attendedCount = $reports->whereIn('attendance_status', AttendanceStatus::presentValues())->count();

                $attendanceRate = $totalSessions > 0 ? round(($attendedCount / $totalSessions) * 100) : 0;
                $avgPerformance = $reports->whereNotNull('overall_performance')->avg('overall_performance');

                $rows->push((object) [
                    'entity_type' => 'interactive',
                    'entity_name' => $course->title,
                    'student_id' => $studentUser->id,
                    'student_name' => $studentUser->name,
                    'student' => $studentUser,
                    'teacher_name' => $course->assignedTeacher?->user?->name ?? '',
                    'attendance_rate' => $attendanceRate,
                    'sessions_completed' => $totalSessions,
                    'avg_performance' => $avgPerformance ? round($avgPerformance, 1) : null,
                    'report_route' => $routePrefix.'.interactive-courses.student-report',
                    'report_params' => ['course' => $course->id, 'student' => $studentUser->id],
                ]);
            }
        }

        return $rows;
    }
}
