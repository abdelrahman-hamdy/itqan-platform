<?php

namespace App\Services;

use App\Constants\ErrorMessages;
use App\Models\ParentProfile;
use App\Models\Payment;
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
}
