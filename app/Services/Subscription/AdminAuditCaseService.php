<?php

namespace App\Services\Subscription;

use App\Enums\BillingCycle;
use App\Models\AcademicPackage;
use App\Models\AcademicSubscription;
use App\Models\Payment;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\SubscriptionAdminAuditDecision;
use App\Models\SubscriptionCycle;
use Illuminate\Support\Collection;

/**
 * Builds the LIVE case list for the admin audit page.
 *
 * Each call recomputes from current data — so cases disappear once an
 * underlying fix is applied. Persisted decisions are joined back in so the
 * page shows the admin's prior verdict (if any) alongside the live data.
 *
 * Categories surfaced today (post-2026-05-16 cleanup):
 *   - inv_d2_drift_payment_mismatch — cycle has no snapshot, price matches a
 *     unique pkg but completed payments don't equal cycle.final_price
 *   - inv_d2_drift_ambiguous — no snapshot, no unique pkg match
 *   - inv_d2_free_not_override — final_price=0 with live pkg > 0
 *   - inv_d2_orphan_package — sub.package_id set, package missing
 *   - paused_no_audit_corrupt — status=paused with no reason or paused_at
 *
 * Each case carries: subject summary, the data the admin needs to decide,
 * a stable `case_key`, recommended option list, and the prior decision if any.
 */
