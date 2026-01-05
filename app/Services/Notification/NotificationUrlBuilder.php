<?php

namespace App\Services\Notification;

use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
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
     * @param Model $session The session model
     * @param User $user The user viewing the notification
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
            return route('academic-teacher.sessions.show', [
                'subdomain' => $subdomain,
                'session' => $session->id,
            ]);
        }

        return '/';
    }

    /**
     * Get session URL for students.
     *
     * @param Model $session The session model
     * @param string $subdomain The academy subdomain
     * @return string
     */
    private function getStudentSessionUrl(Model $session, string $subdomain): string
    {
        $sessionClass = class_basename($session);

        return match ($sessionClass) {
            'QuranSession' => route('student.quran-sessions.show', [
                'subdomain' => $subdomain,
                'session' => $session->id,
            ]),
            'AcademicSession' => route('student.academic-sessions.show', [
                'subdomain' => $subdomain,
                'session' => $session->id,
            ]),
            'InteractiveCourseSession' => route('student.interactive-sessions.show', [
                'subdomain' => $subdomain,
                'session' => $session->id,
            ]),
            default => "/student/sessions/{$session->id}",
        };
    }

    /**
     * Get session URL for parents.
     *
     * @param Model $session The session model
     * @param string $subdomain The academy subdomain
     * @return string
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
     * @param Model $session The session model
     * @return string
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
     * @param Model $session The session model
     * @return string
     */
    public function getTeacherCircleUrl(Model $session): string
    {
        $subdomain = $session->academy?->subdomain ?? 'itqan-academy';

        // Check for individual circle (QuranIndividualCircle model)
        if (method_exists($session, 'individualCircle') && $session->individualCircle) {
            return route('teacher.individual-circles.show', [
                'subdomain' => $subdomain,
                'circle' => $session->individualCircle->id,
            ]);
        }

        // Check for group circle (QuranCircle model)
        if (method_exists($session, 'circle') && $session->circle) {
            return route('teacher.group-circles.show', [
                'subdomain' => $subdomain,
                'circle' => $session->circle->id,
            ]);
        }

        return route('teacher.quran-sessions.show', [
            'subdomain' => $subdomain,
            'session' => $session->id,
        ]);
    }

    /**
     * Get payment URL based on subscription type.
     *
     * @param array $paymentData Payment data with optional subscription info
     * @return string
     */
    public function getPaymentUrl(array $paymentData): string
    {
        if (isset($paymentData['subscription_id'], $paymentData['subscription_type'])) {
            return match ($paymentData['subscription_type']) {
                'quran' => "/circles/{$paymentData['circle_id']}",
                'academic' => "/academic-subscriptions/{$paymentData['subscription_id']}",
                default => '/subscriptions',
            };
        }

        return '/payments';
    }

    /**
     * Get homework URL.
     *
     * @param int|null $homeworkId The homework ID
     * @param Model|null $session The associated session
     * @param User|null $user The user viewing
     * @return string
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
     *
     * @return string
     */
    public function getTeacherEarningsUrl(): string
    {
        return '/teacher/earnings';
    }

    /**
     * Get subscriptions URL.
     *
     * @return string
     */
    public function getSubscriptionsUrl(): string
    {
        return '/subscriptions';
    }
}
