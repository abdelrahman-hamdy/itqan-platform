<?php

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Exceptions\AuthorizationException;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourseSession;
use App\Models\ParentProfile;
use App\Models\Payment;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;

/**
 * Service for verifying parent-child relationships.
 *
 * Centralizes all parent-child verification logic to eliminate duplication
 * across multiple parent controllers.
 */
class ParentChildVerificationService
{
    /**
     * Verify that a child belongs to the parent and return the child profile.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function verifyChildBelongsToParent(
        ParentProfile $parent,
        int|string $childId,
        ?string $errorMessage = null
    ): StudentProfile {
        $child = $parent->students()
            ->where('student_profiles.id', $childId)
            ->forAcademy($parent->academy_id)
            ->first();

        if (! $child) {
            abort(403, $errorMessage ?? ErrorMessages::STUDENT_ACCESS_DENIED);
        }

        return $child;
    }

    /**
     * Get a child without academy filtering (for some contexts).
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function getChildOrFail(
        ParentProfile $parent,
        int|string $childId,
        ?string $errorMessage = null
    ): StudentProfile {
        $child = $parent->students()
            ->where('student_profiles.id', $childId)
            ->first();

        if (! $child) {
            abort(403, $errorMessage ?? ErrorMessages::STUDENT_ACCESS_DENIED);
        }

        return $child;
    }

    /**
     * Get all user IDs for parent's children.
     */
    public function getChildUserIds(ParentProfile $parent): array
    {
        return $parent->students()
            ->with('user')
            ->get()
            ->pluck('user_id')
            ->toArray();
    }

    /**
     * Get all children for a parent with their users loaded.
     */
    public function getChildrenWithUsers(ParentProfile $parent): Collection
    {
        return $parent->students()->with('user')->get();
    }

    /**
     * Verify that a payment belongs to one of the parent's children.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function verifyPaymentBelongsToParent(
        ParentProfile $parent,
        Payment $payment,
        ?string $errorMessage = null
    ): void {
        $childUserIds = $this->getChildUserIds($parent);

        if (! in_array($payment->user_id, $childUserIds)) {
            abort(403, $errorMessage ?? ErrorMessages::PAYMENT_ACCESS_DENIED);
        }
    }

    /**
     * Verify a subscription belongs to one of the parent's children.
     *
     * @param mixed $subscription Any subscription model with a student_id or user_id
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function verifySubscriptionBelongsToParent(
        ParentProfile $parent,
        mixed $subscription,
        ?string $errorMessage = null
    ): void {
        $childUserIds = $this->getChildUserIds($parent);

        // Check for user_id first, then student_id (different subscription models use different fields)
        $subscriptionUserId = $subscription->user_id ?? $subscription->student_id ?? null;

        if (! $subscriptionUserId || ! in_array($subscriptionUserId, $childUserIds)) {
            abort(403, $errorMessage ?? ErrorMessages::SUBSCRIPTION_ACCESS_DENIED);
        }
    }

    /**
     * Verify a certificate belongs to one of the parent's children.
     *
     * @param mixed $certificate Certificate model with user_id
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function verifyCertificateBelongsToParent(
        ParentProfile $parent,
        mixed $certificate,
        ?string $errorMessage = null
    ): void {
        $childUserIds = $this->getChildUserIds($parent);

        if (! in_array($certificate->user_id, $childUserIds)) {
            abort(403, $errorMessage ?? ErrorMessages::CERTIFICATE_ACCESS_DENIED);
        }
    }

    /**
     * Check if a specific child belongs to the parent (without throwing).
     */
    public function childBelongsToParent(ParentProfile $parent, int|string $childId): bool
    {
        return $parent->students()
            ->where('student_profiles.id', $childId)
            ->exists();
    }

    /**
     * Get the active child from session, or null if not set/invalid.
     */
    public function getActiveChild(ParentProfile $parent): ?StudentProfile
    {
        $childId = session('active_child_id');

        if (! $childId) {
            return null;
        }

        return $parent->students()
            ->where('student_profiles.id', $childId)
            ->first();
    }

    /**
     * Get the active child from session, or throw if not set/invalid.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function getActiveChildOrFail(ParentProfile $parent): StudentProfile
    {
        $child = $this->getActiveChild($parent);

        if (! $child) {
            abort(403, ErrorMessages::STUDENT_ACCESS_DENIED);
        }

        return $child;
    }

    /**
     * Verify a subscription belongs to one of parent's children (typed version)
     *
     * Supports QuranSubscription, AcademicSubscription, and CourseSubscription.
     * All subscription types use student_id which references User.id.
     *
     * @param ParentProfile $parent The parent profile
     * @param QuranSubscription|AcademicSubscription|CourseSubscription $subscription The subscription to verify
     * @return void
     * @throws AuthorizationException If subscription doesn't belong to a child
     */
    public function verifySubscriptionBelongsToChild(
        ParentProfile $parent,
        QuranSubscription|AcademicSubscription|CourseSubscription $subscription
    ): void {
        $childUserIds = $this->getChildUserIds($parent);

        if (!in_array($subscription->student_id, $childUserIds)) {
            throw new AuthorizationException('لا يمكنك الوصول إلى هذا الاشتراك');
        }
    }

