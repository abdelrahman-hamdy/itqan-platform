<?php

namespace App\Services\Subscription;

use App\Enums\BillingCycle;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\AcademicPackage;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\BaseSubscription;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles subscription renewal and resubscription logic.
 *
 * Smart date logic:
 * - Sessions exhausted (remaining=0): new subscription starts from now(), fresh cycle
 * - Early renewal (remaining>0): new subscription starts from old ends_at, remaining sessions carry over
 *
 * Modes:
 * - renew: Same or different package, active/expiring subscription
 * - resubscribe: Dormant (cancelled/expired) subscription, may need teacher change
 */
class SubscriptionRenewalService
{
    /**
     * Renew an existing subscription, optionally changing package or billing cycle.
     *
     * @param  array  $options  Keys: package_id, billing_cycle, teacher_id, activate_immediately
     */
    public function renew(BaseSubscription $old, array $options = []): BaseSubscription
    {
        if (! $this->canRenew($old)) {
            throw new Exception(__('subscriptions.cannot_renew'));
        }

        return DB::transaction(function () use ($old, $options) {
            $old = $old::lockForUpdate()->find($old->id);

            // Prevent duplicate renewals from concurrent requests
            if ($old->hasPendingRenewal()) {
                throw new Exception(__('subscriptions.renewal_already_pending'));
            }

            $newData = $this->buildRenewalData($old, $options);

            $modelClass = $old::class;
            $new = $modelClass::create($newData);

            // Disable auto-renew on old subscription to prevent double billing
            $old->update(['auto_renew' => false]);

            Log::info('Subscription renewed', [
                'old_id' => $old->id,
                'new_id' => $new->id,
                'type' => $old->getSubscriptionType(),
                'package_changed' => isset($options['package_id']),
                'billing_cycle_changed' => isset($options['billing_cycle']),
            ]);

            return $new;
        });
    }

    /**
     * Resubscribe from a dormant (cancelled/expired) subscription.
     * Checks teacher availability and uses current package pricing.
     */
    public function resubscribe(BaseSubscription $old, array $options = []): BaseSubscription
    {
        if (! $this->canResubscribe($old)) {
            throw new Exception(__('subscriptions.cannot_resubscribe'));
        }

        // Check teacher availability for session-based subscriptions
        if ($old instanceof QuranSubscription || $old instanceof AcademicSubscription) {
            $this->validateTeacherAvailability($old, $options);
        }

        return DB::transaction(function () use ($old, $options) {
            $old = $old::lockForUpdate()->find($old->id);

            // Force fresh package data for resubscriptions
            $options['use_current_pricing'] = true;

            $newData = $this->buildRenewalData($old, $options);

            // Copy learning context from old subscription
            $newData = $this->copyLearningContext($old, $newData);

            $modelClass = $old::class;
            $new = $modelClass::create($newData);

            $old->update(['auto_renew' => false]);

            Log::info('Subscription resubscribed', [
                'old_id' => $old->id,
                'new_id' => $new->id,
                'type' => $old->getSubscriptionType(),
            ]);

            return $new;
        });
    }

    /**
     * Check if a subscription can be renewed.
     */
    public function canRenew(BaseSubscription $subscription): bool
    {
        return $subscription->status->canRenew()
            || $subscription->is_sessions_exhausted;
    }

    /**
     * Check if a subscription can be resubscribed (dormant renewal).
     */
    public function canResubscribe(BaseSubscription $subscription): bool
    {
        return in_array($subscription->status, [
            SessionSubscriptionStatus::CANCELLED,
            SessionSubscriptionStatus::EXPIRED,
        ]);
    }

    /**
     * Get available renewal options (packages, billing cycles, pricing).
     */
    public function getRenewalOptions(BaseSubscription $subscription): array
    {
        $type = $subscription->getSubscriptionType();
        $academyId = $subscription->academy_id;

        $packages = match ($type) {
            'quran' => QuranPackage::where('academy_id', $academyId)->active()->get(),
            'academic' => AcademicPackage::where('academy_id', $academyId)->active()->get(),
            default => collect(),
        };

        $billingCycles = collect(BillingCycle::cases())
            ->filter(fn (BillingCycle $cycle) => $cycle->supportsAutoRenewal())
            ->map(fn (BillingCycle $cycle) => [
                'value' => $cycle->value,
                'label' => $cycle->label(),
                'months' => $cycle->months(),
            ])
            ->values()
            ->all();

        return [
            'packages' => $packages->map(fn ($pkg) => [
                'id' => $pkg->id,
                'name' => $pkg->name,
                'sessions_per_month' => $pkg->sessions_per_month,
                'session_duration_minutes' => $pkg->session_duration_minutes,
                'monthly_price' => (float) $pkg->monthly_price,
                'quarterly_price' => (float) ($pkg->quarterly_price ?? $pkg->monthly_price * 3),
                'yearly_price' => (float) ($pkg->yearly_price ?? $pkg->monthly_price * 12),
                'currency' => $pkg->currency ?? 'SAR',
            ])->all(),
            'billing_cycles' => $billingCycles,
            'current' => [
                'package_id' => $this->getPackageId($subscription),
                'billing_cycle' => $subscription->billing_cycle->value,
                'sessions_remaining' => method_exists($subscription, 'getSessionsRemaining')
                    ? $subscription->getSessionsRemaining()
                    : 0,
            ],
        ];
    }