class AdminAuditCaseService
{
    /**
     * @param  bool  $includeApplied  If false (default), cases whose admin
     *                                decision has been stamped applied_at
     *                                are filtered out — they're done.
     * @return array<string, array<int, array<string, mixed>>> keyed by case_type
     */
    public function buildAllCases(bool $includeApplied = false): array
    {
        $cases = [
            'inv_d2_drift_payment_mismatch' => $this->buildDriftPaymentMismatch(),
            'inv_d2_drift_ambiguous' => $this->buildDriftAmbiguous(),
            'inv_d2_free_not_override' => $this->buildFreeNotOverride(),
            'inv_d2_orphan_package' => $this->buildOrphanPackage(),
            'paused_no_audit_corrupt' => $this->buildPausedCorrupt(),
        ];

        $allKeys = collect($cases)->flatten(1)->pluck('case_key')->all();
        $decisions = SubscriptionAdminAuditDecision::query()
            ->whereIn('case_key', $allKeys)
            ->get()
            ->keyBy('case_key');

        foreach ($cases as &$bucket) {
            $next = [];
            foreach ($bucket as $case) {
                $decision = $decisions->get($case['case_key']);
                $case['decision'] = $decision;
                if (! $includeApplied && $decision?->applied_at !== null) {
                    continue;
                }
                $next[] = $case;
            }
            $bucket = $next;
        }
        unset($bucket);
        return $cases;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildDriftPaymentMismatch(): array
    {
        return $this->buildDriftCycles(false);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildDriftAmbiguous(): array
    {
        return $this->buildDriftCycles(true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildDriftCycles(bool $ambiguousMode): array
    {
        $cases = [];
        // withoutGlobalScopes() lets super-admin / cross-tenant runs surface
        // every academy's cases. SoftDeletes still excluded via the explicit
        // whereNull below.
        $cycles = SubscriptionCycle::query()
            ->withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('pricing_source', 'package')
            ->get()
            ->filter(fn ($c) => ! (is_array($c->package_snapshot) && ! empty($c->package_snapshot)));

        foreach ($cycles as $cycle) {
            $bc = BillingCycle::tryFrom((string) $cycle->billing_cycle);
            if ($bc === null) {
                continue;
            }
            $sub = $this->resolveSub($cycle);
            if ($sub === null || $sub->package === null) {
                continue;
            }

            $liveBase = PricingResolver::resolvePriceFromPackage($sub->package, $bc);
            $finalPrice = (float) $cycle->final_price;
            $discount = (float) $cycle->discount_amount;
            $expectedFromLive = (float) $liveBase - $discount;

            if (abs($finalPrice - $expectedFromLive) < 0.01) {
                continue; // already INV-D2 clean
            }
            if ($finalPrice === 0.0 && $liveBase > 0.0) {
                continue; // free_not_override category
            }

            $candidatesByPriceAndSessions = $this->candidatePackages($sub->academy_id, $cycle->subscribable_type, $bc, $finalPrice + $discount, (int) $cycle->total_sessions, true);
            $isAmbiguous = count($candidatesByPriceAndSessions) !== 1;

            // Filter to the requested mode (mismatch vs ambiguous)
            if ($ambiguousMode && ! $isAmbiguous) {
                continue;
            }
            if (! $ambiguousMode && $isAmbiguous) {
                continue;
            }

            // Skip the "payment-truth gate already passes" subset (those are
            // Bucket D-HIGH eligible; auto-applied separately). Use the
            // COMPLETED-only payment list here — matching cancelled/expired
            // attempts don't satisfy the payment-truth gate.
            $payments = $this->paymentsFor($sub, $cycle->subscribable_type);
            $hasMatchingCompletedPayment = $payments
                ->filter(fn ($p) => ($p->status instanceof \BackedEnum ? $p->status->value : (string) $p->status) === 'completed')
                ->contains(fn ($p) => abs((float) $p->amount - $finalPrice) < 0.01);
            if (! $ambiguousMode && $hasMatchingCompletedPayment && count($candidatesByPriceAndSessions) === 1) {
                continue; // D-HIGH eligible — not for admin queue
            }

            $caseKey = $ambiguousMode
                ? 'inv_d2_drift_ambiguous:cycle:'.$cycle->id
                : 'inv_d2_drift_payment_mismatch:cycle:'.$cycle->id;

            $cases[] = [
                'case_key' => $caseKey,
                'subject_type' => 'subscription_cycle',
                'subject_id' => $cycle->id,
                'student' => $sub->student?->name ?? '?',
                'teacher' => $this->resolveTeacherName($sub),
                'sub_id' => $sub->id,
                'sub_type' => $cycle->subscribable_type,
                'sub_url' => $this->subUrl($sub, $cycle->subscribable_type),
                'cycle_id' => $cycle->id,
                'cycle_state' => $cycle->cycle_state,
                'payment_status' => $cycle->payment_status,
                'final_price' => $finalPrice,
                'discount' => $discount,
                'total_sessions' => (int) $cycle->total_sessions,
                'billing_cycle' => $bc->value,
                'created_at' => optional($cycle->created_at)?->toDateTimeString(),
                'current_pkg' => $sub->package ? [
                    'id' => $sub->package->id,
                    'name' => $sub->package->name,
                    'sessions' => $sub->package->sessions_per_month,
                    'duration' => $sub->package->session_duration_minutes,
                    'price' => $sub->package->{$bc->value.'_price'},
                    'sale_price' => $sub->package->{'sale_'.$bc->value.'_price'},
                ] : null,
                'matching_pkgs' => array_map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sessions' => $p->sessions_per_month,
                    'duration' => $p->session_duration_minutes,
                ], $candidatesByPriceAndSessions),
                'payments' => $payments->map(fn ($p) => [
                    'id' => $p->id,
                    'amount' => (float) $p->amount,
                    'status' => $p->status instanceof \BackedEnum ? $p->status->value : (string) $p->status,
                    'gateway' => $p->payment_gateway,
                    'created_at' => optional($p->created_at)?->toDateTimeString(),
                ])->all(),
                'options' => $ambiguousMode
                    ? $this->ambiguousOptions(array_map(fn ($p) => $p->id, $candidatesByPriceAndSessions))
                    : $this->paymentMismatchOptions(),
            ];
        }

        return $cases;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildFreeNotOverride(): array
    {
        $cases = [];
        $cycles = SubscriptionCycle::query()
            ->withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('pricing_source', 'package')
            ->where('final_price', 0)
            ->get();

        foreach ($cycles as $cycle) {
            $bc = BillingCycle::tryFrom((string) $cycle->billing_cycle);
            if ($bc === null) {
                continue;
            }
            $sub = $this->resolveSub($cycle);
            if ($sub === null || $sub->package === null) {
                continue;
            }
            $liveBase = PricingResolver::resolvePriceFromPackage($sub->package, $bc);
            if ($liveBase <= 0.0) {
                continue; // pkg is free too; not a violation
            }
            $payments = $this->paymentsFor($sub, $cycle->subscribable_type);

            $cases[] = [
                'case_key' => 'inv_d2_free_not_override:cycle:'.$cycle->id,
                'subject_type' => 'subscription_cycle',
                'subject_id' => $cycle->id,
                'student' => $sub->student?->name ?? '?',
                'teacher' => $this->resolveTeacherName($sub),
                'sub_id' => $sub->id,
                'sub_url' => $this->subUrl($sub, $cycle->subscribable_type),
                'cycle_id' => $cycle->id,
                'final_price' => 0,
                'live_pkg_price' => (float) $liveBase,
                'is_sponsored' => (bool) ($sub->is_sponsored ?? false),
                'sponsorship_reason' => $sub->sponsorship_reason,
                'is_trial' => (bool) ($sub->is_trial_active ?? false),
                'payments' => $payments->map(fn ($p) => [
                    'amount' => (float) $p->amount,
                    'status' => $p->status instanceof \BackedEnum ? $p->status->value : (string) $p->status,
                ])->all(),
                'options' => [
                    'flip_to_sponsorship' => 'Flip pricing_source → manual_override with reason=sponsorship',
                    'flip_to_trial' => 'Flip pricing_source → manual_override with reason=trial_gift',
                    'flip_to_scholarship' => 'Flip pricing_source → manual_override with reason=scholarship',
                    'correct_to_paid' => 'Cycle should have final_price > 0 — correct the price',
                    'other' => 'Other (specify in note)',
                ],
            ];
        }
        return $cases;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildOrphanPackage(): array
    {
        $cases = [];
        $cycles = SubscriptionCycle::query()
            ->withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('pricing_source', 'package')
            ->whereNull('package_id')
            ->get();

        foreach ($cycles as $cycle) {
            $sub = $this->resolveSub($cycle);
            if ($sub === null) {
                continue;
            }
            $hasLivePkg = $sub->package !== null;
            if ($hasLivePkg) {
                continue; // not orphan
            }
            $payments = $this->paymentsFor($sub, $cycle->subscribable_type);
            $bc = BillingCycle::tryFrom((string) $cycle->billing_cycle);
            $candidates = $bc ? $this->candidatePackages($sub->academy_id, $cycle->subscribable_type, $bc, (float) $cycle->final_price + (float) $cycle->discount_amount, (int) $cycle->total_sessions, false) : [];

            $cases[] = [
                'case_key' => 'inv_d2_orphan_package:cycle:'.$cycle->id,
                'subject_type' => 'subscription_cycle',
                'subject_id' => $cycle->id,
                'student' => $sub->student?->name ?? '?',
                'teacher' => $this->resolveTeacherName($sub),
                'sub_id' => $sub->id,
                'sub_url' => $this->subUrl($sub, $cycle->subscribable_type),
                'cycle_id' => $cycle->id,
                'cycle_state' => $cycle->cycle_state,
                'final_price' => (float) $cycle->final_price,
                'total_sessions' => (int) $cycle->total_sessions,
                'billing_cycle' => $cycle->billing_cycle,
                'sub_package_id_field' => $sub->package_id ?? null,
                'matching_pkgs' => array_map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sessions' => $p->sessions_per_month,
                    'duration' => $p->session_duration_minutes,
                ], $candidates),
                'payments' => $payments->map(fn ($p) => [
                    'amount' => (float) $p->amount,
                    'status' => $p->status instanceof \BackedEnum ? $p->status->value : (string) $p->status,
                ])->all(),
                'options' => array_merge(
                    array_combine(
                        array_map(fn ($p) => 'link_pkg_'.$p->id, $candidates),
                        array_map(fn ($p) => 'Link to pkg #'.$p->id.' ('.$p->sessions_per_month.'x'.$p->session_duration_minutes.')', $candidates),
                    ),
                    [
                        'accept_terminal' => 'Accept as terminal/historical (no pkg link needed)',
                        'other' => 'Other (specify in note)',
                    ],
                ),
            ];
        }
        return $cases;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildPausedCorrupt(): array
    {
        $cases = [];
        $paused = QuranSubscription::query()
            ->withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('status', 'paused')
            ->with(['package', 'student', 'quranTeacher'])
            ->get();

        foreach ($paused as $sub) {
            $reason = $sub->pause_reason;
            $pausedAt = $sub->paused_at;
            $isCorrupt = false;
            $bucket = null;
            if ($pausedAt === null && $reason === null) {
                $isCorrupt = true;
                $bucket = 'no_date_no_reason';
            } elseif ($reason === '1') {
                $isCorrupt = true;
                $bucket = 'corrupt_reason';
            } elseif ($pausedAt !== null && (is_null($reason) || $reason === '')) {
                $isCorrupt = true;
                $bucket = 'null_reason_with_date';
            }
            if (! $isCorrupt) {
                continue;
            }
            $payments = $this->paymentsFor($sub, 'quran_subscription');

            $cases[] = [
                'case_key' => 'paused_no_audit_corrupt:sub:'.$sub->id,
                'subject_type' => 'quran_subscription',
                'subject_id' => $sub->id,
                'student' => $sub->student?->name ?? '?',
                'teacher' => $sub->quranTeacher?->name ?? '?',
                'sub_id' => $sub->id,
                'sub_url' => $this->subUrl($sub, 'quran_subscription'),
                'paused_at' => optional($pausedAt)?->toDateTimeString() ?? 'NULL',
                'pause_reason' => $reason ?? 'NULL',
                'pause_bucket' => $bucket,
                'pkg' => $sub->package ? '#'.$sub->package_id.' '.$sub->package->name : 'no package',
                'completed_payments_sum' => $payments->where('status', 'completed')->sum('amount'),
                'options' => [
                    'confirm_pause_end_of_period' => 'Confirm pause — was end-of-period (set reason)',
                    'confirm_pause_admin' => 'Confirm pause — was admin manual action (set reason)',
                    'unpause' => 'Unpause — sub should be ACTIVE',
                    'cancel_sub' => 'Sub should be cancelled instead of paused',
                    'other' => 'Other (specify in note)',
                ],
            ];
        }
        return $cases;
    }

    /**
     * Find academy packages matching price (regular or sale) for the cycle's
     * billing cycle, optionally narrowed by sessions_per_month.
     *
     * @return array<int, object>
     */
    private function candidatePackages(int $academyId, string $morph, BillingCycle $bc, float $target, int $totalSessions, bool $narrowBySessions): array
    {
        $pkgs = $morph === 'quran_subscription'
            ? QuranPackage::query()->withoutGlobalScopes()->where('academy_id', $academyId)->get()
            : AcademicPackage::query()->withoutGlobalScopes()->where('academy_id', $academyId)->get();

        $matches = [];
        foreach ($pkgs as $p) {
            $reg = (float) ($p->{$bc->value.'_price'} ?? 0);
            $sale = $p->{'sale_'.$bc->value.'_price'} !== null ? (float) $p->{'sale_'.$bc->value.'_price'} : null;
            if (abs($target - $reg) < 0.01 || ($sale !== null && abs($target - $sale) < 0.01)) {
                $matches[$p->id] = $p;
            }
        }
        if ($narrowBySessions && $totalSessions > 0) {
            $matches = array_filter($matches, fn ($p) => (int) $p->sessions_per_month === $totalSessions);
        }
        return array_values($matches);
    }

    private function resolveSub(SubscriptionCycle $cycle): ?\App\Models\BaseSubscription
    {
        return match ($cycle->subscribable_type) {
            'quran_subscription' => QuranSubscription::withoutGlobalScopes()->with(['package', 'student', 'quranTeacher'])->find($cycle->subscribable_id),
            'academic_subscription' => AcademicSubscription::withoutGlobalScopes()->with(['package', 'student'])->find($cycle->subscribable_id),
            default => null,
        };
    }

    private function resolveTeacherName(\App\Models\BaseSubscription $sub): string
    {
        if ($sub instanceof QuranSubscription) {
            return $sub->quranTeacher?->name ?? '?';
        }
        return '?';
    }

    private function subUrl(\App\Models\BaseSubscription $sub, string $morph): string
    {
        $type = $morph === 'quran_subscription' ? 'quran' : 'academic';
        try {
            return route('manage.subscriptions.show', ['type' => $type, 'subscription' => $sub->id]);
        } catch (\Throwable) {
            return '#';
        }
    }

    private function paymentsFor(\App\Models\BaseSubscription $sub, string $morph): Collection
    {
        return Payment::query()
            ->where(function ($q) use ($sub, $morph) {
                $q->where('subscription_id', $sub->id)
                    ->orWhere(function ($qq) use ($morph, $sub) {
                        $qq->whereIn('payable_type', [$morph, 'App\\Models\\'.($morph === 'quran_subscription' ? 'QuranSubscription' : 'AcademicSubscription')])
                            ->where('payable_id', $sub->id);
                    });
            })
            ->orderBy('id')
            ->get(['id', 'amount', 'status', 'payment_gateway', 'created_at']);
    }

    private function paymentMismatchOptions(): array
    {
        return [
            'cycle_final_correct' => 'cycle.final_price is correct (payments are partial / wrong-amount)',
            'payments_correct' => 'Payments correct — cycle.final_price should match payment',
            'split_payment_known' => 'Partial / split payment — already known, leave as is',
            'manual_override' => 'Flip to pricing_source=manual_override with reason',
            'other' => 'Other (specify in note)',
        ];
    }

    /**
     * @param  array<int, int>  $pkgIds
     */
    private function ambiguousOptions(array $pkgIds): array
    {
        $opts = [];
        foreach ($pkgIds as $id) {
            $opts['use_pkg_'.$id] = 'Use pkg #'.$id;
        }
        $opts['manual_override'] = 'Manual override (not from package list)';
        $opts['other'] = 'Other (specify in note)';
        return $opts;
    }
}
