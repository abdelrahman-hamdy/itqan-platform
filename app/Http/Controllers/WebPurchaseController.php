<?php

namespace App\Http\Controllers;

use Exception;
use Log;
use App\Models\Course;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class WebPurchaseController extends Controller
{
    /**
     * Handle mobile app purchase redirect to web checkout.
     *
     * This endpoint receives users from the mobile app and redirects them
     * to the appropriate web checkout page. After purchase, users are
     * redirected back to the mobile app via deeplink.
     *
     * GET /mobile-purchase/{type}/{id}?token={sanctum_token}
     *
     * @param  string  $type  Resource type: course, quran_teacher, academic_teacher, etc.
     * @param  string  $id    Resource UUID
     */
    public function mobileRedirect(Request $request, string $type, string $id)
    {
        // Validate token was issued for mobile purchase
        $token = $request->bearerToken() ?? $request->query('token');

        if (!$token) {
            return redirect()->route('login')
                ->with('error', __('Authentication required. Please log in to continue.'));
        }

        $tokenData = PersonalAccessToken::findToken($token);

        if (!$tokenData || !$tokenData->can('web-purchase')) {
            return redirect()->route('login')
                ->with('error', __('Invalid or expired purchase link. Please try again from the mobile app.'));
        }

        // Check if token is expired
        if ($tokenData->expires_at && $tokenData->expires_at->isPast()) {
            return redirect()->route('login')
                ->with('error', __('Purchase link has expired. Please generate a new link from the mobile app.'));
        }

        $user = $tokenData->tokenable;

        // Authenticate user for this session
        auth()->login($user);

        // Mark session as coming from mobile app
        session([
            'purchase_source' => 'mobile',
            'mobile_user_id' => $user->id,
            'mobile_resource_type' => $type,
            'mobile_resource_id' => $id,
        ]);

        // Redirect to appropriate purchase page
        try {
            $redirectUrl = $this->getRedirectUrl($type, $id);

            return redirect($redirectUrl)
                ->with('info', __('Complete your purchase below. You will be redirected back to the mobile app after payment.'));
        } catch (Exception $e) {
            Log::error('Mobile purchase redirect failed', [
                'type' => $type,
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('dashboard')
                ->with('error', __('Unable to process purchase request. Please try again.'));
        }
    }

    /**
     * Get the appropriate redirect URL for the resource type
     */
    protected function getRedirectUrl(string $type, string $id): string
    {
        return match ($type) {
            // New subscriptions
            'course' => route('courses.show', ['course' => $id]), // Will have purchase button
            'quran_teacher' => route('quran.teachers.show', ['teacher' => $id]), // Will have subscribe button
            'academic_teacher' => route('academic.teachers.show', ['teacher' => $id]), // Will have subscribe button

            // Subscription renewals
            'quran_subscription' => route('student.subscriptions.show', ['subscription' => $id, 'type' => 'quran']),
            'academic_subscription' => route('student.subscriptions.show', ['subscription' => $id, 'type' => 'academic']),
            'course_subscription' => route('student.subscriptions.show', ['subscription' => $id, 'type' => 'course']),

            // Fallback to dashboard
            default => route('dashboard'),
        };
    }
}
