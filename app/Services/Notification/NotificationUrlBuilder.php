<?php

namespace App\Services\Notification;

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
        $subdomain = $session->academy?->subdomain ?? 'itqan-academy';

        if ($user->hasRole(['student'])) {
            return $this->getStudentSessionUrl($session, $subdomain);
        }

        if ($user->hasRole(['parent'])) {
            return $this->getParentSessionUrl($session, $subdomain);
        }

        if ($user->hasRole(['quran_teacher'])) {
            return $this->getTeacherCircleUrl($session);
        }

        if ($user->hasRole(['academic_teacher'])) {
            return "/academic-teacher-panel/academic-sessions/{$session->id}";
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
            'AcademicSession' => route('student.academic-sessions.show', [
                'subdomain' => $subdomain,
                'session' => $session->id,
            ]),
            default => "/student/sessions/{$session->id}",
        };
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

        return "/parent/sessions/{$sessionType}/{$session->id}";
    }

    /**
     * Get circle URL from session for students.
     *
     * @param  Model  $session  The session model
     */
    public function getCircleUrlFromSession(Model $session): string
    {
        if (method_exists($session, 'circle') && $session->circle) {
            return "/circles/{$session->circle->id}";
        }

        return "/sessions/{$session->id}";
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
            return "/teacher-panel/quran-group-circles/{$session->circle->id}";
        }

        return "/teacher-panel/quran-sessions/{$session->id}";
    }

    /**
     * Get payment URL - navigates to payments page.
     *
     * @param  array  $paymentData  Payment data with optional subdomain info
     */
    public function getPaymentUrl(array $paymentData): string
    {
        $subdomain = $paymentData['subdomain'] ?? 'itqan-academy';

        return route('student.payments', ['subdomain' => $subdomain]);
    }

    /**
     * Get homework URL.
     *
     * @param  int|null  $homeworkId  The homework ID
     * @param  Model|null  $session  The associated session
     * @param  User|null  $user  The user viewing
     */
    public function getHomeworkUrl(?int $homeworkId, ?Model $session = null, ?User $user = null): string
    {
        if ($homeworkId) {
            return "/homework/{$homeworkId}/view";
        }

        if ($session && $user) {
            return $this->getSessionUrl($session, $user);
        }

        return '/homework';
    }

    /**
     * Get teacher earnings URL.
     */
    public function getTeacherEarningsUrl(): string
    {
        return '/teacher/earnings';
    }

    /**
     * Get subscriptions URL.
     */
    public function getSubscriptionsUrl(): string
    {
        return '/subscriptions';
    }
}
