<?php

declare(strict_types=1);

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranPackage;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionCycle;
use App\Services\Subscription\SubscriptionConsumption;
use App\Services\Subscription\SubscriptionInvariantChecker;

/**
 * Property/fuzz test: generate random sequences of subscription mutations
 * against a single fixture and assert SubscriptionInvariantChecker returns
 * an empty (no-error) result after every mutation.
 *
 * # Seed strategy
 *
 * Each test seed is recorded in the dataset key so a failure is fully
 * reproducible by re-running the same Pest filter. The PHP-side PRNG
 * (`mt_srand`) is reseeded per dataset entry; the operation choices, source
 * choices, and consumption-type choices all flow from that single seed.
 *
 * We restrict the operation set to data-shape-safe mutators that don't
 * require booting the full renewal flow (which itself calls dispatch + the
 * legacy renewal service, both of which would slow the fuzzer to a crawl
 * and re-test functionality already covered by SubscriptionLifecycleTest).
 *
 * Specifically the fuzzer mixes:
 *   - `consumption_record`  → SubscriptionConsumption::record() w/ random source.
 *   - `consumption_reverse` → SubscriptionConsumption::reverse() on a random active row.
 *   - `noop`                → A pure check tick.
 *
 * After every mutation we run `SubscriptionInvariantChecker::check($sub)`
 * and assert NO 'error'-severity violations land. Info / warning entries
 * (e.g. C2 best-effort audit signals) are tolerated.
 */
beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
    $this->actor = createAdmin($this->academy);

    $this->package = QuranPackage::factory()->create([
        'academy_id' => $this->academy->id,
        'monthly_price' => 200,
        'session_duration_minutes' => 30,
    ]);

    $this->sub = QuranSubscription::factory()->make([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'total_sessions' => 50, // big enough to absorb the fuzzer's ops.
        'sessions_used' => 0,
        'sessions_remaining' => 50,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(60),
        'last_payment_date' => now()->subDay(),
        'package_id' => $this->package->id,
    ]);
    $this->sub->reconciling = true;
    $this->sub->save();
    $this->sub->reconciling = false;

    $this->cycle = SubscriptionCycle::factory()->create([
        'subscribable_type' => $this->sub->getMorphClass(),
        'subscribable_id' => $this->sub->id,
        'academy_id' => $this->academy->id,
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        'sessions_used' => 0,
        'total_sessions' => 50,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(60),
        'package_id' => $this->package->id,
        'pricing_source' => 'package',
        'final_price' => 200,
    ]);
    $this->sub->reconciling = true;
    $this->sub->current_cycle_id = $this->cycle->id;
    $this->sub->save();
    $this->sub->reconciling = false;
    $this->sub->refresh();

    $this->consumption = app(SubscriptionConsumption::class);
    $this->checker = app(SubscriptionInvariantChecker::class);
});

/**
 * One fuzzer step. Picks a random op and applies it. Returns `true` if a
 * mutation was attempted (success or expected exception), `false` for a
 * no-op tick.
 */
function fuzzStep(int $step): bool
{
    $sub = test()->sub;
    $student = test()->student;
    $teacher = test()->teacher;
    $actor = test()->actor;
    $consumption = test()->consumption;

    // Pick an op.
    $ops = ['consumption_record', 'consumption_record', 'consumption_reverse', 'noop'];
    $op = $ops[mt_rand(0, count($ops) - 1)];

    if ($op === 'noop') {
        return false;
    }

    if ($op === 'consumption_record') {
        $sources = [
            SessionConsumption::SOURCE_AUTO_ATTENDANCE,
            SessionConsumption::SOURCE_TEACHER_REPORT,
            SessionConsumption::SOURCE_ADMIN_MANUAL,
        ];
        $types = [
            SessionConsumption::TYPE_ATTENDED,
            SessionConsumption::TYPE_LATE,
            SessionConsumption::TYPE_LEFT,
            SessionConsumption::TYPE_ABSENT_COUNTED,
        ];
        $source = $sources[mt_rand(0, count($sources) - 1)];
        $type = $types[mt_rand(0, count($types) - 1)];
        $sourceUser = $source === SessionConsumption::SOURCE_AUTO_ATTENDANCE ? null : $actor;

        $session = QuranSession::factory()->create([
            'academy_id' => $sub->academy_id,
            'student_id' => $student->id,
            'quran_teacher_id' => $teacher->id,
            'quran_subscription_id' => $sub->id,
            'subscription_cycle_id' => $sub->current_cycle_id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addMinutes($step * 30),
            // INV-E1: session.duration_minutes must match the cycle's
            // package.session_duration_minutes (30 per the beforeEach setup).
            // The factory's default is 45 which would inject a duration-drift
            // violation orthogonal to what the fuzzer is exploring.
            'duration_minutes' => 30,
        ]);

        try {
            $consumption->record($session, $student, $sub, $source, $sourceUser, $type);
        } catch (\App\Exceptions\Subscription\OverConsumptionAttempt) {
            // Tolerated — INV-B4 enforcement; the invariant checker should
            // still pass because the write was refused.
        }

        return true;
    }

    if ($op === 'consumption_reverse') {
        $row = SessionConsumption::query()
            ->where('subscription_id', $sub->id)
            ->whereNull('reversed_at')
            ->inRandomOrder()
            ->first();
        if ($row === null) {
            return false;
        }
        $consumption->reverse($row, 'fuzz_step_'.$step, $actor);

        return true;
    }

    return false;
}

function fuzzSeeds(): array
{
    // Deterministic, named seeds so failures are reproducible.
    return array_map(
        fn (int $seed) => [$seed],
        [
            10001, 10002, 10003, 10004, 10005,
            20001, 20002, 20003, 20004, 20005,
            30001, 30002, 30003, 30004, 30005,
            40001, 40002, 40003, 40004, 40005,
            50001, 50002, 50003, 50004, 50005,
            60001, 60002, 60003, 60004, 60005,
            70001, 70002, 70003, 70004, 70005,
            80001, 80002, 80003, 80004, 80005,
            90001, 90002, 90003, 90004, 90005,
            91001, 91002, 91003, 91004, 91005,
        ],
    );
}

test('fuzz: random op sequences keep the subscription invariant-clean (seed=:seed)', function (int $seed) {
    mt_srand($seed);

    // 20 ops per seed × 50 seeds ≈ 1000 mutation attempts total.
    for ($step = 0; $step < 20; $step++) {
        fuzzStep($step);

        $this->sub->refresh();
        $violations = $this->checker->check($this->sub);
        $errors = array_values(array_filter(
            $violations,
            fn ($v) => ($v['severity'] ?? 'error') === 'error',
        ));

        if ($errors !== []) {
            $codes = implode(',', array_unique(array_map(fn ($v) => $v['code'], $errors)));
            $this->fail(sprintf(
                'Fuzz seed %d, step %d: invariant violations [%s] surfaced. Details: %s',
                $seed,
                $step,
                $codes,
                json_encode($errors, JSON_UNESCAPED_SLASHES),
            ));
        }
    }

    expect(true)->toBeTrue();
})->with(fuzzSeeds());
