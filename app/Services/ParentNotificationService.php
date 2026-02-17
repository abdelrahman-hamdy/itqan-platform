<?php

namespace App\Services;

use App\Models\QuizAttempt;
use Illuminate\Support\Collection;
use App\Constants\DefaultAcademy;
use App\Enums\NotificationType;
use App\Models\AcademicHomework;
use App\Models\AcademicSession;
use App\Models\BaseSubscription;
use App\Models\Certificate;
use App\Models\ParentProfile;
use App\Models\QuranSession;
use App\Models\User;
use Carbon\Carbon;

/**
 * Parent Notification Service
 *
 * Handle parent-specific notifications about their children's activities.
 * Integrates with existing NotificationService to send notifications.
 *
 * TIMEZONE HANDLING:
 * All times are stored in UTC. This service converts them to academy
 * timezone for display in notifications.
 */
class ParentNotificationService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Format datetime in academy timezone for notifications.
     */
    private function formatInAcademyTimezone(?Carbon $datetime, string $format = 'Y-m-d h:i A'): string
    {
        if (! $datetime) {
            return '';
        }

        $timezone = AcademyContextService::getTimezone();

        return $datetime->copy()->setTimezone($timezone)->format($format);
    }

    /**
     * Send session reminder to parent
     */
    public function sendSessionReminder(QuranSession|AcademicSession $session): void
    {
        // Get student
        $student = User::find($session->student_id);
        if (! $student) {
            return;
        }

        // Get parent(s) linked to this student
        $parents = $this->getParentsForStudent($student);

        foreach ($parents as $parent) {
            $sessionType = $session instanceof QuranSession ? 'قرآن' : 'أكاديمية';
            $teacherName = $this->getTeacherName($session);

            $this->notificationService->send(
                $parent->user,
                NotificationType::SESSION_REMINDER,
                [
                    'child_name' => $student->name,
                    'session_type' => $sessionType,
                    'teacher_name' => $teacherName,
                    'scheduled_at' => $this->formatInAcademyTimezone($session->scheduled_at),
                ],
                $this->getSessionUrl($session),
                [
                    'session_id' => $session->id,
                    'child_id' => $student->id,
                ],
                false // not critical
            );
        }
    }

    /**
     * Send homework assigned notification
     *
     * @param  int|null  $studentId  Optional student ID if homework is for specific student
     */
    public function sendHomeworkAssigned(AcademicHomework $homework, ?int $studentId = null): void
    {
        // Get student from homework subscription or session
        $student = null;
        if ($studentId) {
            $student = User::find($studentId);
        } elseif ($homework->subscription?->student_id) {
            $student = User::find($homework->subscription->student_id);
        } elseif ($homework->session?->student_id) {
            $student = User::find($homework->session->student_id);
        }

        if (! $student) {
            return;
        }

        // Get parent(s)
        $parents = $this->getParentsForStudent($student);

        $subdomain = $student->academy?->subdomain ?? DefaultAcademy::subdomain();

        foreach ($parents as $parent) {
            $this->notificationService->send(
                $parent->user,
                NotificationType::HOMEWORK_ASSIGNED,
                [
                    'child_name' => $student->name,
                    'homework_title' => $homework->title ?? 'واجب جديد',
                    'due_date' => $homework->due_date?->format('Y-m-d'),
                ],
                route('parent.homework.index', ['subdomain' => $subdomain]),
                [
                    'homework_id' => $homework->id,
                    'child_id' => $student->id,
                ],
                false
            );
        }
    }

    /**
     * Send certificate issued notification
     */
    public function sendCertificateIssued(Certificate $certificate): void
    {
        // Get student
        $student = User::find($certificate->student_id);
        if (! $student) {
            return;
        }

        // Get parent(s)
        $parents = $this->getParentsForStudent($student);
        $subdomain = $student->academy?->subdomain ?? DefaultAcademy::subdomain();

        foreach ($parents as $parent) {
            $this->notificationService->send(
                $parent->user,
                NotificationType::CERTIFICATE_EARNED,
                [
                    'child_name' => $student->name,
                    'certificate_type' => $certificate->certificate_type?->value ?? 'شهادة',
                    'certificate_number' => $certificate->certificate_number,
                ],
                route('parent.certificates.show', ['subdomain' => $subdomain, 'certificate' => $certificate->id]),
                [
                    'certificate_id' => $certificate->id,
                    'child_id' => $student->id,
                ],
                false
            );
        }
    }

    /**
     * Send payment reminder
     */
    public function sendPaymentReminder(BaseSubscription $subscription): void
    {
        // Get student
        $student = User::find($subscription->student_id);
        if (! $student) {
            return;
        }

        // Get parent(s)
        $parents = $this->getParentsForStudent($student);
        $subdomain = $student->academy?->subdomain ?? DefaultAcademy::subdomain();

        foreach ($parents as $parent) {
            $this->notificationService->send(
                $parent->user,
                NotificationType::SUBSCRIPTION_EXPIRING,
                [
                    'child_name' => $student->name,
                    'amount' => $subscription->final_price ?? $subscription->total_price,
                    'currency' => $subscription->currency ?? getCurrencyCode(null, $subscription->academy),
                    'due_date' => $subscription->next_payment_at?->format('Y-m-d'),
                ],
                route('parent.payments.index', ['subdomain' => $subdomain]),
                [
                    'subscription_id' => $subscription->id,
                    'child_id' => $student->id,
                ],
                true // critical
            );
        }
    }

    /**
     * Send quiz graded notification
     */
    public function sendQuizGraded(QuizAttempt $quizAttempt): void
    {
        // Get student
        $studentProfile = $quizAttempt->studentProfile;
        if (! $studentProfile) {
            return;
        }

        $student = $studentProfile->user;
        if (! $student) {
            return;
        }

        // Get parent(s)
        $parents = $this->getParentsForStudent($student);
        $subdomain = $student->academy?->subdomain ?? DefaultAcademy::subdomain();

        foreach ($parents as $parent) {
            $notificationType = $quizAttempt->passed
                ? NotificationType::QUIZ_PASSED
                : NotificationType::QUIZ_FAILED;

            $this->notificationService->send(
                $parent->user,
                $notificationType,
                [
                    'child_name' => $student->name,
                    'quiz_name' => $quizAttempt->quizAssignment->quiz->title ?? 'اختبار',
                    'score' => $quizAttempt->score,
                    'passed' => $quizAttempt->passed ? 'نجح' : 'لم ينجح',
                ],
                route('parent.quizzes.index', ['subdomain' => $subdomain]),
                [
                    'quiz_attempt_id' => $quizAttempt->id,
                    'child_id' => $student->id,
                ],
                false
            );
        }
    }

    /**
     * Get all parents linked to a student
     */
    public function getParentsForStudent(User $student): Collection
    {
        $studentProfile = $student->studentProfileUnscoped;
        if (! $studentProfile) {
            return collect();
        }

        // Get parents through the pivot table
        return ParentProfile::whereHas('students', function ($query) use ($studentProfile) {
            $query->where('student_id', $studentProfile->id);
        })
            ->where('academy_id', $student->academy_id)
            ->whereNotNull('user_id') // Only parents with linked user accounts
            ->with('user')
            ->get();
    }

    /**
     * Get teacher name from session
     */
    private function getTeacherName(QuranSession|AcademicSession $session): string
    {
        if ($session instanceof QuranSession) {
            return $session->quranTeacher?->user->name ?? 'المعلم';
        }

        return $session->academicTeacher?->user->name ?? 'المعلم';
    }

    /**
     * Get session detail URL
     */
    private function getSessionUrl(QuranSession|AcademicSession $session): string
    {
        $sessionType = $session instanceof QuranSession ? 'quran' : 'academic';
        $subdomain = $session->academy?->subdomain ?? DefaultAcademy::subdomain();

        return route('parent.sessions.show', [
            'subdomain' => $subdomain,
            'sessionType' => $sessionType,
            'session' => $session->id,
        ]);
    }
}
