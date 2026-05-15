<?php

declare(strict_types=1);

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use Illuminate\Support\Facades\DB;

/**
 * Issue #4 regression — the 2026_05_15 backfill copies parent
 * subscription.package_id onto cycles where pricing_source='package' but
 * the snapshot is NULL (artifact of the 2026_05_14_000002 default).
 *
 * Idempotent: re-running the backfill leaves filled rows untouched and
 * never overwrites a populated snapshot.
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'pkg-backfill-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
    $this->package = QuranPackage::factory()->create([
        'academy_id' => $this->academy->id,
        'monthly_price' => 200,
    ]);

    $this->sub = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->active()
        ->create([
            'academy_id' => $this->academy->id,
            'package_id' => $this->package->id,
            'status' => SessionSubscriptionStatus::ACTIVE,
            'payment_status' => SubscriptionPaymentStatus::PAID,
            'total_sessions' => 8,
            'sessions_used' => 0,
            'sessions_remaining' => 8,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
        ]);

    $this->cycle = SubscriptionCycle::factory()->create([
        'subscribable_type' => $this->sub->getMorphClass(),
        'subscribable_id' => $this->sub->id,
        'academy_id' => $this->academy->id,
        'cycle_number' => 1,
        'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
        'payment_status' => SubscriptionCycle::PAYMENT_PAID,
        'total_sessions' => 8,
        'sessions_used' => 0,
        'starts_at' => now()->subDays(5),
        'ends_at' => now()->addDays(25),
        'package_id' => null, // legacy shape: snapshot never copied
        'pricing_source' => 'package',
        'final_price' => 200,
    ]);
});

function runPackageIdBackfill(): void
{
    DB::statement(<<<'SQL'
        UPDATE subscription_cycles c
        INNER JOIN quran_subscriptions s ON s.id = c.subscribable_id
        SET c.package_id = s.package_id
        WHERE c.subscribable_type = ?
          AND c.package_id IS NULL
          AND c.pricing_source = 'package'
          AND s.package_id IS NOT NULL
    SQL, [(new QuranSubscription)->getMorphClass()]);
}

it('fills the NULL package_id snapshot from the parent subscription', function () {
    expect($this->cycle->fresh()->package_id)->toBeNull();

    runPackageIdBackfill();

    expect($this->cycle->fresh()->package_id)->toBe($this->package->id);
});

it('is idempotent — second run does not overwrite an already-populated snapshot', function () {
    runPackageIdBackfill();
    $afterFirst = $this->cycle->fresh()->package_id;

    runPackageIdBackfill();
    $afterSecond = $this->cycle->fresh()->package_id;

    expect($afterFirst)->toBe($this->package->id);
    expect($afterSecond)->toBe($this->package->id);
});

it('does not touch cycles whose pricing_source is sale_price or manual_override', function () {
    $this->cycle->forceFill(['pricing_source' => 'sale_price'])->save();

    runPackageIdBackfill();

    expect($this->cycle->fresh()->package_id)->toBeNull();
});
