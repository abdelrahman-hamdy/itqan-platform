<?php

namespace App\Services\Subscription;

use App\Enums\PaymentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\AcademicPackage;
use App\Models\AcademicSubscription;
use App\Models\BaseSubscription;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates admin-created subscriptions for external payments.
 *
 * Creates the full chain in a single transaction:
 * subscription + payment record + circle/lesson + initial progress.
 */
class AdminSubscriptionWizardService
{
    /**
     * Create a full subscription with all related entities.
     *
     * @param  array  $data  Keys: type, student_id, teacher_id, package_id, billing_cycle,
     *                       amount, payment_method, payment_reference, payment_date,
     *                       consumed_sessions, memorization_level, specialization, notes
     */
    public function createFullSubscription(array $data): BaseSubscription
    {
        return DB::transaction(function () use ($data) {
            $type = $data['type'];

            // Group circles may not have a package — use circle fee instead
            $package = ! empty($data['package_id']) ? $this->findPackage($type, $data['package_id']) : null;

            if (! $package && $type !== 'quran_group') {
                throw new \Exception(__('subscriptions.package_not_found'));
            }

            // 1. Create subscription
            $subscription = $this->createSubscription($type, $data, $package);

            // 2. Create payment record (skip if pending/inside platform)
            if (! ($data['create_as_pending'] ?? false)) {
                // Admin-created active subscription: bootstrap first cycle so the
                // cycles relation manager and API payload are populated immediately.
                $subscription->ensureCurrentCycle();

                $payment = $this->createPaymentRecord($subscription, $data);
                PaymentAuditLog::logCreation($payment, auth()->id());
            }

            // 3. Create related entities (circle for Quran individual, lesson for Academic)
            $this->createRelatedEntities($type, $subscription, $data);

            // 4. Set initial progress if specified
            $this->setInitialProgress($subscription, $data);

            Log::info('Admin created full subscription', [
                'subscription_id' => $subscription->id,
                'type' => $type,
                'student_id' => $data['student_id'],
                'admin_id' => auth()->id(),
            ]);

            return $subscription;
        });
    }

    private function createSubscription(string $type, array $data, $package): BaseSubscription
    {
        $billingCycle = \App\Enums\BillingCycle::from($data['billing_cycle']);
        $startsAt = $data['starts_at'] ?? now();
        $endsAt = $billingCycle->calculateEndDate($startsAt);
        $isSponsored = $data['is_sponsored'] ?? false;
        $amount = $isSponsored ? 0 : ($data['amount'] ?? ($package ? $this->getPriceFromPackage($package, $billingCycle) : 0));

        $baseData = [
            'academy_id' => $data['academy_id'] ?? auth()->user()->academy_id,
            'student_id' => $data['student_id'],
            'status' => ($data['create_as_pending'] ?? false) ? SessionSubscriptionStatus::PENDING : SessionSubscriptionStatus::ACTIVE,
            'payment_status' => ($data['create_as_pending'] ?? false) ? SubscriptionPaymentStatus::PENDING : SubscriptionPaymentStatus::PAID,
            'billing_cycle' => $billingCycle,
            'starts_at' => ($data['create_as_pending'] ?? false) ? null : $startsAt,
            'ends_at' => ($data['create_as_pending'] ?? false) ? null : $endsAt,
            'next_billing_date' => ($data['create_as_pending'] ?? false) ? null : $endsAt,
            'last_payment_date' => ($data['create_as_pending'] ?? false) ? null : now(),
            'auto_renew' => $billingCycle->supportsAutoRenewal(),
            'session_duration_minutes' => $package?->session_duration_minutes ?? 60,
            'total_sessions' => ($package?->sessions_per_month ?? 8) * max(1, $billingCycle->months()),
            'sessions_remaining' => ($package?->sessions_per_month ?? 8) * max(1, $billingCycle->months()),
            'sessions_used' => 0,
            'total_sessions_completed' => 0,
            'total_sessions_missed' => 0,
            'package_price_monthly' => (float) ($package?->monthly_price ?? $amount),
            'package_price_quarterly' => (float) ($package?->quarterly_price ?? ($package?->monthly_price ?? $amount) * 3),
            'package_price_yearly' => (float) ($package?->yearly_price ?? ($package?->monthly_price ?? $amount) * 12),
            'total_price' => $amount,
            'final_price' => $amount,
            'discount_amount' => $data['discount'] ?? 0,
            'is_recurring_discount' => $data['is_recurring_discount'] ?? false,
            'currency' => $package?->currency ?? 'SAR',
            'package_name_ar' => $package?->name,
            'package_name_en' => $package?->name,
            'notes' => $data['notes'] ?? null,
            'progress_percentage' => 0,
            'purchase_source' => 'admin',
        ];

        // Common optional fields
        $baseData['learning_goals'] = $data['learning_goals'] ?? null;

        if ($type === 'quran_individual' || $type === 'quran_group') {
            $baseData['quran_teacher_id'] = $data['teacher_id'];
            $baseData['package_id'] = $data['package_id'] ?? null;
            $baseData['is_sponsored'] = $data['is_sponsored'] ?? false;
            $baseData['subscription_type'] = $type === 'quran_group' ? 'group' : 'individual';
            $baseData['memorization_level'] = $data['memorization_level'] ?? 'beginner';
            $baseData['subscription_code'] = QuranSubscription::generateSubscriptionCode($baseData['academy_id']);

            // Link group circle if provided
            if ($type === 'quran_group' && ! empty($data['quran_circle_id'])) {
                $baseData['education_unit_type'] = 'quran_circle';
                $baseData['education_unit_id'] = $data['quran_circle_id'];
            }

            return QuranSubscription::create($baseData);
        }

        // Academic
        $baseData['teacher_id'] = $data['teacher_id'];
        $baseData['academic_package_id'] = $data['package_id'];
        $baseData['subscription_type'] = 'private';
        $baseData['subject_id'] = $data['subject_id'] ?? null;
        $baseData['grade_level_id'] = $data['grade_level_id'] ?? null;
        $baseData['sessions_per_week'] = $data['sessions_per_week'] ?? max(1, intval($package->sessions_per_month / 4.33));
        $baseData['subscription_code'] = AcademicSubscription::generateSubscriptionCode($baseData['academy_id']);

        return AcademicSubscription::create($baseData);
    }