    /**
     * Verify a session belongs to one of parent's children
     *
     * Supports QuranSession, AcademicSession, and InteractiveCourseSession.
     * Handles both individual sessions (student_id set) and group sessions
     * (circle_id set for Quran, course enrollments for Interactive).
     *
     * @param ParentProfile $parent The parent profile
     * @param QuranSession|AcademicSession|InteractiveCourseSession $session The session to verify
     * @return void
     * @throws AuthorizationException If session doesn't belong to a child
     */
    public function verifySessionBelongsToChild(
        ParentProfile $parent,
        QuranSession|AcademicSession|InteractiveCourseSession $session
    ): void {
        $childUserIds = $this->getChildUserIds($parent);

        // Check individual sessions (student_id is set)
        if ($session->student_id && in_array($session->student_id, $childUserIds)) {
            return; // Access granted
        }

        // Check Quran group circle sessions
        if ($session instanceof QuranSession && $session->circle_id) {
            $circleStudentIds = $session->circle
                ->students()
                ->pluck('quran_circle_students.student_id')
                ->toArray();

            if (!empty(array_intersect($childUserIds, $circleStudentIds))) {
                return; // Access granted
            }
        }

        // Check Interactive Course sessions
        if ($session instanceof InteractiveCourseSession && $session->course_id) {
            $enrolledStudentIds = $session->course
                ->enrollments()
                ->pluck('student_id')
                ->toArray();

            if (!empty(array_intersect($childUserIds, $enrolledStudentIds))) {
                return; // Access granted
            }
        }

        // No access granted
        throw new AuthorizationException('لا يمكنك الوصول إلى هذه الجلسة');
    }

    /**
     * Get all children with their active subscriptions eager loaded
     *
     * Loads all subscription types (Quran, Academic, Course) with their
     * related data for efficient parent dashboard rendering.
     *
     * @param ParentProfile $parent The parent profile
     * @return Collection Collection of StudentProfile models with subscriptions
     */
    public function getChildrenWithSubscriptions(ParentProfile $parent): Collection
    {
        return $parent->students()
            ->with([
                'user',
                'gradeLevel',
            ])
            ->get()
            ->each(function (StudentProfile $child) use ($parent) {
                // Load Quran subscriptions
                $child->quranSubscriptions = QuranSubscription::where('student_id', $child->user_id)
                    ->where('academy_id', $parent->academy_id)
                    ->where('status', \App\Enums\SubscriptionStatus::ACTIVE->value)
                    ->with(['quranTeacher.user', 'package', 'individualCircle'])
                    ->get();

                // Load Academic subscriptions
                $child->academicSubscriptions = AcademicSubscription::where('student_id', $child->user_id)
                    ->where('academy_id', $parent->academy_id)
                    ->where('status', \App\Enums\SubscriptionStatus::ACTIVE->value)
                    ->with(['academicTeacher.user', 'subject', 'academicPackage'])
                    ->get();

                // Load Course enrollments
                $child->courseEnrollments = CourseSubscription::where('student_id', $child->user_id)
                    ->where('academy_id', $parent->academy_id)
                    ->where('status', \App\Enums\SubscriptionStatus::ACTIVE->value)
                    ->with(['course.assignedTeacher.user'])
                    ->get();
            });
    }

    /**
     * Verify parent has access to a child and return the child's User ID
     *
     * Helper method that combines verification and ID retrieval.
     *
     * @param ParentProfile $parent The parent profile
     * @param int|string $childId The child's StudentProfile ID
     * @return int The child's User ID
     * @throws AuthorizationException If child doesn't belong to parent
     */
    public function getVerifiedChildUserId(ParentProfile $parent, int|string $childId): int
    {
        $child = $this->verifyChildBelongsToParent($parent, $childId);
        return $child->user_id;
    }

    /**
     * Check if parent has at least one child
     *
     * @param ParentProfile $parent The parent profile
     * @return bool True if parent has children
     */
    public function hasChildren(ParentProfile $parent): bool
    {
        return $parent->students()->exists();
    }

    /**
     * Get count of parent's children
     *
     * @param ParentProfile $parent The parent profile
     * @return int Number of children
     */
    public function getChildrenCount(ParentProfile $parent): int
    {
        return $parent->students()->count();
    }
}
