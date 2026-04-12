<?php

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranSubscription;
use App\Models\SubscriptionCycle;
use Illuminate\Support\Facades\File;

/**
 * Tests the consolidate-renewal-chains command's ability to collapse legacy
 * `previous_subscription_id` chains into the cycle-based model, including
 * the specific orphan pattern that produced subscription 905 in production.
 */
beforeEach(function () {
    $this->academy = createAcademy();
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    // Create a dummy backup file so --force works
    $this->backupPath = storage_path('app/test-consolidation-backup-'.uniqid().'.sql');
    File::put($this->backupPath, '-- test backup');
});

afterEach(function () {
    if (isset($this->backupPath) && File::exists($this->backupPath)) {
        File::delete($this->backupPath);
    }
});

function makeChainedSub(array $overrides = []): QuranSubscription
{
    return QuranSubscription::factory()->create(array_merge([
        'academy_id' => test()->academy->id,
        'student_id' => test()->student->id,
        'quran_teacher_id' => test()->teacher->id,
    ], $overrides));
}

test('dry run does not mutate any data', function () {
    $root = makeChainedSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'starts_at' => now()->subDays(30),
        'ends_at' => now()->subDays(2),
    ]);
    $renewed = makeChainedSub([
        'previous_subscription_id' => $root->id,
        'status' => SessionSubscriptionStatus::PENDING,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
    ]);

    $this->artisan('subscriptions:consolidate-renewal-chains')
        ->assertExitCode(0);

    expect(QuranSubscription::withoutGlobalScopes()->find($root->id))->not->toBeNull();
    expect(QuranSubscription::withoutGlobalScopes()->find($renewed->id))->not->toBeNull();
});

test('orphan pending-no-date row (828 -> 905 pattern) is folded back into its parent', function () {
    $parent = makeChainedSub([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'starts_at' => now()->subDays(30),
        'ends_at' => now()->addDays(2),
    ]);

    $orphan = makeChainedSub([
        'previous_subscription_id' => $parent->id,
        'status' => SessionSubscriptionStatus::PENDING,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
        'starts_at' => null,
        'ends_at' => null,
        'metadata' => [
            'grace_period_ends_at' => now()->addDays(10)->toDateTimeString(),
            'extensions' => [
                [
                    'type' => 'grace_period',
                    'grace_days' => 14,
                    'extended_at' => now()->toDateTimeString(),
                ],
            ],
        ],
    ]);

    $this->artisan('subscriptions:consolidate-renewal-chains', [
        '--force' => true,
        '--backup-path' => $this->backupPath,
    ])->assertExitCode(0);

    // Orphan is gone
    expect(QuranSubscription::withoutGlobalScopes()->find($orphan->id))->toBeNull();

    // Parent survives
    $parent->refresh();
    expect($parent->id)->not->toBeNull();

    // Parent inherited the grace metadata from the orphan
    expect($parent->metadata['grace_period_ends_at'] ?? null)->not->toBeNull();

    // Parent has a current active cycle snapshot
    expect($parent->current_cycle_id)->not->toBeNull();
    expect($parent->currentCycle->cycle_state)->toBe(SubscriptionCycle::STATE_ACTIVE);
});

test('multi-node chain is fully collapsed with archived cycles', function () {
    $a = makeChainedSub([
        'status' => SessionSubscriptionStatus::EXPIRED,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'starts_at' => now()->subMonths(3),
        'ends_at' => now()->subMonths(2),
    ]);
    $b = makeChainedSub([
        'previous_subscription_id' => $a->id,
        'status' => SessionSubscriptionStatus::EXPIRED,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'starts_at' => now()->subMonths(2),
        'ends_at' => now()->subMonth(),
    ]);
    $c = makeChainedSub([
        'previous_subscription_id' => $b->id,
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addDays(15),
    ]);

    $this->artisan('subscriptions:consolidate-renewal-chains', [
        '--force' => true,
        '--backup-path' => $this->backupPath,
    ])->assertExitCode(0);

    // A and B are deleted, C survives
    expect(QuranSubscription::withoutGlobalScopes()->find($a->id))->toBeNull();
    expect(QuranSubscription::withoutGlobalScopes()->find($b->id))->toBeNull();

    $c->refresh();
    expect($c->previous_subscription_id)->toBeNull();
    expect($c->current_cycle_id)->not->toBeNull();

    // Two archived cycles + one active cycle
    $cycles = $c->cycles()->get();
    expect($cycles->count())->toBe(3);
    expect($cycles->where('cycle_state', SubscriptionCycle::STATE_ARCHIVED)->count())->toBe(2);
    expect($cycles->where('cycle_state', SubscriptionCycle::STATE_ACTIVE)->count())->toBe(1);
});
