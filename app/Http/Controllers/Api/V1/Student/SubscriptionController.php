<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use ApiResponses;

    /**
     * Get all subscriptions for the student.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->get('status'); // active, expired, cancelled, all
        $type = $request->get('type'); // quran, academic, course, or null for all

        $subscriptions = [];

        if (!$type || $type === 'quran') {
            $query = QuranSubscription::where('student_id', $user->id)
                ->with(['quranTeacher', 'package', 'individualCircle']);

            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            $quranSubs = $query->orderBy('created_at', 'desc')->get();

            foreach ($quranSubs as $sub) {
                $subscriptions[] = $this->formatSubscription($sub, 'quran');
            }
        }

        if (!$type || $type === 'academic') {
            $query = AcademicSubscription::where('student_id', $user->id)
                ->with(['teacher', 'subject', 'gradeLevel']);

            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            $academicSubs = $query->orderBy('created_at', 'desc')->get();

            foreach ($academicSubs as $sub) {
                $subscriptions[] = $this->formatSubscription($sub, 'academic');
            }
        }

        if (!$type || $type === 'course') {
            $query = CourseSubscription::where('user_id', $user->id)
                ->with(['course.assignedTeacher']);

            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            $courseSubs = $query->orderBy('created_at', 'desc')->get();

            foreach ($courseSubs as $sub) {
                $subscriptions[] = $this->formatSubscription($sub, 'course');
            }
        }

        // Sort by created_at
        usort($subscriptions, function ($a, $b) {
            return strtotime($b['created_at']) <=> strtotime($a['created_at']);
        });

        return $this->success([
            'subscriptions' => $subscriptions,
            'total' => count($subscriptions),
        ], __('Subscriptions retrieved successfully'));
    }

    /**
     * Get a specific subscription.
     *
     * @param Request $request
     * @param string $type
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        $subscription = null;

        switch ($type) {
            case 'quran':
                $subscription = QuranSubscription::where('id', $id)
                    ->where('student_id', $user->id)
                    ->with(['quranTeacher', 'package', 'individualCircle', 'sessions', 'payments'])
                    ->first();
                break;

            case 'academic':
                $subscription = AcademicSubscription::where('id', $id)
                    ->where('student_id', $user->id)
                    ->with(['teacher', 'subject', 'gradeLevel', 'sessions', 'payments'])
                    ->first();
                break;

            case 'course':
                $subscription = CourseSubscription::where('id', $id)
                    ->where('user_id', $user->id)
                    ->with(['course.assignedTeacher', 'course.sessions', 'payments'])
                    ->first();
                break;
        }

        if (!$subscription) {
            return $this->notFound(__('Subscription not found.'));
        }

        return $this->success([
            'subscription' => $this->formatSubscriptionDetails($subscription, $type),
        ], __('Subscription retrieved successfully'));
    }

    /**
     * Get sessions for a specific subscription.
     *
     * @param Request $request
     * @param string $type
     * @param int $id
     * @return JsonResponse
     */
    public function sessions(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        $sessions = [];

        switch ($type) {
            case 'quran':
                $subscription = QuranSubscription::where('id', $id)
                    ->where('student_id', $user->id)
                    ->first();

                if (!$subscription) {
                    return $this->notFound(__('Subscription not found.'));
                }

                $sessions = $subscription->sessions()
                    ->with(['quranTeacher'])
                    ->orderBy('scheduled_at', 'desc')
                    ->get()
                    ->map(fn($s) => $this->formatSessionBrief($s, 'quran'))
                    ->toArray();
                break;

            case 'academic':
                $subscription = AcademicSubscription::where('id', $id)
                    ->where('student_id', $user->id)
                    ->first();

                if (!$subscription) {
                    return $this->notFound(__('Subscription not found.'));
                }

                $sessions = $subscription->sessions()
                    ->with(['academicTeacher.user'])
                    ->orderBy('scheduled_at', 'desc')
                    ->get()
                    ->map(fn($s) => $this->formatSessionBrief($s, 'academic'))
                    ->toArray();
                break;

            case 'course':
                $subscription = CourseSubscription::where('id', $id)
                    ->where('user_id', $user->id)
                    ->with(['course'])
                    ->first();

                if (!$subscription) {
                    return $this->notFound(__('Subscription not found.'));
                }

                $sessions = $subscription->course->sessions()
                    ->orderBy('scheduled_date', 'desc')
                    ->get()
                    ->map(fn($s) => $this->formatSessionBrief($s, 'interactive'))
                    ->toArray();
                break;
        }

        return $this->success([
            'sessions' => $sessions,
            'total' => count($sessions),
        ], __('Sessions retrieved successfully'));
    }

    /**
     * Toggle auto-renew for a subscription.
     *
     * @param Request $request
     * @param string $type
     * @param int $id
     * @return JsonResponse
     */
    public function toggleAutoRenew(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        $subscription = null;

        switch ($type) {
            case 'quran':
                $subscription = QuranSubscription::where('id', $id)
                    ->where('student_id', $user->id)
                    ->whereIn('status', ['active', 'pending'])
                    ->first();
                break;

            case 'academic':
                $subscription = AcademicSubscription::where('id', $id)
                    ->where('student_id', $user->id)
                    ->whereIn('status', ['active', 'pending'])
                    ->first();
                break;
        }

        if (!$subscription) {
            return $this->notFound(__('Subscription not found or cannot be modified.'));
        }

        $subscription->update([
            'auto_renew' => !$subscription->auto_renew,
        ]);

        return $this->success([
            'auto_renew' => $subscription->auto_renew,
        ], $subscription->auto_renew
            ? __('Auto-renewal enabled.')
            : __('Auto-renewal disabled.')
        );
    }

    /**
     * Cancel a subscription.
     *
     * @param Request $request
     * @param string $type
     * @param int $id
     * @return JsonResponse
     */
    public function cancel(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        $subscription = null;

        switch ($type) {
            case 'quran':
                $subscription = QuranSubscription::where('id', $id)
                    ->where('student_id', $user->id)
                    ->whereIn('status', ['active', 'pending'])
                    ->first();
                break;

            case 'academic':
                $subscription = AcademicSubscription::where('id', $id)
                    ->where('student_id', $user->id)
                    ->whereIn('status', ['active', 'pending'])
                    ->first();
                break;

            case 'course':
                $subscription = CourseSubscription::where('id', $id)
                    ->where('user_id', $user->id)
                    ->whereIn('status', ['active', 'pending'])
                    ->first();
                break;
        }

        if (!$subscription) {
            return $this->notFound(__('Subscription not found or cannot be cancelled.'));
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->get('reason'),
        ]);

        return $this->success([
            'cancelled' => true,
        ], __('Subscription cancelled successfully.'));
    }

    /**
     * Format subscription for list view.
     */
    protected function formatSubscription($subscription, string $type): array
    {
        $title = match ($type) {
            'quran' => $subscription->package_name_ar ?? 'اشتراك قرآني',
            'academic' => $subscription->subject_name ?? $subscription->subject?->name ?? 'اشتراك أكاديمي',
            'course' => $subscription->course?->title ?? 'اشتراك دورة',
            default => 'اشتراك',
        };

        $teacher = match ($type) {
            'quran' => $subscription->quranTeacher,
            'academic' => $subscription->teacher,
            'course' => $subscription->course?->assignedTeacher,
            default => null,
        };

        return [
            'id' => $subscription->id,
            'type' => $type,
            'subscription_code' => $subscription->subscription_code,
            'title' => $title,
            'status' => $subscription->status->value ?? $subscription->status,
            'status_label' => $subscription->status->label ?? $subscription->status,
            'start_date' => ($subscription->starts_at ?? $subscription->start_date)?->toDateString(),
            'end_date' => ($subscription->ends_at ?? $subscription->end_date)?->toDateString(),
            'auto_renew' => $subscription->auto_renew ?? false,
            'price' => [
                'amount' => $subscription->final_price ?? $subscription->monthly_price ?? 0,
                'currency' => $subscription->currency ?? 'SAR',
            ],
            'teacher' => $teacher?->user ? [
                'id' => $teacher->user->id,
                'name' => $teacher->user->name,
                'avatar' => $teacher->user->avatar ? asset('storage/' . $teacher->user->avatar) : null,
            ] : null,
            'sessions' => $this->getSessionStats($subscription, $type),
            'created_at' => $subscription->created_at->toISOString(),
        ];
    }

    /**
     * Format subscription for detail view.
     */
    protected function formatSubscriptionDetails($subscription, string $type): array
    {
        $base = $this->formatSubscription($subscription, $type);

        // Add payment info
        $base['payment_status'] = $subscription->payment_status->value ?? $subscription->payment_status;
        $base['payment_status_label'] = $subscription->payment_status->label ?? $subscription->payment_status;
        $base['next_billing_date'] = $subscription->next_billing_date?->toDateString();
        $base['billing_cycle'] = $subscription->billing_cycle->value ?? $subscription->billing_cycle;

        // Add package/plan info
        $base['package'] = [
            'name_ar' => $subscription->package_name_ar,
            'name_en' => $subscription->package_name_en,
            'description' => $subscription->package_description_ar,
            'features' => $subscription->package_features ?? [],
        ];

        // Add type-specific details
        if ($type === 'quran') {
            $base['quran_details'] = [
                'subscription_type' => $subscription->subscription_type,
                'memorization_level' => $subscription->memorization_level,
                'memorization_level_label' => $subscription->memorization_level_label,
                'current_surah' => $subscription->current_surah,
                'current_surah_name' => $subscription->current_surah_name,
            ];
        }

        if ($type === 'academic') {
            $base['academic_details'] = [
                'subject' => $subscription->subject?->name,
                'grade_level' => $subscription->gradeLevel?->name,
                'sessions_per_week' => $subscription->sessions_per_week,
                'hourly_rate' => $subscription->hourly_rate,
            ];
        }

        if ($type === 'course') {
            $base['course_details'] = [
                'course_id' => $subscription->course_id,
                'course_title' => $subscription->course?->title,
                'course_description' => $subscription->course?->description,
                'total_sessions' => $subscription->course?->total_sessions,
                'progress' => $subscription->progress_percentage ?? 0,
            ];
        }

        // Recent payments
        if ($subscription->payments) {
            $base['recent_payments'] = $subscription->payments()
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'amount' => $p->amount,
                    'currency' => $p->currency,
                    'status' => $p->status,
                    'paid_at' => $p->paid_at?->toISOString(),
                ])
                ->toArray();
        }

        return $base;
    }

    /**
     * Get session statistics for a subscription.
     */
    protected function getSessionStats($subscription, string $type): array
    {
        if ($type === 'quran') {
            return [
                'total' => $subscription->total_sessions ?? 0,
                'used' => $subscription->sessions_used ?? 0,
                'remaining' => $subscription->sessions_remaining ?? 0,
            ];
        }

        if ($type === 'academic') {
            return [
                'scheduled' => $subscription->total_sessions_scheduled ?? 0,
                'completed' => $subscription->total_sessions_completed ?? 0,
                'missed' => $subscription->total_sessions_missed ?? 0,
            ];
        }

        if ($type === 'course') {
            $completed = $subscription->course?->sessions()
                ->where('status', 'completed')
                ->count() ?? 0;
            $total = $subscription->course?->total_sessions ?? 0;

            return [
                'total' => $total,
                'completed' => $completed,
                'remaining' => max(0, $total - $completed),
            ];
        }

        return [];
    }

    /**
     * Format session for subscription sessions list.
     */
    protected function formatSessionBrief($session, string $type): array
    {
        $scheduledAt = $type === 'interactive'
            ? \Carbon\Carbon::parse($session->scheduled_date . ' ' . $session->scheduled_time)->toISOString()
            : $session->scheduled_at?->toISOString();

        return [
            'id' => $session->id,
            'type' => $type,
            'title' => $session->title,
            'status' => $session->status->value ?? $session->status,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $session->duration_minutes ?? 45,
        ];
    }
}
