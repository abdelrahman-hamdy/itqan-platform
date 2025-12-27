<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Enums\SubscriptionStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\Certificate;
use App\Models\CourseSubscription;
use App\Models\ParentProfile;
use App\Models\Payment;
use App\Models\QuizAttempt;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;

/**
 * Parent Data Service
 *
 * Centralized service for fetching all child data with authorization checks.
 * All methods validate parent-child relationship before returning data.
 */
class ParentDataService
{
    /**
     * Get all linked children for parent
     *
     * @param ParentProfile $parent
     * @return Collection
     */
    public function getChildren(ParentProfile $parent): Collection
    {
        return $parent->students()
            ->forAcademy($parent->academy_id)
            ->with(['user'])
            ->get();
    }

    /**
     * Get specific child data (validates parent owns child)
     *
     * @param ParentProfile $parent
     * @param int $childId
     * @return array
     */
    public function getChildData(ParentProfile $parent, int $childId): array
    {
        $child = $this->validateChildAccess($parent, $childId);

        return [
            'child' => $child,
            'user' => $child->user,
            'relationship_type' => $child->pivot->relationship_type ?? null,
            'subscriptions_count' => $this->getChildSubscriptionsCount($child),
            'certificates_count' => $this->getChildCertificatesCount($child),
            'upcoming_sessions_count' => $this->getChildUpcomingSessionsCount($child),
        ];
    }

