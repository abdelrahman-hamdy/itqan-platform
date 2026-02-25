<?php

namespace App\Http\Middleware;

use Exception;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockMobilePaymentInitiation
{
    /**
     * Prevent payment initiation from mobile API while allowing read-only operations.
     *
     * This middleware ensures compliance with Google Play guidelines by blocking
     * all payment creation/processing from the mobile app. Users must complete
     * purchases on the web platform.
     *
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Detect if request is from mobile API
        $isMobileApi = $this->isMobileRequest($request);

        if (!$isMobileApi) {
            // Not a mobile request, allow through
            return $next($request);
        }

        // Check if this is a payment-related route
        if ($this->isPaymentRoute($request)) {
            // Allow GET requests (viewing payment history)
            if ($request->isMethod('GET')) {
                return $next($request);
            }

            // Block POST/PUT/PATCH/DELETE (payment creation/modification)
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                return $this->blockPayment($request);
            }
        }

        // Not a payment route, allow through
        return $next($request);
    }

    /**
     * Detect if request is from mobile app
     */
    protected function isMobileRequest(Request $request): bool
    {
        // Check X-Platform header (mobile app should set this)
        if ($request->header('X-Platform') === 'mobile') {
            return true;
        }

        // Check if it's an API route (assume mobile)
        if ($request->is('api/v1/*')) {
            return true;
        }

        // Check user agent for mobile indicators
        $userAgent = strtolower($request->userAgent() ?? '');
        if (str_contains($userAgent, 'okhttp') ||  // Android HTTP client
            str_contains($userAgent, 'cfnetwork') || // iOS HTTP client
            str_contains($userAgent, 'dart')) {      // Flutter HTTP client
            return true;
        }

        return false;
    }

    /**
     * Check if this is a payment-related route
     */
    protected function isPaymentRoute(Request $request): bool
    {
        $path = $request->path();

        // Payment initiation routes
        $paymentPatterns = [
            'api/v1/payments/create-intent',
            'api/v1/payments/charge-saved',
            'api/v1/student/payments',
            'api/v1/student/subscriptions/*/purchase',
            'api/v1/student/courses/*/purchase',
            'api/v1/student/courses/*/enroll',
            'api/v1/parent/payments/initiate',
            'api/v1/parent/payments',
        ];

        foreach ($paymentPatterns as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        // Check route name
        $routeName = $request->route()?->getName();
        if ($routeName && str_contains($routeName, 'payment')) {
            // Allow viewing payment history
            if (str_contains($routeName, 'index') || str_contains($routeName, 'show')) {
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Block payment and return structured error
     */
    protected function blockPayment(Request $request): Response
    {
        $webUrl = $this->generateWebPurchaseUrl($request);

        $message = __('Payments must be completed on the web platform');

        return response()->json([
            'message' => $message,
            'message_ar' => 'يرجى إتمام عملية الدفع على الموقع الإلكتروني',
            'message_en' => 'Please complete payment on the website',
            'error_code' => 'MOBILE_PAYMENT_BLOCKED',
            'web_url' => $webUrl,
            'instructions' => __('To complete your purchase, please visit our website. You will be redirected back to the app after payment.'),
        ], 403);
    }

    /**
     * Generate web purchase URL based on requested resource
     */
    protected function generateWebPurchaseUrl(Request $request): string
    {
        // Try to extract resource type and ID from route
        $type = $this->detectResourceType($request);
        $id = $request->route('id')
            ?? $request->route('course')
            ?? $request->route('subscription')
            ?? $request->input('course_id')
            ?? $request->input('teacher_id');

        if ($type && $id) {
            try {
                return route('mobile.purchase.redirect', [
                    'type' => $type,
                    'id' => $id,
                ]);
            } catch (Exception $e) {
                // Route doesn't exist yet, return base URL
                return config('app.url');
            }
        }

        // Fallback to base URL
        return config('app.url');
    }

    /**
     * Detect resource type from route
     */
    protected function detectResourceType(Request $request): ?string
    {
        $path = $request->path();

        if (str_contains($path, 'courses')) {
            return 'course';
        }

        if (str_contains($path, 'quran')) {
            return 'quran_teacher';
        }

        if (str_contains($path, 'academic')) {
            return 'academic_teacher';
        }

        return null;
    }
}
