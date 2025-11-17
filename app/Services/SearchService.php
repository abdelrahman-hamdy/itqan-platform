<?php

namespace App\Services;

use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\InteractiveCourse;
use App\Models\RecordedCourse;
use App\Models\QuranTeacherProfile;
use App\Models\AcademicTeacherProfile;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SearchService
{
    /**
     * Search across all student-accessible resources
     *
     * @param string $query
     * @param StudentProfile|null $student
     * @param array $filters
     * @return Collection
     */
    public function searchAll(string $query, ?StudentProfile $student = null, array $filters = []): Collection
    {
        $query = trim($query);

        if (empty($query)) {
            return collect([
                'quran_circles' => collect(),
                'individual_circles' => collect(),
                'academic_sessions' => collect(),
                'interactive_courses' => collect(),
                'recorded_courses' => collect(),
                'quran_teachers' => collect(),
                'academic_teachers' => collect(),
            ]);
        }

        $results = [
            'quran_circles' => $this->searchQuranCircles($query, $student, $filters),
            'individual_circles' => $this->searchIndividualCircles($query, $student, $filters),
            'academic_sessions' => $this->searchAcademicSessions($query, $student, $filters),
            'interactive_courses' => $this->searchInteractiveCourses($query, $student, $filters),
            'recorded_courses' => $this->searchRecordedCourses($query, $student, $filters),
            'quran_teachers' => $this->searchQuranTeachers($query, $filters),
            'academic_teachers' => $this->searchAcademicTeachers($query, $filters),
        ];

        return collect($results);
    }

    /**
     * Search Quran group circles
     */
    protected function searchQuranCircles(string $query, ?StudentProfile $student, array $filters): Collection
    {
        $queryBuilder = QuranCircle::query()
            ->with(['quranTeacher', 'students'])
            ->where(function ($q) use ($query) {
                $q->where('name_ar', 'LIKE', "%{$query}%")
                  ->orWhere('name_en', 'LIKE', "%{$query}%")
                  ->orWhere('description_ar', 'LIKE', "%{$query}%")
                  ->orWhere('description_en', 'LIKE', "%{$query}%")
                  ->orWhereHas('quranTeacher', function ($teacherQuery) use ($query) {
                      $teacherQuery->where('first_name', 'LIKE', "%{$query}%")
                                   ->orWhere('last_name', 'LIKE', "%{$query}%");
                  });
            });

        // Apply filters
        if (isset($filters['level'])) {
            $queryBuilder->where('circle_level', $filters['level']);
        }

        if (isset($filters['enrollment_status'])) {
            $queryBuilder->where('enrollment_status', $filters['enrollment_status']);
        }

        // Show enrolled circles first if student is provided
        if ($student) {
            $queryBuilder->orderByRaw(
                "CASE WHEN id IN (SELECT circle_id FROM quran_circle_students WHERE student_id = ? AND status = 'active') THEN 0 ELSE 1 END",
                [$student->user_id]
            );
        }

        return $queryBuilder
            ->orderBy('name_ar')
            ->limit(10)
            ->get()
            ->map(function ($circle) use ($student) {
                return [
                    'type' => 'quran_circle',
                    'id' => $circle->id,
                    'title' => $circle->name,
                    'description' => $circle->description,
                    'icon' => 'ri-group-line',
                    'icon_bg' => 'bg-green-100',
                    'icon_color' => 'text-green-600',
                    'teacher_name' => $circle->quranTeacher?->full_name ?? 'معلم غير محدد',
                    'meta' => [
                        'students_count' => $circle->students_count ?? $circle->students->count(),
                        'max_students' => $circle->max_students,
                        'schedule' => $circle->schedule_days_text,
                        'monthly_fee' => $circle->monthly_fee,
                    ],
                    'status' => $circle->enrollment_status,
                    'is_enrolled' => $student ? $circle->students->contains('id', $student->user_id) : false,
                    'route' => route('student.circles.show', [
                        'subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy',
                        'circleId' => $circle->id
                    ]),
                ];
            });
    }

    /**
     * Search individual Quran circles (1-to-1)
     */
    protected function searchIndividualCircles(string $query, ?StudentProfile $student, array $filters): Collection
    {
        if (!$student) {
            return collect();
        }

        $queryBuilder = QuranIndividualCircle::query()
            ->with(['quranTeacher', 'student'])
            ->where('student_id', $student->user_id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%")
                  ->orWhereHas('quranTeacher', function ($teacherQuery) use ($query) {
                      $teacherQuery->where('first_name', 'LIKE', "%{$query}%")
                                   ->orWhere('last_name', 'LIKE', "%{$query}%");
                  });
            });

        return $queryBuilder
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($circle) {
                return [
                    'type' => 'individual_circle',
                    'id' => $circle->id,
                    'title' => $circle->name ?? 'حلقة فردية',
                    'description' => $circle->description,
                    'icon' => 'ri-user-line',
                    'icon_bg' => 'bg-blue-100',
                    'icon_color' => 'text-blue-600',
                    'teacher_name' => $circle->quranTeacher?->full_name ?? 'معلم غير محدد',
                    'meta' => [
                        'sessions_completed' => $circle->sessions_completed ?? 0,
                        'total_sessions' => $circle->total_sessions ?? 0,
                        'progress_percentage' => $circle->progress_percentage ?? 0,
                    ],
                    'status' => 'active',
                    'is_enrolled' => true,
                    'route' => route('individual-circles.show', [
                        'subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy',
                        'circle' => $circle->id
                    ]),
                ];
            });
    }

    /**
     * Search academic sessions (private lessons)
     */
    protected function searchAcademicSessions(string $query, ?StudentProfile $student, array $filters): Collection
    {
        if (!$student) {
            return collect();
        }

        $queryBuilder = AcademicSubscription::query()
            ->with(['academicTeacher', 'student', 'subject', 'gradeLevel'])
            ->where('student_id', $student->user_id)
            ->where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('subject_name', 'LIKE', "%{$query}%")
                  ->orWhere('grade_level_name', 'LIKE', "%{$query}%")
                  ->orWhere('notes', 'LIKE', "%{$query}%")
                  ->orWhereHas('academicTeacher', function ($teacherQuery) use ($query) {
                      $teacherQuery->where('first_name', 'LIKE', "%{$query}%")
                                   ->orWhere('last_name', 'LIKE', "%{$query}%");
                  })
                  ->orWhereHas('subject', function ($subjectQuery) use ($query) {
                      $subjectQuery->where('name', 'LIKE', "%{$query}%");
                  });
            });

        if (isset($filters['subject_id'])) {
            $queryBuilder->where('subject_id', $filters['subject_id']);
        }

        return $queryBuilder
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($subscription) {
                return [
                    'type' => 'academic_session',
                    'id' => $subscription->id,
                    'title' => $subscription->subject?->name ?? $subscription->subject_name ?? 'درس خاص',
                    'description' => "دروس خاصة في {$subscription->subject_name} - {$subscription->grade_level_name}",
                    'icon' => 'ri-book-open-line',
                    'icon_bg' => 'bg-purple-100',
                    'icon_color' => 'text-purple-600',
                    'teacher_name' => $subscription->academicTeacher?->full_name ?? 'معلم غير محدد',
                    'meta' => [
                        'subject' => $subscription->subject?->name ?? $subscription->subject_name,
                        'grade_level' => $subscription->gradeLevel?->name ?? $subscription->grade_level_name,
                        'sessions_per_month' => $subscription->sessions_per_month,
                        'student_price' => $subscription->final_monthly_amount,
                    ],
                    'status' => $subscription->status ?? 'active',
                    'is_enrolled' => true,
                    'route' => route('student.academic-teachers', [
                        'subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy',
                    ]),
                ];
            });
    }

    /**
     * Search interactive courses (live courses)
     */
    protected function searchInteractiveCourses(string $query, ?StudentProfile $student, array $filters): Collection
    {
        $queryBuilder = InteractiveCourse::query()
            ->with(['assignedTeacher', 'subject', 'gradeLevel', 'enrollments'])
            ->where('is_published', true)
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%")
                  ->orWhereHas('assignedTeacher', function ($teacherQuery) use ($query) {
                      $teacherQuery->where('first_name', 'LIKE', "%{$query}%")
                                   ->orWhere('last_name', 'LIKE', "%{$query}%");
                  })
                  ->orWhereHas('subject', function ($subjectQuery) use ($query) {
                      $subjectQuery->where('name', 'LIKE', "%{$query}%");
                  });
            });

        if (isset($filters['subject_id'])) {
            $queryBuilder->where('subject_id', $filters['subject_id']);
        }

        if (isset($filters['grade_level_id'])) {
            $queryBuilder->where('grade_level_id', $filters['grade_level_id']);
        }

        // Show enrolled courses first if student is provided
        if ($student) {
            $queryBuilder->orderByRaw(
                "CASE WHEN id IN (SELECT course_id FROM interactive_course_enrollments WHERE student_id = ? AND status = 'active') THEN 0 ELSE 1 END",
                [$student->user_id]
            );
        }

        return $queryBuilder
            ->orderBy('title')
            ->limit(10)
            ->get()
            ->map(function ($course) use ($student) {
                $enrollment = $student ? $course->enrollments->where('student_id', $student->user_id)->first() : null;

                return [
                    'type' => 'interactive_course',
                    'id' => $course->id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'icon' => 'ri-book-open-line',
                    'icon_bg' => 'bg-blue-100',
                    'icon_color' => 'text-blue-600',
                    'teacher_name' => $course->assignedTeacher?->full_name ?? 'غير محدد',
                    'meta' => [
                        'subject' => $course->subject?->name,
                        'grade_level' => $course->gradeLevel?->name,
                        'total_sessions' => $course->total_sessions ?? 0,
                        'duration_weeks' => $course->duration_weeks ?? 0,
                        'student_price' => $course->student_price,
                        'progress_percentage' => $enrollment?->progress_percentage ?? 0,
                    ],
                    'status' => 'published',
                    'is_enrolled' => $enrollment !== null,
                    'route' => $enrollment
                        ? route('my.interactive-course.show', [
                            'subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy',
                            'course' => $course->id
                          ])
                        : route('interactive-courses.show', [
                            'subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy',
                            'course' => $course->id
                          ]),
                ];
            });
    }

    /**
     * Search recorded courses (pre-recorded)
     */
    protected function searchRecordedCourses(string $query, ?StudentProfile $student, array $filters): Collection
    {
        $queryBuilder = RecordedCourse::query()
            ->with(['subject', 'gradeLevel', 'enrollments'])
            ->where('is_published', true)
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%")
                  ->orWhereHas('subject', function ($subjectQuery) use ($query) {
                      $subjectQuery->where('name', 'LIKE', "%{$query}%");
                  });
            });

        if (isset($filters['subject_id'])) {
            $queryBuilder->where('subject_id', $filters['subject_id']);
        }

        if (isset($filters['grade_level_id'])) {
            $queryBuilder->where('grade_level_id', $filters['grade_level_id']);
        }

        // Show enrolled courses first if student is provided
        if ($student) {
            $queryBuilder->orderByRaw(
                "CASE WHEN id IN (SELECT recorded_course_id FROM course_subscriptions WHERE student_id = ? AND status = 'active') THEN 0 ELSE 1 END",
                [$student->user_id]
            );
        }

        return $queryBuilder
            ->orderBy('title')
            ->limit(10)
            ->get()
            ->map(function ($course) use ($student) {
                $enrollment = $student ? $course->enrollments->where('student_id', $student->user_id)->where('status', 'active')->first() : null;

                return [
                    'type' => 'recorded_course',
                    'id' => $course->id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'icon' => 'ri-video-line',
                    'icon_bg' => 'bg-red-100',
                    'icon_color' => 'text-red-600',
                    'teacher_name' => null, // Recorded courses don't have assigned teachers
                    'meta' => [
                        'subject' => $course->subject?->name,
                        'grade_level' => $course->gradeLevel?->name,
                        'duration_hours' => $course->duration_hours,
                        'lessons_count' => $course->total_lessons ?? 0,
                        'price' => $course->price,
                        'progress_percentage' => $enrollment?->progress_percentage ?? 0,
                    ],
                    'status' => 'published',
                    'is_enrolled' => $enrollment !== null,
                    'route' => route('courses.show', [
                        'subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy',
                        'courseId' => $course->id
                    ]),
                ];
            });
    }

    /**
     * Search Quran teachers
     */
    protected function searchQuranTeachers(string $query, array $filters): Collection
    {
        $queryBuilder = QuranTeacherProfile::query()
            ->with(['user', 'circles'])
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'LIKE', "%{$query}%")
                  ->orWhere('last_name', 'LIKE', "%{$query}%")
                  ->orWhere('bio_arabic', 'LIKE', "%{$query}%")
                  ->orWhere('bio_english', 'LIKE', "%{$query}%");
            })
            ->where('is_active', true);

        return $queryBuilder
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(10)
            ->get()
            ->map(function ($teacher) {
                return [
                    'type' => 'quran_teacher',
                    'id' => $teacher->id,
                    'title' => $teacher->full_name,
                    'description' => $teacher->bio_arabic ?? $teacher->bio_english,
                    'icon' => 'ri-user-star-line',
                    'icon_bg' => 'bg-green-100',
                    'icon_color' => 'text-green-600',
                    'teacher_name' => null, // This IS the teacher
                    'meta' => [
                        'experience_years' => $teacher->teaching_experience_years,
                        'circles_count' => $teacher->circles->count(),
                    ],
                    'status' => 'active',
                    'is_enrolled' => false,
                    'route' => route('student.quran-teachers', [
                        'subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy',
                    ]) . '#teacher-' . $teacher->id,
                ];
            });
    }

    /**
     * Search academic teachers
     */
    protected function searchAcademicTeachers(string $query, array $filters): Collection
    {
        $queryBuilder = AcademicTeacherProfile::query()
            ->with(['user', 'subjects'])
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'LIKE', "%{$query}%")
                  ->orWhere('last_name', 'LIKE', "%{$query}%")
                  ->orWhereHas('subjects', function ($subjectQuery) use ($query) {
                      $subjectQuery->where('name', 'LIKE', "%{$query}%");
                  });
            })
            ->where('is_active', true);

        return $queryBuilder
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(10)
            ->get()
            ->map(function ($teacher) {
                return [
                    'type' => 'academic_teacher',
                    'id' => $teacher->id,
                    'title' => $teacher->full_name,
                    'description' => $teacher->subjects->pluck('name')->join(' - '),
                    'icon' => 'ri-graduation-cap-line',
                    'icon_bg' => 'bg-purple-100',
                    'icon_color' => 'text-purple-600',
                    'teacher_name' => null, // This IS the teacher
                    'meta' => [
                        'subjects' => $teacher->subjects->pluck('name')->join(', '),
                        'experience_years' => $teacher->teaching_experience_years,
                    ],
                    'status' => 'active',
                    'is_enrolled' => false,
                    'route' => route('student.academic-teachers', [
                        'subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy',
                    ]) . '#teacher-' . $teacher->id,
                ];
            });
    }

    /**
     * Get total results count
     */
    public function getTotalResultsCount(Collection $results): int
    {
        return $results->sum(function ($items) {
            return $items->count();
        });
    }
}
