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
     * @param Model $session The session model
     * @param User $user The user viewing the notification
     * @return string The URL to navigate to
     */
    public function getSessionUrl(Model $session, User $user): string
    {
        $sessionType = strtolower(class_basename($session));
        $sessionId = $session->id;

        if ($user->hasRole(['student'])) {
            return $this->getStudentSessionUrl($sessionType, $sessionId);
        }

        if ($user->hasRole(['quran_teacher'])) {
            return $this->getTeacherCircleUrl($session);
        }

        if ($user->hasRole(['academic_teacher'])) {
            return "/teacher/academic-sessions/{$sessionId}";
        }

        return '/';
    }

    /**
     * Get session URL for students.
     *
     * @param string $sessionType The session type
     * @param mixed $sessionId The session ID
     * @return string
     */
    private function getStudentSessionUrl(string $sessionType, mixed $sessionId): string
    {
        return match ($sessionType) {
            'quransession' => "/sessions/{$sessionId}",
            'academicsession' => "/academic-sessions/{$sessionId}",
            'interactivecoursesession' => "/student/interactive-sessions/{$sessionId}",
            default => "/sessions/{$sessionId}",
        };
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
        if (method_exists($session, 'circle') && $session->circle) {
            $circle = $session->circle;

            if ($circle->circle_type === 'individual') {
                return "/teacher/individual-circles/{$circle->id}";
            }

            if ($circle->circle_type === 'group') {
                return "/teacher/group-circles/{$circle->id}";
            }
        }

        return "/teacher/sessions/{$session->id}";
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
