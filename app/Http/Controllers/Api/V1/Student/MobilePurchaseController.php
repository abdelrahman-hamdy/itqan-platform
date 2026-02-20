<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\Course;
use App\Models\CourseSubscription;
use App\Models\RecordedCourse;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\SubscriptionAccessLog;
use App\Services\AcademyContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MobilePurchaseController extends Controller
{
    /**
     * Generate time-limited web purchase URL for mobile app.
     *
     * GET /api/v1/student/purchase-url/{type}/{id}
     *
     * @param  string  $type  Resource type: course, quran_teacher, academic_teacher
     * @param  string  $id    Resource UUID
     */
    public function getWebUrl(Request $request, string $type, string $id)
    {
        $validator = Validator::make(compact('type', 'id'), [
            'type' => ['required', 'in:course,quran_teacher,academic_teacher'],
            'id' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('Invalid request parameters'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Verify resource exists and is purchasable
        $resource = $this->findResource($type, $id);

        if (!$resource) {
            return response()->json([
                'message' => __('Resource not found'),
                'error_code' => 'RESOURCE_NOT_FOUND',
            ], 404);
        }

        // Check if user already has active subscription
        $existingSubscription = $this->checkExistingSubscription($user, $type, $id);

        if ($existingSubscription && $existingSubscription->canAccess()) {
            return response()->json([
                'message' => __('You already have an active subscription'),
                'subscription' => $this->formatSubscription($existingSubscription),
                'error_code' => 'ALREADY_SUBSCRIBED',
            ], 200);
        }

        // Generate time-limited token for web session (1 hour expiry)
        $token = $user->createToken(
            'mobile-purchase',
            ['web-purchase'],
            now()->addHour()
        );

        // Log purchase attempt
        SubscriptionAccessLog::create([
            'academy_id' => AcademyContextService::getApiContextAcademyId(),
            'user_id' => $user->id,
            'platform' => 'mobile',
            'action' => 'purchase_attempted',
            'resource_type' => $type,
            'resource_id' => $id,
            'metadata' => json_encode([
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ]),
        ]);

        // Generate web URL
        $webUrl = route('mobile.purchase.redirect', [
            'type' => $type,
            'id' => $id,
            'token' => $token->plainTextToken,
        ]);

        return response()->json([
            'web_url' => $webUrl,
            'expires_at' => now()->addHour()->toIso8601String(),
            'instructions' => __('This link will open your web browser to complete the purchase. You will be redirected back to the app after payment.'),
        ]);
    }

    /**
     * Confirm purchase after deeplink return from web.
     *
     * POST /api/v1/student/purchase-completed
     *
     * @param  Request  $request
     */
    public function confirmPurchase(Request $request)
    {
        $validated = $request->validate([
            'subscription_id' => ['required'],
        ]);

        $user = $request->user();

        // Find subscription across all types
        $subscription = $this->findAnySubscription($validated['subscription_id']);

        if (!$subscription) {
            return response()->json([
                'message' => __('Subscription not found'),
                'error_code' => 'SUBSCRIPTION_NOT_FOUND',
            ], 404);
        }

        // Verify ownership
        if ($subscription->student_id !== $user->id) {
            return response()->json([
                'message' => __('Unauthorized access to subscription'),
                'error_code' => 'UNAUTHORIZED',
            ], 403);
        }

        // Update last accessed
        $subscription->update([
            'last_accessed_at' => now(),
            'last_accessed_platform' => 'mobile',
        ]);

        return response()->json([
            'message' => __('Purchase confirmed successfully'),
            'subscription' => $this->formatSubscription($subscription),
        ]);
    }

    /**
     * Find resource by type and ID
     */
    protected function findResource(string $type, string $id)
    {
        return match ($type) {
            'course' => RecordedCourse::find($id) ?? Course::find($id),
            'quran_teacher' => QuranTeacherProfile::where('user_id', $id)->first() ?? QuranTeacherProfile::find($id),
            'academic_teacher' => AcademicTeacherProfile::where('user_id', $id)->first() ?? AcademicTeacherProfile::find($id),
            default => null,
        };
    }

    /**
     * Check for existing subscription
     */
    protected function checkExistingSubscription($user, string $type, string $id)
    {
        return match ($type) {
            'course' => CourseSubscription::where('student_id', $user->id)
                ->where(function ($q) use ($id) {
                    $q->where('interactive_course_id', $id)
                        ->orWhere('recorded_course_id', $id);
                })
                ->orderBy('created_at', 'desc')
                ->first(),

            'quran_teacher' => QuranSubscription::where('student_id', $user->id)
                ->where('quran_teacher_id', $id)
                ->orderBy('created_at', 'desc')
                ->first(),

            'academic_teacher' => AcademicSubscription::where('student_id', $user->id)
                ->where('teacher_id', $id)
                ->orderBy('created_at', 'desc')
                ->first(),

            default => null,
        };
    }

    /**
     * Find subscription by ID across all types
     */
    protected function findAnySubscription(string $id)
    {
        return QuranSubscription::find($id)
            ?? AcademicSubscription::find($id)
            ?? CourseSubscription::find($id);
    }

    /**
     * Format subscription for API response
     */
    protected function formatSubscription($subscription): array
    {
        $type = match (get_class($subscription)) {
            QuranSubscription::class => 'quran',
            AcademicSubscription::class => 'academic',
            CourseSubscription::class => 'course',
            default => 'unknown',
        };

        return [
            'id' => $subscription->id,
            'type' => $type,
            'status' => $subscription->status->value,
            'payment_status' => $subscription->payment_status->value,
            'can_access' => $subscription->canAccess(),
            'purchase_source' => $subscription->purchase_source?->value ?? 'legacy',
            'starts_at' => $subscription->starts_at?->toIso8601String(),
            'ends_at' => $subscription->ends_at?->toIso8601String(),
            'created_at' => $subscription->created_at->toIso8601String(),
        ];
    }
}
