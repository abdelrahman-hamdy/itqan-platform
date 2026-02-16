<?php

namespace App\Services\Notification;

use App\Constants\DefaultAcademy;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Builds URLs for notification actions.
 *
 * Generates appropriate URLs based on resource type, user role,
 * and session/subscription context.
 */
class NotificationUrlBuilder
{
    /**
     * Get the appropriate URL for a session based on user role.
     *
     * @param  Model  $session  The session model
     * @param  User  $user  The user viewing the notification
     * @return string The URL to navigate to
     */
    public function getSessionUrl(Model $session, User $user): string
    {
        $subdomain = $session->academy?->subdomain ?? DefaultAcademy::subdomain();

        if ($user->hasRole([UserType::SUPER_ADMIN->value])) {
            return $this->getAdminSessionUrl($session);
        }

        if ($user->hasRole([UserType::ADMIN->value])) {
            return $this->getAdminSessionUrl($session);
        }

        if ($user->hasRole([UserType::SUPERVISOR->value])) {
            return $this->getSupervisorSessionUrl($session);
        }

        if ($user->hasRole([UserType::STUDENT->value])) {
            return $this->getStudentSessionUrl($session, $subdomain);
        }

        if ($user->hasRole([UserType::PARENT->value])) {
            return $this->getParentSessionUrl($session, $subdomain);
        }

        if ($user->hasRole([UserType::QURAN_TEACHER->value])) {
            return $this->getTeacherCircleUrl($session);
        }

        if ($user->hasRole([UserType::ACADEMIC_TEACHER->value])) {
            return $this->getAcademicTeacherSessionUrl($session);
        }

        return '/';
    }

    /**
     * Get session URL for students.
     *
     * @param  Model  $session  The session model
     * @param  string  $subdomain  The academy subdomain
     */
    private function getStudentSessionUrl(Model $session, string $subdomain): string
    {
        $sessionClass = class_basename($session);

        return match ($sessionClass) {
            'QuranSession' => $this->getStudentQuranSessionUrl($session, $subdomain),
            'AcademicSession' => route('student.academic-sessions.show', [
                'subdomain' => $subdomain,
                'session' => $session->id,
            ]),
            'InteractiveCourseSession' => route('student.interactive-sessions.show', [
                'subdomain' => $subdomain,
                'session' => $session->id,
            ]),
            default => route('student.profile', ['subdomain' => $subdomain]),
        };
    }

    /**
     * Get student URL for a Quran session (navigates to circle page).
     */
    private function getStudentQuranSessionUrl(Model $session, string $subdomain): string
    {
        // Individual circle session
        if (method_exists($session, 'individualCircle') && $session->individualCircle) {
            return route('individual-circles.show', [
                'subdomain' => $subdomain,
                'circle' => $session->individualCircle->id,
            ]);
        }

        // Group circle session
        if (method_exists($session, 'circle') && $session->circle) {
            return route('student.circles.show', [
                'subdomain' => $subdomain,
                'circleId' => $session->circle->id,
            ]);
        }

        return route('student.profile', ['subdomain' => $subdomain]);
    }

    /**
     * Get session URL for parents.
     *
     * @param  Model  $session  The session model
     * @param  string  $subdomain  The academy subdomain
     */
    private function getParentSessionUrl(Model $session, string $subdomain): string
    {
        $sessionType = match (class_basename($session)) {
            'QuranSession' => 'quran',
            'AcademicSession' => 'academic',
            'InteractiveCourseSession' => 'interactive',
            default => 'quran',
        };

        return route('parent.sessions.show', [
            'subdomain' => $subdomain,
            'sessionType' => $sessionType,
            'session' => $session->id,
        ]);
    }

    /**
     * Get circle URL from session for students.
     *
     * @param  Model  $session  The session model
     */
    public function getCircleUrlFromSession(Model $session): string
    {
        $subdomain = $session->academy?->subdomain ?? DefaultAcademy::subdomain();

        return route('student.profile', ['subdomain' => $subdomain]);
    }

    /**
     * Get appropriate teacher URL based on circle type.
     *
     * @param  Model  $session  The session model
     */
    public function getTeacherCircleUrl(Model $session): string
    {
        // Use Filament teacher panel URLs directly
        if (method_exists($session, 'individualCircle') && $session->individualCircle) {
            return "/teacher-panel/quran-individual-circles/{$session->individualCircle->id}";
        }

        if (method_exists($session, 'circle') && $session->circle) {
            return "/teacher-panel/quran-circles/{$session->circle->id}";
        }

        return "/teacher-panel/quran-sessions/{$session->id}";
    }

