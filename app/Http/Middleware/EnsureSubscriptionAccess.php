<?php

namespace App\Http\Middleware;

use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\QuranSession;
use App\Models\AcademicSession;
use App\Enums\CourseType;
use App\Enums\EnrollmentStatus;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;
use App\Models\SubscriptionAccessLog;
use App\Services\AcademyContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionAccess
{
    /**
     * Handle an incoming request.
     * Ensures the user has a valid subscription to access the requested resource.
     *
     * @param Closure(Request):Response $next
     * @param  string  $resourceType  The type of resource being accessed (quran_session, academic_session, etc.)
     */
    public function handle(Request $request, Closure $next, string $resourceType): Response
    {
        $user = $request->user();
        $resourceId = $this->extractResourceId($request);

        if (!$user || !$resourceId) {
            return $this->denyAccess($request, $user, $resourceType, $resourceId, 'missing_auth_or_resource');
        }

        // Find the subscription related to this resource
        $subscription = $this->findSubscriptionForResource($user, $resourceType, $resourceId);

        if (!$subscription) {
            return $this->denyAccess($request, $user, $resourceType, $resourceId, 'no_subscription', null);
        }

        // Check if subscription allows access
        if (!$subscription->canAccess()) {
            $reason = match (true) {
                $subscription->payment_status !== SubscriptionPaymentStatus::PAID => 'payment_required',
                $subscription->status === SessionSubscriptionStatus::PAUSED => 'subscription_paused',
                $subscription->status === SessionSubscriptionStatus::CANCELLED => 'subscription_cancelled',
                default => 'subscription_inactive',
            };

            return $this->denyAccess($request, $user, $resourceType, $resourceId, $reason, $subscription);
        }

        // Update last accessed timestamp
        $subscription->update([
            'last_accessed_at' => now(),
            'last_accessed_platform' => $this->detectPlatform($request),
        ]);

        // Log successful access
        $this->logAccess($user, $subscription, $resourceType, $resourceId, 'access_granted', $request);

        // Attach subscription to request for controller use
        $request->attributes->add(['subscription' => $subscription]);

        return $next($request);
    }

    /**
     * Extract resource ID from route parameters
     */
    protected function extractResourceId(Request $request): ?string
    {
        // Try common route parameter names
        return $request->route('id')
            ?? $request->route('session')
            ?? $request->route('course')
            ?? $request->route('lesson')
            ?? null;
    }

    /**
     * Find subscription for the given resource
     */
    protected function findSubscriptionForResource($user, string $resourceType, $resourceId)
    {
        return match ($resourceType) {
            'quran_session' => $this->findQuranSubscriptionForSession($user, $resourceId),
            'academic_session' => $this->findAcademicSubscriptionForSession($user, $resourceId),
            'interactive_course' => $this->findCourseSubscriptionForCourse($user, $resourceId, 'interactive'),
            'recorded_course', 'course' => $this->findCourseSubscriptionForCourse($user, $resourceId, 'recorded'),
            default => null,
        };
    }

    /**
     * Find Quran subscription for a session
     */
    protected function findQuranSubscriptionForSession($user, $sessionId)
    {
        // Get the session first
        $session = QuranSession::find($sessionId);

        if (!$session) {
            return null;
        }

        // Find subscription that matches this student and session's teacher
        // No status filter — canAccess() will determine if access is allowed
        return QuranSubscription::where('student_id', $user->id)
            ->where('quran_teacher_id', $session->quran_teacher_id)
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Find Academic subscription for a session
     */
    protected function findAcademicSubscriptionForSession($user, $sessionId)
    {
        // Get the session first
        $session = AcademicSession::find($sessionId);

        if (!$session) {
            return null;
        }

        // Find subscription that matches this student and session's teacher
        // No status filter — canAccess() will determine if access is allowed
        return AcademicSubscription::where('student_id', $user->id)
            ->where('teacher_id', $session->academic_teacher_id)
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Find Course subscription
     */
    protected function findCourseSubscriptionForCourse($user, $courseId, $courseType)
    {
        $courseTypeValue = $courseType instanceof CourseType ? $courseType->value : $courseType;
        $column = $courseTypeValue === 'interactive' ? 'interactive_course_id' : 'recorded_course_id';

        // No status filter — canAccess() will determine if access is allowed
        return CourseSubscription::where('student_id', $user->id)
            ->where($column, $courseId)
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Deny access with structured error response
     */
    protected function denyAccess(
        Request $request,
        $user,
        string $resourceType,
        $resourceId,
        string $reason,
        $subscription = null
    ): Response {
        // Log the denial
        if ($user) {
            $this->logAccess($user, $subscription, $resourceType, $resourceId, 'access_denied', $request, $reason);
        }

        $message = match ($reason) {
            'no_subscription' => __('You do not have a subscription for this content'),
            'payment_required' => __('Payment is required to access this content'),
            'subscription_paused' => __('Your subscription is paused'),
            'subscription_cancelled' => __('Your subscription has been cancelled'),
            'subscription_inactive' => __('Your subscription is not active'),
            default => __('Access denied'),
        };

        // For API requests, return JSON
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error_code' => 'ACCESS_DENIED',
                'reason' => $reason,
                'web_url' => $this->getWebPurchaseUrl($resourceType, $resourceId),
            ], 403);
        }

        // For web requests, redirect or abort
        abort(403, $message);
    }

    /**
     * Log access attempt
     */
    protected function logAccess(
        $user,
        $subscription,
        string $resourceType,
        $resourceId,
        string $action,
        Request $request,
        ?string $reason = null
    ): void {
        try {
            SubscriptionAccessLog::create([
                'tenant_id' => $request->attributes->get('academy')?->id ?? AcademyContextService::getApiContextAcademyId(),
                'subscription_type' => $subscription ? get_class($subscription) : 'none',
                'subscription_id' => $subscription?->id,
                'user_id' => $user->id,
                'platform' => $this->detectPlatform($request),
                'action' => $action,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'metadata' => json_encode([
                    'user_agent' => $request->userAgent(),
                    'ip' => $request->ip(),
                    'reason' => $reason,
                ]),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to log subscription access', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'action' => $action,
            ]);
        }
    }

    /**
     * Detect platform from request
     */
    protected function detectPlatform(Request $request): string
    {
        $platform = $request->header('X-Platform');

        if ($platform === 'mobile') {
            return 'mobile';
        }

        if ($platform === 'web') {
            return 'web';
        }

        // Default: API requests without explicit header are assumed mobile
        if ($request->is('api/*')) {
            return 'mobile';
        }

        return 'web';
    }

    /**
     * Generate web purchase URL for mobile users
     */
    protected function getWebPurchaseUrl(string $resourceType, $resourceId): string
    {
        // Map resource types to purchase types
        $type = match ($resourceType) {
            'quran_session' => 'quran_teacher',
            'academic_session' => 'academic_teacher',
            'interactive_course', 'recorded_course' => 'course',
            default => 'course',
        };

        return route('mobile.purchase.redirect', [
            'type' => $type,
            'id' => $resourceId,
        ]);
    }
}
