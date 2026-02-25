<?php

namespace App\Http\Controllers\Api\V1\ParentApi;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\ParentStudentRelationship;
use App\Models\QuranSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use ApiResponses;

    /**
     * Get all subscriptions for linked children.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        // Get all linked children
        $children = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->with('student.user')
            ->get();

        $subscriptions = [];

        foreach ($children as $relationship) {
            $student = $relationship->student;
            $studentUserId = $student->user?->id ?? $student->id;

            // Filter by specific child if requested
            if ($request->filled('child_id') && $student->id != $request->child_id) {
                continue;
            }

            // Get Quran subscriptions
            $quranSubs = QuranSubscription::where('student_id', $studentUserId)
                ->with(['quranTeacher.user', 'individualCircle', 'circle'])
                ->get();

            foreach ($quranSubs as $sub) {
                $subscriptions[] = [
                    'id' => $sub->id,
                    'type' => 'quran',
                    'child_id' => $student->id,
                    'child_name' => $student->full_name,
                    'name' => $sub->individualCircle?->name ?? $sub->circle?->name ?? 'اشتراك قرآني',
                    'teacher_name' => $sub->quranTeacher?->user?->name,
                    'status' => $sub->status,
                    'sessions_total' => $sub->sessions_count,
                    'sessions_used' => $sub->completed_sessions_count ?? 0,
                    'sessions_remaining' => $sub->remaining_sessions ?? $sub->sessions_count,
                    'price' => $sub->price,
                    'currency' => $sub->currency ?? getCurrencyCode(),
                    'payment_status' => $sub->payment_status ?? 'pending',
                    'start_date' => $sub->start_date?->toDateString(),
                    'end_date' => $sub->end_date?->toDateString(),
                    'auto_renew' => $sub->auto_renew ?? false,
                    'in_grace_period' => $sub->isInGracePeriod(),
                    'needs_renewal' => $sub->needsRenewal(),
                    'grace_period_ends_at' => $sub->getGracePeriodEndsAt()?->toDateString(),
                    'paid_until' => ($sub->ends_at ?? $sub->end_date)?->toDateString(),
                    'created_at' => $sub->created_at->toISOString(),
                ];
            }

            // Get Academic subscriptions
            $academicSubs = AcademicSubscription::where('student_id', $studentUserId)
                ->with(['academicTeacher.user', 'subject'])
                ->get();

            foreach ($academicSubs as $sub) {
                $subscriptions[] = [
                    'id' => $sub->id,
                    'type' => 'academic',
                    'child_id' => $student->id,
                    'child_name' => $student->full_name,
                    'name' => $sub->subject?->name ?? $sub->subject_name ?? 'اشتراك أكاديمي',
                    'teacher_name' => $sub->academicTeacher?->user?->name,
                    'status' => $sub->status,
                    'sessions_total' => $sub->sessions_count,
                    'sessions_used' => $sub->completed_sessions_count ?? 0,
                    'sessions_remaining' => $sub->remaining_sessions ?? $sub->sessions_count,
                    'price' => $sub->total_price ?? $sub->price,
                    'currency' => $sub->currency ?? getCurrencyCode(),
                    'payment_status' => $sub->payment_status ?? 'pending',
                    'start_date' => $sub->start_date?->toDateString(),
                    'end_date' => $sub->end_date?->toDateString(),
                    'auto_renew' => $sub->auto_renew ?? false,
                    'in_grace_period' => $sub->isInGracePeriod(),
                    'needs_renewal' => $sub->needsRenewal(),
                    'grace_period_ends_at' => $sub->getGracePeriodEndsAt()?->toDateString(),
                    'paid_until' => ($sub->ends_at ?? $sub->end_date)?->toDateString(),
                    'created_at' => $sub->created_at->toISOString(),
                ];
            }

            // Get Course subscriptions - use student_id (StudentProfile.id)
            $courseSubs = CourseSubscription::where('student_id', $student->id)
                ->with(['interactiveCourse.assignedTeacher.user', 'recordedCourse'])
                ->get();

            foreach ($courseSubs as $sub) {
                $subscriptions[] = [
                    'id' => $sub->id,
                    'type' => 'course',
                    'child_id' => $student->id,
                    'child_name' => $student->full_name,
                    'name' => $sub->course?->title ?? 'دورة تفاعلية',
                    'teacher_name' => $sub->course?->assignedTeacher?->user?->name,
                    'status' => $sub->status,
                    'progress_percentage' => $sub->progress_percentage ?? 0,
                    'completed_sessions' => $sub->completed_sessions ?? 0,
                    'total_sessions' => $sub->course?->total_sessions ?? 0,
                    'price' => $sub->course?->price ?? 0,
                    'currency' => $sub->currency ?? getCurrencyCode(),
                    'payment_status' => $sub->payment_status ?? 'paid',
                    'enrolled_at' => $sub->created_at->toISOString(),
                ];
            }
        }

        // Filter by status
        if ($request->filled('status')) {
            $subscriptions = array_filter($subscriptions, fn ($s) => $s['status'] === $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $subscriptions = array_filter($subscriptions, fn ($s) => $s['type'] === $request->type);
        }

        // Sort by created_at desc
        usort($subscriptions, fn ($a, $b) => strtotime($b['created_at'] ?? $b['enrolled_at'] ?? '0') <=>
            strtotime($a['created_at'] ?? $a['enrolled_at'] ?? '0')
        );

        return $this->success([
            'subscriptions' => array_values($subscriptions),
            'total' => count($subscriptions),
        ], __('Subscriptions retrieved successfully'));
    }

    /**
     * Get a specific subscription.
     */
    public function show(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        $relationships = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->with('student.user')
            ->get();

        // QuranSubscription / AcademicSubscription: student_id references User.id
        $childUserIds = $relationships
            ->map(fn ($r) => $r->student->user?->id ?? $r->student->id)
            ->filter()
            ->toArray();

        // CourseSubscription: student_id references StudentProfile.id (NOT User.id)
        $childStudentProfileIds = $relationships
            ->pluck('student_id')
            ->filter()
            ->toArray();

        $subscription = match ($type) {
            'quran' => QuranSubscription::where('id', $id)
                ->whereIn('student_id', $childUserIds)
                ->with(['quranTeacher.user', 'individualCircle', 'circle', 'student', 'sessions'])
                ->first(),
            'academic' => AcademicSubscription::where('id', $id)
                ->whereIn('student_id', $childUserIds)
                ->with(['academicTeacher.user', 'subject', 'student', 'sessions'])
                ->first(),
            'course' => CourseSubscription::where('id', $id)
                ->whereIn('student_id', $childStudentProfileIds)
                ->with(['interactiveCourse.assignedTeacher.user', 'recordedCourse'])
                ->first(),
            default => null,
        };

        if (! $subscription) {
            return $this->notFound(__('Subscription not found.'));
        }

        return $this->success([
            'subscription' => $this->formatSubscriptionDetail($type, $subscription),
        ], __('Subscription retrieved successfully'));
    }

    /**
     * Format subscription detail based on type.
     */
    protected function formatSubscriptionDetail(string $type, $subscription): array
    {
        $base = [
            'id' => $subscription->id,
            'type' => $type,
        ];

        if ($type === 'quran') {
            return array_merge($base, [
                'child_name' => $subscription->student?->name ?? $subscription->student?->full_name,
                'name' => $subscription->individualCircle?->name ?? $subscription->circle?->name ?? 'اشتراك قرآني',
                'circle_type' => $subscription->circle_id ? 'group' : 'individual',
                'teacher' => $subscription->quranTeacher?->user ? [
                    'id' => $subscription->quranTeacher->user->id,
                    'name' => $subscription->quranTeacher->user->name,
                    'avatar' => $subscription->quranTeacher->user->avatar
                        ? asset('storage/'.$subscription->quranTeacher->user->avatar)
                        : null,
                ] : null,
                'status' => $subscription->status,
                'sessions_total' => $subscription->sessions_count,
                'sessions_used' => $subscription->completed_sessions_count ?? 0,
                'sessions_remaining' => $subscription->remaining_sessions ?? $subscription->sessions_count,
                'price' => $subscription->price,
                'currency' => $subscription->currency ?? getCurrencyCode(),
                'payment_status' => $subscription->payment_status ?? 'pending',
                'start_date' => $subscription->start_date?->toDateString(),
                'end_date' => $subscription->end_date?->toDateString(),
                'auto_renew' => $subscription->auto_renew ?? false,
                'schedule' => $subscription->schedule ?? [],
                'recent_sessions' => $subscription->sessions?->take(5)->map(fn ($s) => [
                    'id' => $s->id,
                    'scheduled_at' => $s->scheduled_at?->toISOString(),
                    'status' => $s->status->value ?? $s->status,
                ])->toArray() ?? [],
                'created_at' => $subscription->created_at->toISOString(),
            ]);
        }

        if ($type === 'academic') {
            return array_merge($base, [
                'child_name' => $subscription->student?->name ?? 'طالب',
                'name' => $subscription->subject?->name ?? $subscription->subject_name ?? 'اشتراك أكاديمي',
                'subject' => $subscription->subject ? [
                    'id' => $subscription->subject->id,
                    'name' => $subscription->subject->name,
                ] : null,
                'teacher' => $subscription->academicTeacher?->user ? [
                    'id' => $subscription->academicTeacher->user->id,
                    'name' => $subscription->academicTeacher->user->name,
                    'avatar' => $subscription->academicTeacher->user->avatar
                        ? asset('storage/'.$subscription->academicTeacher->user->avatar)
                        : null,
                ] : null,
                'status' => $subscription->status,
                'sessions_total' => $subscription->sessions_count,
                'sessions_used' => $subscription->completed_sessions_count ?? 0,
                'sessions_remaining' => $subscription->remaining_sessions ?? $subscription->sessions_count,
                'price' => $subscription->total_price ?? $subscription->price,
                'currency' => $subscription->currency ?? getCurrencyCode(),
                'payment_status' => $subscription->payment_status ?? 'pending',
                'start_date' => $subscription->start_date?->toDateString(),
                'end_date' => $subscription->end_date?->toDateString(),
                'auto_renew' => $subscription->auto_renew ?? false,
                'schedule' => $subscription->schedule ?? [],
                'recent_sessions' => $subscription->sessions?->take(5)->map(fn ($s) => [
                    'id' => $s->id,
                    'scheduled_at' => $s->scheduled_at?->toISOString(),
                    'status' => $s->status->value ?? $s->status,
                ])->toArray() ?? [],
                'created_at' => $subscription->created_at->toISOString(),
            ]);
        }

        // Course
        return array_merge($base, [
            'child_name' => $subscription->user?->name,
            'course' => $subscription->course ? [
                'id' => $subscription->course->id,
                'title' => $subscription->course->title,
                'thumbnail' => $subscription->course->thumbnail
                    ? asset('storage/'.$subscription->course->thumbnail)
                    : null,
                'total_sessions' => $subscription->course->total_sessions,
            ] : null,
            'teacher' => $subscription->course?->assignedTeacher?->user ? [
                'id' => $subscription->course->assignedTeacher->user->id,
                'name' => $subscription->course->assignedTeacher->user->name,
                'avatar' => $subscription->course->assignedTeacher->user->avatar
                    ? asset('storage/'.$subscription->course->assignedTeacher->user->avatar)
                    : null,
            ] : null,
            'status' => $subscription->status,
            'progress_percentage' => $subscription->progress_percentage ?? 0,
            'completed_sessions' => $subscription->completed_sessions ?? 0,
            'payment_status' => $subscription->payment_status ?? 'paid',
            'enrolled_at' => $subscription->created_at->toISOString(),
        ]);
    }
}