    /**
     * Build the data array for the new subscription.
     */
    private function buildRenewalData(BaseSubscription $old, array $options): array
    {
        $packageId = $options['package_id'] ?? $this->getPackageId($old);
        $billingCycle = isset($options['billing_cycle'])
            ? BillingCycle::from($options['billing_cycle'])
            : $old->billing_cycle;

        // Determine pricing: use new package or old snapshot
        $pricingData = $this->resolvePricing($old, $packageId, $billingCycle, $options);

        // Determine start date and session carryover
        $sessionsRemaining = method_exists($old, 'getSessionsRemaining') ? $old->getSessionsRemaining() : 0;
        $sessionsExhausted = $old->is_sessions_exhausted;

        if ($sessionsExhausted || $sessionsRemaining <= 0) {
            // All sessions used: start fresh from now
            $startsAt = now();
        } else {
            // Early renewal: start from old end date to preserve remaining time
            $startsAt = $old->ends_at && $old->ends_at->isFuture() ? $old->ends_at : now();
        }

        $endsAt = $billingCycle->calculateEndDate($startsAt);

        // Calculate total sessions for new period + carryover
        $newSessionsPerMonth = $pricingData['sessions_per_month'] ?? $old->sessions_per_month ?? 8;
        $totalNewSessions = $newSessionsPerMonth * max(1, $billingCycle->months());
        $carryoverSessions = max(0, $sessionsRemaining);
        $totalSessions = $totalNewSessions + $carryoverSessions;

        // Determine activation status
        $activateImmediately = $options['activate_immediately'] ?? false;
        $status = $activateImmediately
            ? SessionSubscriptionStatus::ACTIVE
            : SessionSubscriptionStatus::PENDING;
        $paymentStatus = $activateImmediately
            ? SubscriptionPaymentStatus::PAID
            : SubscriptionPaymentStatus::PENDING;

        $data = [
            'academy_id' => $old->academy_id,
            'student_id' => $old->student_id,
            'previous_subscription_id' => $old->id,
            'subscription_code' => $old::generateSubscriptionCode($old->academy_id),
            'status' => $status,
            'payment_status' => $paymentStatus,
            'billing_cycle' => $billingCycle,
            'starts_at' => $activateImmediately ? $startsAt : null,
            'ends_at' => $activateImmediately ? $endsAt : null,
            'next_billing_date' => $activateImmediately ? $endsAt : null,
            'last_payment_date' => $activateImmediately ? now() : null,
            'auto_renew' => $billingCycle->supportsAutoRenewal(),
            'total_sessions' => $totalSessions,
            'sessions_remaining' => $totalSessions,
            'sessions_used' => 0,
            'total_sessions_completed' => 0,
            'total_sessions_missed' => 0,
            'sessions_per_month' => $newSessionsPerMonth,
            'session_duration_minutes' => $pricingData['session_duration_minutes'] ?? $old->session_duration_minutes,
            'progress_percentage' => 0,
            'currency' => $old->currency ?? 'SAR',
        ];

        // Merge pricing
        $data = array_merge($data, $pricingData['price_fields']);

        // Add type-specific fields
        $data = $this->addTypeSpecificFields($old, $data, $options);

        return $data;
    }