    /**
     * Get session URL for academic teacher panel.
     * Differentiates between AcademicSession and InteractiveCourseSession.
     */
    private function getAcademicTeacherSessionUrl(Model $session): string
    {
        $sessionClass = class_basename($session);

        return match ($sessionClass) {
            'InteractiveCourseSession' => "/academic-teacher-panel/interactive-course-sessions/{$session->id}",
            default => "/academic-teacher-panel/academic-sessions/{$session->id}",
        };
    }

    /**
     * Get session URL for admin panel (super admin / academy admin).
     */
    private function getAdminSessionUrl(Model $session): string
    {
        $sessionClass = class_basename($session);

        return match ($sessionClass) {
            'QuranSession' => "/admin/quran-sessions/{$session->id}",
            'AcademicSession' => "/admin/academic-sessions/{$session->id}",
            'InteractiveCourseSession' => "/admin/interactive-course-sessions/{$session->id}",
            default => "/admin/quran-sessions/{$session->id}",
        };
    }

    /**
     * Get session URL for supervisor panel.
     */
    private function getSupervisorSessionUrl(Model $session): string
    {
        return "/supervisor-panel/monitored-all-sessions/{$session->id}";
    }

    /**
     * Get admin panel URL for a payment.
     */
    public function getAdminPaymentUrl(string $paymentId): string
    {
        return "/admin/payments/{$paymentId}";
    }

    /**
     * Get admin panel URL for a subscription.
     */
    public function getAdminSubscriptionUrl(string $type, string $subscriptionId): string
    {
        return match ($type) {
            'quran' => "/admin/quran-subscriptions/{$subscriptionId}",
            'academic' => "/admin/academic-subscriptions/{$subscriptionId}",
            default => "/admin/quran-subscriptions/{$subscriptionId}",
        };
    }

    /**
     * Get admin panel URL for a student profile.
     */
    public function getAdminStudentUrl(string $studentId): string
    {
        return "/admin/student-profiles/{$studentId}";
    }

    /**
     * Get admin panel URL for a teacher payout.
     */
    public function getAdminPayoutUrl(string $payoutId): string
    {
        return "/admin/teacher-payouts/{$payoutId}";
    }

    /**
     * Get admin panel URL for a trial request.
     */
    public function getAdminTrialRequestUrl(string $trialRequestId): string
    {
        return "/admin/quran-trial-requests/{$trialRequestId}";
    }

    /**
     * Get payment URL - navigates to payments page.
     *
     * @param  array  $paymentData  Payment data with optional subdomain info
     */
    public function getPaymentUrl(array $paymentData): string
    {
        $subdomain = $paymentData['subdomain'] ?? DefaultAcademy::subdomain();

        return route('student.payments', ['subdomain' => $subdomain]);
    }

    /**
     * Get homework URL based on user role.
     *
     * @param  int|null  $homeworkId  The homework ID
     * @param  Model|null  $session  The associated session
     * @param  User|null  $user  The user viewing
     */
    public function getHomeworkUrl(?int $homeworkId, ?Model $session = null, ?User $user = null): string
    {
        if ($session && $user) {
            return $this->getSessionUrl($session, $user);
        }

        return '/';
    }

    /**
     * Get teacher earnings URL based on teacher type.
     *
     * @param  User|null  $teacher  The teacher user (to determine Quran vs Academic panel)
     */
    public function getTeacherEarningsUrl(?User $teacher = null): string
    {
        if ($teacher && $teacher->hasRole([UserType::ACADEMIC_TEACHER->value])) {
            return '/academic-teacher-panel/teacher-earnings';
        }

        return '/teacher-panel/teacher-earnings';
    }

    /**
     * Get student subscriptions URL.
     *
     * @param  User|null  $student  The student user (to get academy subdomain)
     */
    public function getSubscriptionsUrl(?User $student = null): string
    {
        $subdomain = $student?->academy?->subdomain ?? DefaultAcademy::subdomain();

        return route('student.subscriptions', ['subdomain' => $subdomain]);
    }

    /**
     * Get teacher trial request URL in the Filament teacher panel.
     */
    public function getTeacherTrialRequestUrl(string $trialRequestId): string
    {
        return "/teacher-panel/quran-trial-requests/{$trialRequestId}";
    }

    /**
     * Get student trial request URL.
     */
    public function getStudentTrialRequestUrl(string $trialRequestId, ?string $subdomain = null): string
    {
        $subdomain = $subdomain ?? DefaultAcademy::subdomain();

        return route('student.trial-requests.show', [
            'subdomain' => $subdomain,
            'trialRequest' => $trialRequestId,
        ]);
    }

    /**
     * Get parent dashboard URL.
     */
    public function getParentDashboardUrl(?string $subdomain = null): string
    {
        $subdomain = $subdomain ?? DefaultAcademy::subdomain();

        return route('parent.dashboard', ['subdomain' => $subdomain]);
    }
}
