<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

/**
 * Payment Policy
 *
 * Authorization policy for payment access.
 */
class PaymentPolicy
{
    /**
     * Determine whether the user can view any payments.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['superadmin', 'admin', 'supervisor', 'student']);
    }

    /**
     * Determine whether the user can view the payment.
     */
    public function view(User $user, Payment $payment): bool
    {
        // Admins can view any payment in their academy
        if ($user->hasRole(['superadmin', 'admin', 'supervisor'])) {
            return $this->sameAcademy($user, $payment);
        }

        // Users can view their own payments
        if ($payment->user_id === $user->id) {
            return true;
        }

        // Parents can view their children's payments
        if ($user->isParent()) {
            return $this->isParentOfPaymentOwner($user, $payment);
        }

        return false;
    }

    /**
     * Determine whether the user can create payments.
     */
    public function create(User $user): bool
    {
        // Admins can create payments manually
        if ($user->hasRole(['superadmin', 'admin'])) {
            return true;
        }

        // Students can create payments (for subscriptions)
        if ($user->hasRole('student')) {
            return true;
        }

        // Parents can create payments for their children
        if ($user->isParent()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the payment.
     */
    public function update(User $user, Payment $payment): bool
    {
        // Only admins can update payments
        return $user->hasRole(['superadmin', 'admin']) && $this->sameAcademy($user, $payment);
    }

    /**
     * Determine whether the user can delete the payment.
     */
    public function delete(User $user, Payment $payment): bool
    {
        // Only superadmin can delete payments
        return $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can refund the payment.
     */
    public function refund(User $user, Payment $payment): bool
    {
        // Only admins can refund payments
        if (!$user->hasRole(['superadmin', 'admin'])) {
            return false;
        }

        // Must be in same academy
        if (!$this->sameAcademy($user, $payment)) {
            return false;
        }

        // Payment must be successful and not already refunded
        return $payment->status === 'completed' && !$payment->refunded_at;
    }

    /**
     * Determine whether the user can download the receipt.
     */
    public function downloadReceipt(User $user, Payment $payment): bool
    {
        return $this->view($user, $payment);
    }

    /**
     * Check if user is a parent of the payment owner.
     */
    private function isParentOfPaymentOwner(User $user, Payment $payment): bool
    {
        $parent = $user->parentProfile;
        if (!$parent) {
            return false;
        }

        $paymentUser = $payment->user;
        if (!$paymentUser) {
            return false;
        }

        $studentProfile = $paymentUser->studentProfileUnscoped;
        if (!$studentProfile) {
            return false;
        }

        return $parent->students()
            ->where('student_profiles.id', $studentProfile->id)
            ->exists();
    }

    /**
     * Check if payment belongs to same academy as user.
     */
    private function sameAcademy(User $user, Payment $payment): bool
    {
        $userAcademyId = $user->getCurrentAcademyId();
        if (!$userAcademyId) {
            return false;
        }

        return $payment->academy_id === $userAcademyId;
    }
}
