<?php

namespace App\Jobs;

use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\BaseSession;
use App\Models\CourseSubscription;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Phase A.6 — INV-E2 propagation job.
 *
 * When a renewal (or admin package-change) lands a new cycle whose
 * `package.session_duration_minutes` differs from what the future scheduled
 * sessions of that cycle were stamped with at creation time, this job rewrites
 * those future sessions so they match the anchor cycle's package.
 *
 * Invariants enforced:
 *   - INV-E1: future scheduled sessions on `cycle_id = $cycleId` end up with
 *     `duration_minutes == anchorCycle.package.session_duration_minutes`.
 *   - INV-E2: this job is the ONLY writer that performs that rewrite — every
 *     renew/resubscribe/admin-change path dispatches it (wired in A.5, not
 *     here).
 *   - INV-E3: historical (already-happened) sessions are untouched. The
 *     `scheduled_at > now()` filter is the only thing guaranteeing that.
 *
 * Idempotency: re-running on a cycle whose future sessions already match the
 * package is a no-op — every comparison short-circuits before the save.
 *
 * Queue: 'notifications' (per MEMORY.md horizon supervisor list).
 * Caller passes both the cycle id AND the subscribable morph type. We do not
 * trust the morph alone: when the row is missing or already archived we
 * abort with a warning log rather than throw.
 */
class RebuildFutureSessionsForCycle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 90, 240];

    public int $timeout = 120;

    /**
     * @param  int  $cycleId  SubscriptionCycle primary key.
     * @param  string  $cycleType  Morph alias of the subscribable that owns the cycle
     *                             ('quran_subscription' | 'academic_subscription' |
     *                             'course_subscription'). Used to pick the right
     *                             session model + package relation.
     */
    public function __construct(
        public int $cycleId,
        public string $cycleType,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $cycle = SubscriptionCycle::withoutGlobalScopes()->find($this->cycleId);
        if ($cycle === null) {
            Log::warning('RebuildFutureSessionsForCycle: cycle not found', [
                'cycle_id' => $this->cycleId,
                'cycle_type' => $this->cycleType,
            ]);

            return;
        }

        // Course subscriptions are enrollment-based and don't carry
        // subscription_cycle_id on their session model. Nothing to rebuild.
        if ($cycle->subscribable_type === (new CourseSubscription)->getMorphClass()
            || $this->cycleType === (new CourseSubscription)->getMorphClass()) {
            Log::info('RebuildFutureSessionsForCycle: course cycle — skipping (enrollment-based, no per-session counter)', [
                'cycle_id' => $cycle->id,
            ]);

            return;
        }

        $subscription = $this->loadSubscription($cycle);
        if ($subscription === null) {
            Log::warning('RebuildFutureSessionsForCycle: subscribable not found for cycle', [
                'cycle_id' => $cycle->id,
                'subscribable_type' => $cycle->subscribable_type,
                'subscribable_id' => $cycle->subscribable_id,
            ]);

            return;
        }

        $package = $subscription->package()->withoutGlobalScopes()->first();
        if ($package === null) {
            Log::warning('RebuildFutureSessionsForCycle: package not found on subscribable', [
                'cycle_id' => $cycle->id,
                'subscribable_type' => $cycle->subscribable_type,
                'subscribable_id' => $cycle->subscribable_id,
            ]);

            return;
        }

        $newDuration = (int) ($package->session_duration_minutes ?? 0);
        if ($newDuration <= 0) {
            Log::warning('RebuildFutureSessionsForCycle: package has no positive session_duration_minutes — refusing to overwrite sessions', [
                'cycle_id' => $cycle->id,
                'package_id' => $package->id,
                'package_class' => $package::class,
            ]);

            return;
        }

        $sessionClass = $this->resolveSessionClass($cycle->subscribable_type);
        if ($sessionClass === null) {
            Log::warning('RebuildFutureSessionsForCycle: unsupported subscribable_type', [
                'cycle_id' => $cycle->id,
                'subscribable_type' => $cycle->subscribable_type,
            ]);

            return;
        }

        $futureSessions = $sessionClass::query()
            ->withoutGlobalScopes()
            ->where('subscription_cycle_id', $cycle->id)
            ->whereIn('status', $this->preStartStatuses())
            ->where('scheduled_at', '>', now())
            ->get();

        $rebuilt = 0;
        foreach ($futureSessions as $session) {
            /** @var BaseSession $session */
            $oldDuration = (int) ($session->duration_minutes ?? 0);
            if ($oldDuration === $newDuration) {
                continue;
            }

            Log::info('RebuildFutureSessionsForCycle: rewriting session duration', [
                'cycle_id' => $cycle->id,
                'session_id' => $session->id,
                'session_type' => $session::class,
                'old_duration_minutes' => $oldDuration,
                'new_duration_minutes' => $newDuration,
                'package_id' => $package->id,
            ]);

            $session->duration_minutes = $newDuration;
            $session->save();
            $rebuilt++;
        }

        Log::info('RebuildFutureSessionsForCycle: complete', [
            'cycle_id' => $cycle->id,
            'examined' => $futureSessions->count(),
            'rebuilt' => $rebuilt,
            'new_duration_minutes' => $newDuration,
        ]);
    }

    /**
     * Load the subscribable that owns the cycle, scope-free so archived /
     * cancelled rows are still resolvable.
     */
    private function loadSubscription(SubscriptionCycle $cycle): ?object
    {
        $morphMap = [
            (new QuranSubscription)->getMorphClass() => QuranSubscription::class,
            (new AcademicSubscription)->getMorphClass() => AcademicSubscription::class,
        ];

        $class = $morphMap[$cycle->subscribable_type] ?? null;
        if ($class === null) {
            return null;
        }

        return $class::withoutGlobalScopes()->find($cycle->subscribable_id);
    }

    /**
     * Pick the session model that carries `subscription_cycle_id` for this
     * morph. `InteractiveCourseSession` is intentionally excluded — see the
     * 2026-05-04 migration header.
     *
     * @return class-string<BaseSession>|null
     */
    private function resolveSessionClass(string $subscribableType): ?string
    {
        return match ($subscribableType) {
            (new QuranSubscription)->getMorphClass() => QuranSession::class,
            (new AcademicSubscription)->getMorphClass() => AcademicSession::class,
            default => null,
        };
    }

    /**
     * Sessions that haven't started yet. INV-E3 forbids rewriting sessions
     * that have already happened (or are in flight).
     *
     * Note: SessionStatus has no literal 'pending' case — the invariant doc's
     * `{scheduled, pending}` set maps to {SCHEDULED, READY} in this codebase
     * (READY = "meeting created, ready to start" but not yet ongoing).
     *
     * @return list<string>
     */
    private function preStartStatuses(): array
    {
        return [
            \App\Enums\SessionStatus::SCHEDULED->value,
            \App\Enums\SessionStatus::READY->value,
        ];
    }
}
