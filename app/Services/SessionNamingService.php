<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\BaseSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Session Naming Service
 *
 * Provides unified session code generation and dynamic display name generation
 * for all session types (Quran, Academic, Interactive Course).
 *
 * Key features:
 * - Consistent code format: {TYPE}-{YYMM}-{SEQ} (e.g., QI-2601-0042)
 * - Audience-aware titles: calendar, teacher, student, admin, notification
 * - Dynamic computation: titles update automatically on reschedule
 */
class SessionNamingService
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('session-naming');
    }

    /**
     * Generate a unique session code for a session.
     *
     * Format: {TYPE}-{YYMM}-{SEQ}
     * Example: QI-2601-0042 (Quran Individual, Jan 2026, sequence 42)
     */
    public function generateSessionCode(BaseSession $session): string
    {
        $typeKey = $session->getSessionTypeKey();
        $prefix = $this->config['type_prefixes'][$typeKey] ?? 'XX';
        $yearMonth = now()->format('ym');

        return DB::transaction(function () use ($session, $prefix, $yearMonth) {
            $codePrefix = "{$prefix}-{$yearMonth}-";

            // Get the last sequence number for this type and month
            $lastSession = $this->getLastSessionByCodePrefix($session, $codePrefix);

            $nextSequence = 1;
            if ($lastSession && preg_match('/(\d{4})$/', $lastSession->session_code, $matches)) {
                $nextSequence = (int) $matches[1] + 1;
            }

            return $codePrefix.str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
        }, 5); // 5 retries for deadlock handling
    }

    /**
     * Get the last session with the given code prefix for sequence calculation.
     */
    protected function getLastSessionByCodePrefix(BaseSession $session, string $codePrefix): ?BaseSession
    {
        $query = $session::withTrashed()
            ->where('session_code', 'LIKE', $codePrefix.'%')
            ->lockForUpdate()
            ->orderByRaw('CAST(SUBSTRING(session_code, -4) AS UNSIGNED) DESC');

        return $query->first(['session_code']);
    }

    /**
     * Get the display name for a session based on audience.
     *
     * @param  string  $audience  One of: calendar, teacher, student, admin, notification
     */
    public function getDisplayName(BaseSession $session, string $audience = 'admin'): string
    {
        $typeKey = $session->getSessionTypeKey();
        $templates = $this->config['templates'][$audience] ?? $this->config['templates']['admin'];
        $template = $templates[$typeKey] ?? ':session_code';

        $placeholders = $this->getPlaceholders($session);

        return $this->interpolate($template, $placeholders);
    }

    /**
     * Get all available placeholders for a session.
     */
    protected function getPlaceholders(BaseSession $session): array
    {
        $placeholders = [
            'session_code' => $session->session_code ?? '',
            'session_number' => '',
            'n' => '',
            'student_name' => '',
            'student_first' => '',
            'teacher_name' => '',
            'teacher_first' => '',
            'circle_name' => '',
            'circle_short' => '',
            'subject_name' => '',
            'subject_short' => '',
            'course_title' => '',
            'course_short' => '',
            'students_count' => 1,
        ];

        if ($session instanceof QuranSession) {
            $placeholders = array_merge($placeholders, $this->getQuranPlaceholders($session));
        } elseif ($session instanceof AcademicSession) {
            $placeholders = array_merge($placeholders, $this->getAcademicPlaceholders($session));
        } elseif ($session instanceof InteractiveCourseSession) {
            $placeholders = array_merge($placeholders, $this->getInteractiveCoursePlaceholders($session));
        }

        return $placeholders;
    }

    /**
     * Get placeholders specific to Quran sessions.
     */
    protected function getQuranPlaceholders(QuranSession $session): array
    {
        $studentName = '';
        $teacherName = '';
        $circleName = '';
        $studentsCount = 1;

        // Get student name (for individual/trial sessions)
        if ($session->student) {
            $studentName = $session->student->name ?? '';
        } elseif ($session->individualCircle?->subscription?->student) {
            $studentName = $session->individualCircle->subscription->student->name ?? '';
        }

        // Get teacher name
        if ($session->quranTeacher) {
            $teacherName = $session->quranTeacher->user->name ?? '';
        }

        // Get circle info (for group sessions)
        if ($session->circle) {
            $circleName = $session->circle->name ?? '';
            $studentsCount = $session->circle->enrolled_students ?? $session->circle->active_students_count ?? 1;
        } elseif ($session->individualCircle) {
            $circleName = $session->individualCircle->name ?? '';
        }

        return [
            'student_name' => $studentName,
            'student_first' => $this->getFirstName($studentName),
            'teacher_name' => $teacherName,
            'teacher_first' => $this->getFirstName($teacherName),
            'circle_name' => $circleName,
            'circle_short' => $this->truncate($circleName, $this->config['truncation']['circle_short'] ?? 15),
            'students_count' => $studentsCount,
        ];
    }

    /**
     * Get placeholders specific to Academic sessions.
     */
    protected function getAcademicPlaceholders(AcademicSession $session): array
    {
        $studentName = '';
        $teacherName = '';
        $subjectName = '';

        // Get student name
        if ($session->student) {
            $studentName = $session->student->name ?? '';
        } elseif ($session->academicIndividualLesson?->subscription?->student) {
            $studentName = $session->academicIndividualLesson->subscription->student->name ?? '';
        }

        // Get teacher name
        if ($session->academicTeacher) {
            $teacherName = $session->academicTeacher->user->name ?? '';
        }

        // Get subject name
        if ($session->academicIndividualLesson?->subscription?->subject) {
            $subjectName = $session->academicIndividualLesson->subscription->subject->getDisplayName() ?? '';
        }

        return [
            'student_name' => $studentName,
            'student_first' => $this->getFirstName($studentName),
            'teacher_name' => $teacherName,
            'teacher_first' => $this->getFirstName($teacherName),
            'subject_name' => $subjectName,
            'subject_short' => $this->truncate($subjectName, $this->config['truncation']['subject_short'] ?? 10),
        ];
    }

    /**
     * Get placeholders specific to Interactive Course sessions.
     */
    protected function getInteractiveCoursePlaceholders(InteractiveCourseSession $session): array
    {
        $teacherName = '';
        $courseTitle = '';
        $sessionNumber = $session->session_number ?? 0;

        // Get course info
        if ($session->course) {
            $courseTitle = $session->course->title ?? $session->course->name ?? '';

            // Get teacher from course
            if ($session->course->assignedTeacher) {
                $teacherName = $session->course->assignedTeacher->user->name ?? '';
            }
        }

        return [
            'session_number' => $sessionNumber,
            'n' => $sessionNumber,
            'teacher_name' => $teacherName,
            'teacher_first' => $this->getFirstName($teacherName),
            'course_title' => $courseTitle,
            'course_short' => $this->truncate($courseTitle, $this->config['truncation']['course_short'] ?? 15),
        ];
    }

    /**
     * Interpolate placeholders in a template string.
     */
    protected function interpolate(string $template, array $placeholders): string
    {
        foreach ($placeholders as $key => $value) {
            $template = str_replace(":{$key}", (string) $value, $template);
        }

        return $template;
    }

    /**
     * Get the first name from a full name.
     */
    protected function getFirstName(?string $fullName): string
    {
        if (empty($fullName)) {
            return '';
        }

        $parts = explode(' ', trim($fullName));

        return $parts[0] ?? '';
    }

    /**
     * Truncate a string to a maximum length with ellipsis.
     */
    protected function truncate(?string $text, int $maxLength = 15): string
    {
        if (empty($text)) {
            return '';
        }

        return Str::limit($text, $maxLength, '...');
    }

    /**
     * Get the type prefix for a session type key.
     */
    public function getTypePrefix(string $typeKey): string
    {
        return $this->config['type_prefixes'][$typeKey] ?? 'XX';
    }

    /**
     * Parse a session code to extract its components.
     *
     * @return array{type: string, year_month: string, sequence: int}|null
     */
    public function parseSessionCode(string $code): ?array
    {
        if (preg_match('/^([A-Z]{2})-(\d{4})-(\d{4})$/', $code, $matches)) {
            return [
                'type' => $matches[1],
                'year_month' => $matches[2],
                'sequence' => (int) $matches[3],
            ];
        }

        return null;
    }
}
