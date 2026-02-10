<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Http\Helpers\ImageHelper;
use App\Models\AcademicSession;
use App\Models\BaseSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full session details for observation
 *
 * @mixin BaseSession
 */
class SessionDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $type = $this->getSessionType();

        $data = [
            'id' => $this->id,
            'type' => $type,
            'session_code' => $this->getSessionCode(),
            'status' => $this->status->value ?? $this->status,
            'status_label' => $this->status->label() ?? null,
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'duration_minutes' => $this->duration_minutes,
            'teacher' => $this->getTeacherData(),
            'meeting' => $this->getMeetingData(),
            'attendance' => $this->getAttendanceData(),
            'reports' => $this->studentReports->map(function ($report) {
                return [
                    'id' => $report->id,
                    'status' => $report->status,
                    'summary' => $report->summary,
                    'created_at' => $report->created_at?->toISOString(),
                ];
            }),
        ];

        // Add type-specific data
        if ($type === 'quran') {
            $data['quran_data'] = $this->getQuranData();
            $data['student'] = $this->getStudentData();
            $data['circle'] = $this->getCircleData();
        } elseif ($type === 'academic') {
            $data['academic_data'] = $this->getAcademicData();
            $data['student'] = $this->getStudentData();
            $data['lesson'] = $this->getLessonData();
        } elseif ($type === 'interactive') {
            $data['interactive_data'] = $this->getInteractiveData();
            $data['students'] = $this->getCourseStudents();
            $data['course'] = $this->getCourseData();
        }

        return $data;
    }

    /**
     * Get session type
     */
    protected function getSessionType(): string
    {
        return match (get_class($this->resource)) {
            QuranSession::class => 'quran',
            AcademicSession::class => 'academic',
            InteractiveCourseSession::class => 'interactive',
            default => 'unknown',
        };
    }

    /**
     * Get session code
     */
    protected function getSessionCode(): ?string
    {
        if ($this->resource instanceof QuranSession) {
            return $this->session_code ?? $this->circle?->circle_code ?? $this->individualCircle?->circle_code;
        }

        if ($this->resource instanceof AcademicSession) {
            return $this->session_code;
        }

        if ($this->resource instanceof InteractiveCourseSession) {
            return $this->course?->course_code;
        }

        return null;
    }

    /**
     * Get teacher data with full details
     */
    protected function getTeacherData(): array
    {
        $teacher = null;

        if ($this->resource instanceof QuranSession) {
            $teacher = $this->quranTeacher;
        } elseif ($this->resource instanceof AcademicSession) {
            $teacher = $this->academicTeacher?->user;
        } elseif ($this->resource instanceof InteractiveCourseSession) {
            $teacher = $this->course?->assignedTeacher?->user;
        }

        if (! $teacher) {
            return [];
        }

        return [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'phone' => $teacher->phone,
            'email' => $teacher->email,
            'avatar' => ImageHelper::getAvatarUrls($teacher->avatar, $teacher->name),
        ];
    }

    /**
     * Get student data (for individual sessions)
     */
    protected function getStudentData(): array
    {
        $student = $this->student;

        if (! $student) {
            return [];
        }

        return [
            'id' => $student->id,
            'name' => $student->name,
            'email' => $student->email,
            'grade_level' => $student->studentProfile?->gradeLevel?->name,
        ];
    }

    /**
     * Get course students (for interactive sessions)
     */
    protected function getCourseStudents(): array
    {
        if (! $this->resource instanceof InteractiveCourseSession) {
            return [];
        }

        return $this->course?->enrollments->map(function ($enrollment) {
            return [
                'id' => $enrollment->student_id,
                'name' => $enrollment->student?->name,
            ];
        })->toArray() ?? [];
    }

    /**
     * Get meeting data
     */
    protected function getMeetingData(): ?array
    {
        if (! $this->meeting_room_name) {
            return null;
        }

        return [
            'room_name' => $this->meeting_room_name,
            'is_active' => in_array($this->status->value ?? $this->status, ['ready', 'ongoing']),
            'participant_count' => $this->meeting?->participant_count ?? 0,
            'started_at' => $this->meeting?->started_at?->toISOString(),
        ];
    }

    /**
     * Get attendance data
     */
    protected function getAttendanceData(): array
    {
        // Note: Attendance records might not be loaded, return basic info
        return [
            'has_attendance' => ! empty($this->teacher_attended_at),
            'teacher_attended_at' => $this->teacher_attended_at?->toISOString(),
            'student_attended_at' => $this->student_attended_at?->toISOString(),
        ];
    }

    /**
     * Get Quran-specific data
     */
    protected function getQuranData(): array
    {
        if (! $this->resource instanceof QuranSession) {
            return [];
        }

        return [
            'session_type' => $this->session_type,
            'from_surah' => $this->from_surah,
            'from_ayah' => $this->from_ayah,
            'to_surah' => $this->to_surah,
            'to_ayah' => $this->to_ayah,
            'from_page' => $this->from_page,
            'to_page' => $this->to_page,
        ];
    }

    /**
     * Get Academic-specific data
     */
    protected function getAcademicData(): array
    {
        if (! $this->resource instanceof AcademicSession) {
            return [];
        }

        return [
            'lesson_content' => $this->lesson_content,
            'topics' => $this->topics,
        ];
    }

    /**
     * Get Interactive-specific data
     */
    protected function getInteractiveData(): array
    {
        if (! $this->resource instanceof InteractiveCourseSession) {
            return [];
        }

        return [
            'scheduled_date' => $this->scheduled_date?->format('Y-m-d'),
            'scheduled_time' => $this->scheduled_time,
        ];
    }

    /**
     * Get circle data (for Quran sessions)
     */
    protected function getCircleData(): ?array
    {
        if (! $this->resource instanceof QuranSession) {
            return null;
        }

        $circle = $this->circle ?? $this->individualCircle;

        if (! $circle) {
            return null;
        }

        return [
            'id' => $circle->id,
            'circle_code' => $circle->circle_code,
            'specialization' => $circle->specialization ?? null,
        ];
    }

    /**
     * Get lesson data (for Academic sessions)
     */
    protected function getLessonData(): ?array
    {
        if (! $this->resource instanceof AcademicSession) {
            return null;
        }

        $lesson = $this->academicIndividualLesson;

        if (! $lesson) {
            return null;
        }

        return [
            'id' => $lesson->id,
            'lesson_code' => $lesson->lesson_code,
            'subject' => [
                'id' => $lesson->academic_subject_id,
                'name' => $lesson->academicSubject?->name,
            ],
        ];
    }

    /**
     * Get course data (for Interactive sessions)
     */
    protected function getCourseData(): ?array
    {
        if (! $this->resource instanceof InteractiveCourseSession) {
            return null;
        }

        $course = $this->course;

        if (! $course) {
            return null;
        }

        return [
            'id' => $course->id,
            'course_code' => $course->course_code,
            'name' => $course->name,
            'subject' => [
                'id' => $course->subject_id,
                'name' => $course->subject?->name,
            ],
        ];
    }
}