    private function createPaymentRecord(BaseSubscription $subscription, array $data): Payment
    {
        return Payment::createPayment([
            'academy_id' => $subscription->academy_id,
            'user_id' => $subscription->student_id,
            'payable_type' => $subscription::class,
            'payable_id' => $subscription->id,
            'payment_method' => $data['payment_method'] ?? 'cash',
            'payment_gateway' => 'manual',
            'payment_type' => 'subscription',
            'amount' => $subscription->final_price,
            'currency' => $subscription->currency,
            'status' => PaymentStatus::COMPLETED,
            'payment_status' => 'paid',
            'payment_date' => $data['payment_date'] ?? now(),
            'paid_at' => $data['payment_date'] ?? now(),
            'confirmed_at' => now(),
            'receipt_number' => $data['payment_reference'] ?? null,
            'notes' => $data['payment_notes'] ?? __('subscriptions.manual_payment_created_by_admin'),
            'created_by' => auth()->id(),
        ]);
    }

    private function createRelatedEntities(string $type, BaseSubscription $subscription, array $data): void
    {
        if ($type === 'quran_individual' && $subscription instanceof QuranSubscription) {
            $circle = QuranIndividualCircle::create([
                'academy_id' => $subscription->academy_id,
                'quran_teacher_id' => $subscription->quran_teacher_id,
                'student_id' => $subscription->student_id,
                'subscription_id' => $subscription->id,
                'specialization' => $data['specialization'] ?? 'memorization',
                'memorization_level' => $data['memorization_level'] ?? 'beginner',
                'total_sessions' => $subscription->total_sessions,
                'sessions_remaining' => $subscription->sessions_remaining,
                'default_duration_minutes' => $subscription->session_duration_minutes ?? 60,
                'is_active' => true,
            ]);

            $subscription->linkToEducationUnit($circle);
        } elseif ($type === 'quran_group' && $subscription instanceof QuranSubscription && ! empty($data['quran_circle_id'])) {
            $circle = QuranCircle::find($data['quran_circle_id']);
            if ($circle) {
                $circle->enrollStudent(User::findOrFail($subscription->student_id), [
                    'subscription_id' => $subscription->id,
                ]);
            }
        } elseif ($type === 'academic' && $subscription instanceof AcademicSubscription) {
            if (method_exists($subscription, 'createLessonAndSessions')) {
                $subscription->createLessonAndSessions();
            }
        }
    }

    private function setInitialProgress(BaseSubscription $subscription, array $data): void
    {
        $consumed = (int) ($data['consumed_sessions'] ?? 0);
        if ($consumed > 0 && $consumed < $subscription->total_sessions) {
            $remaining = $subscription->total_sessions - $consumed;
            $subscription->update([
                'sessions_used' => $consumed,
                'sessions_remaining' => $remaining,
                'total_sessions_completed' => $consumed,
                'progress_percentage' => round(($consumed / $subscription->total_sessions) * 100, 2),
            ]);

            // Sync circle's sessions_remaining so calendar scheduling sees the correct count
            if ($subscription instanceof QuranSubscription) {
                $circle = $subscription->individualCircle;
                $circle?->update(['sessions_remaining' => $remaining]);
            }
        }
    }

    private function findPackage(string $type, int $packageId): QuranPackage|AcademicPackage|null
    {
        return match ($type) {
            'quran_individual', 'quran_group' => QuranPackage::find($packageId),
            'academic' => AcademicPackage::find($packageId),
            default => null,
        };
    }

    private function getPriceFromPackage($package, \App\Enums\BillingCycle $billingCycle): float
    {
        return PricingResolver::resolvePriceFromPackage($package, $billingCycle, useSalePrices: false);
    }
}