    /**
     * Resolve pricing from package (current or snapshotted).
     */
    private function resolvePricing(BaseSubscription $old, $packageId, BillingCycle $billingCycle, array $options): array
    {
        $useCurrentPricing = $options['use_current_pricing'] ?? ($packageId !== $this->getPackageId($old));

        if ($useCurrentPricing && $packageId) {
            $package = $this->findPackage($old, $packageId);
            if ($package) {
                $finalPrice = $options['amount'] ?? PricingResolver::resolvePriceFromPackage($package, $billingCycle);

                return [
                    'sessions_per_month' => $package->sessions_per_month,
                    'session_duration_minutes' => $package->session_duration_minutes,
                    'price_fields' => [
                        'package_name_ar' => $package->name,
                        'package_name_en' => $package->name,
                        'monthly_price' => (float) $package->monthly_price,
                        'quarterly_price' => (float) ($package->quarterly_price ?? $package->monthly_price * 3),
                        'yearly_price' => (float) ($package->yearly_price ?? $package->monthly_price * 12),
                        'final_price' => $finalPrice,
                        'discount_amount' => 0,
                    ],
                ];
            }
        }

        // Use old subscription's snapshotted pricing
        $finalPrice = $options['amount'] ?? $old->getPriceForBillingCycle();

        return [
            'sessions_per_month' => $old->sessions_per_month,
            'session_duration_minutes' => $old->session_duration_minutes,
            'price_fields' => [
                'package_name_ar' => $old->package_name_ar,
                'package_name_en' => $old->package_name_en,
                'monthly_price' => $old->monthly_price,
                'quarterly_price' => $old->quarterly_price,
                'yearly_price' => $old->yearly_price,
                'final_price' => $finalPrice,
                'discount_amount' => 0,
            ],
        ];
    }

    /**
     * Add type-specific fields (Quran or Academic).
     */
    private function addTypeSpecificFields(BaseSubscription $old, array $data, array $options): array
    {
        if ($old instanceof QuranSubscription) {
            $data['quran_teacher_id'] = $options['teacher_id'] ?? $old->quran_teacher_id;
            $data['package_id'] = $options['package_id'] ?? $old->package_id;
            $data['subscription_type'] = $old->subscription_type;
            $data['memorization_level'] = $old->memorization_level;
            $data['education_unit_type'] = $old->education_unit_type;
            $data['education_unit_id'] = $old->education_unit_id;
        } elseif ($old instanceof AcademicSubscription) {
            $data['teacher_id'] = $options['teacher_id'] ?? $old->teacher_id;
            $data['academic_package_id'] = $options['package_id'] ?? $old->academic_package_id;
            $data['subscription_type'] = $old->subscription_type ?? 'private';
            $data['subject_id'] = $old->subject_id;
            $data['grade_level_id'] = $old->grade_level_id;
            $data['subject_name'] = $old->subject_name;
            $data['grade_level_name'] = $old->grade_level_name;
            $data['sessions_per_week'] = $old->sessions_per_week;
            $data['timezone'] = $old->timezone;
        }

        return $data;
    }

    /**
     * Copy learning context from old subscription to new one (for resubscribe).
     */
    private function copyLearningContext(BaseSubscription $old, array $data): array
    {
        if ($old instanceof QuranSubscription) {
            // memorization_level is already set by addTypeSpecificFields()
            $data['learning_goals'] = $old->learning_goals;
            $data['student_notes'] = $old->student_notes;
        } elseif ($old instanceof AcademicSubscription) {
            $data['learning_goals'] = $old->learning_goals;
            $data['student_notes'] = $old->student_notes;
        }

        return $data;
    }

    /**
     * Validate teacher availability for resubscription.
     */
    private function validateTeacherAvailability(BaseSubscription $old, array $options): void
    {
        $teacherId = $options['teacher_id'] ?? null;

        if ($old instanceof QuranSubscription) {
            $currentTeacherId = $teacherId ?? $old->quran_teacher_id;
            if ($currentTeacherId) {
                $teacherProfile = QuranTeacherProfile::find($currentTeacherId);
                if (! $teacherProfile || ! $teacherProfile->is_active) {
                    if (! $teacherId) {
                        throw new Exception(__('subscriptions.teacher_unavailable_select_new'));
                    }
                }
            }
        } elseif ($old instanceof AcademicSubscription) {
            $currentTeacherId = $teacherId ?? $old->teacher_id;
            if ($currentTeacherId) {
                $teacherProfile = AcademicTeacherProfile::find($currentTeacherId);
                if (! $teacherProfile) {
                    if (! $teacherId) {
                        throw new Exception(__('subscriptions.teacher_unavailable_select_new'));
                    }
                }
            }
        }
    }

    /**
     * Get the package ID from a subscription (type-aware).
     */
    private function getPackageId(BaseSubscription $subscription): ?int
    {
        if ($subscription instanceof QuranSubscription) {
            return $subscription->package_id;
        }
        if ($subscription instanceof AcademicSubscription) {
            return $subscription->academic_package_id;
        }

        return null;
    }

    /**
     * Find a package by subscription type.
     */
    private function findPackage(BaseSubscription $subscription, int $packageId): QuranPackage|AcademicPackage|null
    {
        if ($subscription instanceof QuranSubscription) {
            return QuranPackage::find($packageId);
        }
        if ($subscription instanceof AcademicSubscription) {
            return AcademicPackage::find($packageId);
        }

        return null;
    }
}
