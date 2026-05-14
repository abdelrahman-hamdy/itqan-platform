<?php

namespace Tests\Concerns;

use App\Models\Academy;
use App\Models\QuranPackage;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SubscriptionAuditLog;
use App\Models\SubscriptionCycle;
use App\Models\User;
use Carbon\Carbon;

/**
 * Loads the JSON fixtures under tests/Fixtures/Subscription/ into in-memory
 * models so prod-shape regression tests can exercise the v2 contract against
 * the actual broken shapes we've seen on prod.
 *
 * Each fixture file follows the shape documented in tests/Fixtures/Subscription/*.json
 * (description, subscription, cycle, optional package, expected_violation_codes,
 * recovery_action, notes).
 *
 * All `*_offset_days` fields in the fixture are resolved relative to now() when
 * the fixture is loaded — keeps fixtures portable across test runs without
 * mutating the timestamps in the JSON itself.
 *
 * IMPORTANT: writes go through factories, NOT through the v2 writer services —
 * the entire point of these fixtures is to materialise *invalid* shapes that
 * the v2 writers would refuse to produce. The InvariantChecker then surfaces
 * the violations as expected.
 */
trait LoadsSubscriptionFixtures
{
    protected function loadSubscriptionFixture(string $name): array
    {
        $path = base_path("tests/Fixtures/Subscription/{$name}.json");
        $payload = json_decode(file_get_contents($path), true);

        $academy = Academy::factory()->create();
        $student = User::factory()->create(['academy_id' => $academy->id]);
        $teacher = User::factory()->create(['academy_id' => $academy->id]);

        $packageAttrs = $payload['package'] ?? [];
        $package = QuranPackage::factory()->create(array_merge([
            'academy_id' => $academy->id,
            'session_duration_minutes' => 30,
            'monthly_price' => 200,
        ], $packageAttrs));

        $subAttrs = $this->resolveOffsets($payload['subscription'] ?? []);
        // Set $reconciling = true so the BaseSubscriptionObserver does NOT
        // auto-fill missing starts_at / ends_at / next_billing_date — these
        // fixtures intentionally construct invalid shapes that the v2 writers
        // would refuse to produce; the observer's defaults would erase the
        // very fields we're testing the checker against.
        $subscription = QuranSubscription::factory()->make(array_merge([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'quran_teacher_id' => $teacher->id,
            'package_id' => $package->id,
        ], $subAttrs));
        $subscription->reconciling = true;
        $subscription->save();
        $subscription->reconciling = false;

        $cycleAttrs = $this->resolveOffsets($payload['cycle'] ?? []);
        $cycle = SubscriptionCycle::factory()->create(array_merge([
            'subscribable_type' => $subscription->getMorphClass(),
            'subscribable_id' => $subscription->id,
            'academy_id' => $academy->id,
            // INV-D4: a cycle with pricing_source='package' (the default)
            // must carry a package_id snapshot. Without it the checker
            // flags every fixture for snapshot loss before the per-fixture
            // shape gets a chance to surface.
            'package_id' => $package->id,
            'package_snapshot' => $packageAttrs ?: ['monthly_price' => 200, 'session_duration_minutes' => 30],
        ], $cycleAttrs));

        // Link the cycle as the sub's current cycle so checks anchored on
        // `currentCycle` (INV-A1/A2/A4/A5/...) can actually find a cycle to
        // compare against. SubscriptionRowGuard requires reconciling=true to
        // permit writes to the derived sub row.
        $subscription->reconciling = true;
        $subscription->current_cycle_id = $cycle->id;
        $subscription->save();
        $subscription->reconciling = false;

        // INV-F2 / INV-G1 surface from audit-log history. Fixtures that need
        // those violations declare a top-level `audit_log_entry` map. We
        // create exactly one row from it; multi-row scenarios stay out of
        // scope.
        if (! empty($payload['audit_log_entry']) && is_array($payload['audit_log_entry'])) {
            $logAttrs = $payload['audit_log_entry'];
            SubscriptionAuditLog::create([
                'subscription_id' => $subscription->id,
                'subscription_type' => $subscription->getMorphClass(),
                'cycle_id' => $cycle->id,
                'action' => $logAttrs['action'] ?? 'unknown',
                'source' => $logAttrs['source'] ?? 'admin',
                'actor_user_id' => $logAttrs['actor_user_id'] ?? null,
                'before_state' => $logAttrs['before_state'] ?? [],
                'after_state' => $logAttrs['after_state'] ?? [],
                'view_state_before' => $logAttrs['view_state_before'] ?? null,
                'view_state_after' => $logAttrs['view_state_after'] ?? null,
                'invariant_violations' => $logAttrs['invariant_violations'] ?? null,
                'has_violations' => (bool) ($logAttrs['has_violations'] ?? false),
                'latency_ms' => $logAttrs['latency_ms'] ?? null,
                'created_at' => now(),
            ]);
        }

        // INV-E1 surfaces from future scheduled sessions whose duration
        // disagrees with the cycle's package. Fixtures that need that
        // violation declare a top-level `future_session` map.
        if (! empty($payload['future_session']) && is_array($payload['future_session'])) {
            $futureSession = $this->resolveOffsets($payload['future_session']);
            QuranSession::factory()->create(array_merge([
                'academy_id' => $academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $teacher->id,
                'quran_subscription_id' => $subscription->id,
                'subscription_cycle_id' => $cycle->id,
            ], $futureSession));
        }

        $subscription->refresh();

        return [
            'subscription' => $subscription,
            'cycle' => $cycle,
            'student' => $student,
            'teacher' => $teacher,
            'academy' => $academy,
            'package' => $package,
            'expected_violations' => $payload['expected_violation_codes'] ?? [],
            'recovery_action' => $payload['recovery_action'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ];
    }

    /**
     * Resolves `*_offset_days` keys (e.g. `starts_at_offset_days`) into concrete
     * Carbon timestamps on the corresponding base key (`starts_at`).
     */
    private function resolveOffsets(array $attrs): array
    {
        $resolved = [];
        foreach ($attrs as $key => $value) {
            if (str_ends_with($key, '_offset_days')) {
                $baseKey = substr($key, 0, -strlen('_offset_days'));
                if ($value === null) {
                    $resolved[$baseKey] = null;
                } else {
                    $resolved[$baseKey] = Carbon::now()->addDays((int) $value);
                }
            } else {
                $resolved[$key] = $value;
            }
        }

        return $resolved;
    }
}