    /**
     * Get subscriptions for child
     *
     * @param ParentProfile $parent
     * @param int $childId
     * @return array
     */
    public function getChildSubscriptions(ParentProfile $parent, int $childId): array
    {
        $child = $this->validateChildAccess($parent, $childId);
        $userId = $child->user_id;

        // Get Quran subscriptions
        $quranSubscriptions = QuranSubscription::where('student_id', $userId)
            ->where('academy_id', $parent->academy_id)
            ->with(['package', 'quranTeacher', 'individualCircle'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get Academic subscriptions
        $academicSubscriptions = AcademicSubscription::where('student_id', $userId)
            ->where('academy_id', $parent->academy_id)
            ->with(['teacher', 'subject', 'gradeLevel'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get Course subscriptions
        $courseSubscriptions = CourseSubscription::where('student_id', $userId)
            ->where('academy_id', $parent->academy_id)
            ->with(['recordedCourse', 'interactiveCourse'])
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'quran' => $quranSubscriptions,
            'academic' => $academicSubscriptions,
            'courses' => $courseSubscriptions,
        ];
    }

    /**
     * Get upcoming sessions for child
     *
     * @param ParentProfile $parent
     * @param int $childId
     * @return Collection
     */
    public function getChildUpcomingSessions(ParentProfile $parent, int $childId): Collection
    {
        $child = $this->validateChildAccess($parent, $childId);
        $userId = $child->user_id;

        // Get upcoming Quran sessions
        $quranSessions = QuranSession::where('student_id', $userId)
            ->where('academy_id', $parent->academy_id)
            ->where('status', SessionStatus::SCHEDULED->value)
            ->where('scheduled_at', '>=', now())
            ->with(['quranTeacher', 'individualCircle', 'circle'])
            ->orderBy('scheduled_at', 'asc')
            ->get();

        // Get upcoming Academic sessions
        $academicSessions = AcademicSession::where('student_id', $userId)
            ->where('academy_id', $parent->academy_id)
            ->where('status', SessionStatus::SCHEDULED->value)
            ->where('scheduled_at', '>=', now())
            ->with(['academicTeacher', 'academicIndividualLesson'])
            ->orderBy('scheduled_at', 'asc')
            ->get();

        // Merge and sort by scheduled_at
        return $quranSessions->merge($academicSessions)
            ->sortBy('scheduled_at')
            ->values();
    }

    /**
     * Get payment history for child
     *
     * @param ParentProfile $parent
     * @param int $childId
     * @return Collection
     */
    public function getChildPayments(ParentProfile $parent, int $childId): Collection
    {
        $child = $this->validateChildAccess($parent, $childId);
        $userId = $child->user_id;

        return Payment::where('user_id', $userId)
            ->where('academy_id', $parent->academy_id)
            ->with(['subscription'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get certificates for child
     *
     * @param ParentProfile $parent
     * @param int $childId
     * @return Collection
     */
    public function getChildCertificates(ParentProfile $parent, int $childId): Collection
    {
        $child = $this->validateChildAccess($parent, $childId);
        $userId = $child->user_id;

        return Certificate::where('student_id', $userId)
            ->where('academy_id', $parent->academy_id)
            ->with(['certificateable', 'issuedBy'])
            ->orderBy('issued_at', 'desc')
            ->get();
    }

    /**
     * Get quiz results for child
     *
     * @param ParentProfile $parent
     * @param int $childId
     * @return Collection
     */
    public function getChildQuizResults(ParentProfile $parent, int $childId): Collection
    {
        $child = $this->validateChildAccess($parent, $childId);

        return QuizAttempt::where('student_id', $child->id)
            ->with(['quizAssignment.quiz'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get child progress report
     *
     * @param ParentProfile $parent
     * @param int $childId
     * @return array
     */
    public function getChildProgressReport(ParentProfile $parent, int $childId): array
    {
        $child = $this->validateChildAccess($parent, $childId);
        $userId = $child->user_id;

        // Calculate session statistics
        $quranSessionsTotal = QuranSession::where('student_id', $userId)
            ->where('academy_id', $parent->academy_id)
            ->count();

        $quranSessionsCompleted = QuranSession::where('student_id', $userId)
            ->where('academy_id', $parent->academy_id)
            ->where('status', SessionStatus::COMPLETED->value)
            ->count();

        $academicSessionsTotal = AcademicSession::where('student_id', $userId)
            ->where('academy_id', $parent->academy_id)
            ->count();

        $academicSessionsCompleted = AcademicSession::where('student_id', $userId)
            ->where('academy_id', $parent->academy_id)
            ->where('status', SessionStatus::COMPLETED->value)
            ->count();

        // Calculate attendance rate
        $totalSessions = $quranSessionsTotal + $academicSessionsTotal;
        $completedSessions = $quranSessionsCompleted + $academicSessionsCompleted;
        $attendanceRate = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100, 2) : 0;

        return [
            'quran_sessions_total' => $quranSessionsTotal,
            'quran_sessions_completed' => $quranSessionsCompleted,
            'academic_sessions_total' => $academicSessionsTotal,
            'academic_sessions_completed' => $academicSessionsCompleted,
            'total_sessions' => $totalSessions,
            'completed_sessions' => $completedSessions,
            'attendance_rate' => $attendanceRate,
            'certificates_count' => $this->getChildCertificatesCount($child),
        ];
    }

    /**
     * Validates parent-child relationship, throws 403 if invalid
     *
     * @param ParentProfile $parent
     * @param int $childId
     * @return StudentProfile
     */
    private function validateChildAccess(ParentProfile $parent, int $childId): StudentProfile
    {
        $child = $parent->students()
            ->where('student_profiles.id', $childId)
            ->forAcademy($parent->academy_id)
            ->first();

        if (!$child) {
            abort(403, 'لا يمكنك الوصول إلى بيانات هذا الطالب');
        }

        return $child;
    }

    /**
     * Get subscriptions count for child
     *
     * @param StudentProfile $child
     * @return int
     */
    private function getChildSubscriptionsCount(StudentProfile $child): int
    {
        $userId = $child->user_id;

        $quranCount = QuranSubscription::where('student_id', $userId)
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->count();

        $academicCount = AcademicSubscription::where('student_id', $userId)
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->count();

        $courseCount = CourseSubscription::where('student_id', $userId)
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->count();

        return $quranCount + $academicCount + $courseCount;
    }

    /**
     * Get certificates count for child
     *
     * @param StudentProfile $child
     * @return int
     */
    private function getChildCertificatesCount(StudentProfile $child): int
    {
        return Certificate::where('student_id', $child->user_id)->count();
    }

    /**
     * Get upcoming sessions count for child
     *
     * @param StudentProfile $child
     * @return int
     */
    private function getChildUpcomingSessionsCount(StudentProfile $child): int
    {
        $userId = $child->user_id;

        $quranCount = QuranSession::where('student_id', $userId)
            ->where('status', SessionStatus::SCHEDULED->value)
            ->where('scheduled_at', '>=', now())
            ->count();

        $academicCount = AcademicSession::where('student_id', $userId)
            ->where('status', SessionStatus::SCHEDULED->value)
            ->where('scheduled_at', '>=', now())
            ->count();

        return $quranCount + $academicCount;
    }
}
