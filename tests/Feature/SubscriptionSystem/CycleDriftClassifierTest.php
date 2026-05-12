<?php

declare(strict_types=1);

use App\Support\Subscriptions\CycleDriftClassifier;

/**
 * Pure unit tests for the deterministic cycle-drift classifier. The
 * classifier is the foundation of the 2026-05-12 forensic re-analysis —
 * whatever it labels CONFIRMED_BUG is what the operator will apply.
 * If any of these rules silently shift, the wrong subscriptions get
 * "fixed" against active students, so each rule is pinned by a
 * representative synthetic row.
 */
uses()->group('subscription-drift-classifier');

function classifierRow(array $overrides = []): array
{
    return array_merge([
        'stored_used' => 0,
        'actual_counted' => 0,
        'soft_deleted_counted' => 0,
        'prior_repairs' => 0,
        'shown_exhausted' => 0,
        'purchase_source' => 'web',
        'cycle_number' => 1,
        'cycle_state' => 'active',
        'cycle_created_at' => '2026-05-06 00:00:00',
    ], $overrides);
}

it('classifies the Ammar control row as CONFIRMED_BUG', function () {
    // Cycle 686 pre-fix shape: stored=12, actual=8, gap=+4, non-admin source,
    // cycle 2, currently exhausted, no prior repair, no soft-deletes.
    $result = CycleDriftClassifier::classify(classifierRow([
        'stored_used' => 12,
        'actual_counted' => 8,
        'soft_deleted_counted' => 0,
        'prior_repairs' => 0,
        'shown_exhausted' => 1,
        'purchase_source' => 'web',
        'cycle_number' => 2,
        'cycle_state' => 'active',
        'cycle_created_at' => '2026-05-05 12:00:00',
    ]));

    expect($result['class'])->toBe(CycleDriftClassifier::CLASS_CONFIRMED_BUG);
    expect($result['gap'])->toBe(4);
    expect($result['reason_ar'])->toContain('عمار');
});

it('RE_DRIFT beats every other rule when prior_repairs is non-zero', function () {
    // Same shape as the Ammar row, but already repaired once.
    // Re-drift means the forward-only fix is incomplete — top priority.
    $result = CycleDriftClassifier::classify(classifierRow([
        'stored_used' => 12,
        'actual_counted' => 8,
        'soft_deleted_counted' => 0,
        'prior_repairs' => 1,
        'shown_exhausted' => 1,
        'purchase_source' => 'web',
        'cycle_number' => 2,
        'cycle_state' => 'active',
        'cycle_created_at' => '2026-05-05 12:00:00',
    ]));

    expect($result['class'])->toBe(CycleDriftClassifier::CLASS_RE_DRIFT);
});

it('SOFT_DELETED_EXPLAINED fires when ghosts fully cover the positive gap', function () {
    // Stored=10, alive=6, ghosts=4 → gap=+4 fully explained.
    $result = CycleDriftClassifier::classify(classifierRow([
        'stored_used' => 10,
        'actual_counted' => 6,
        'soft_deleted_counted' => 4,
        'cycle_number' => 2,
        'shown_exhausted' => 1,
    ]));

    expect($result['class'])->toBe(CycleDriftClassifier::CLASS_SOFT_DELETED_EXPLAINED);
});

it('SOFT_DELETED partial coverage falls through to CONFIRMED_BUG', function () {
    // Stored=10, alive=4, ghosts=2 → gap=+6 only partially explained.
    $result = CycleDriftClassifier::classify(classifierRow([
        'stored_used' => 10,
        'actual_counted' => 4,
        'soft_deleted_counted' => 2,
        'cycle_number' => 2,
        'shown_exhausted' => 1,
    ]));

    expect($result['class'])->toBe(CycleDriftClassifier::CLASS_CONFIRMED_BUG);
});

it('PRESET_SUSPECT fires for admin source on cycle 1 with positive gap', function () {
    $result = CycleDriftClassifier::classify(classifierRow([
        'stored_used' => 5,
        'actual_counted' => 2,
        'purchase_source' => 'admin',
        'cycle_number' => 1,
        'shown_exhausted' => 1,
    ]));

    expect($result['class'])->toBe(CycleDriftClassifier::CLASS_PRESET_SUSPECT);
});

it('admin source on cycle 2 with shown_exhausted is CONFIRMED_BUG (preset bleed)', function () {
    // Admin preset legitimately lives on cycle 1 only. Drift on cycle ≥ 2
    // of an admin sub is the same materializeFromSubscription bleed as the
    // Ammar case — currently exhausting students who should still have
    // sessions. Forensic survey 2026-05-12 surfaced 8 such rows on prod.
    $result = CycleDriftClassifier::classify(classifierRow([
        'stored_used' => 7,
        'actual_counted' => 3,
        'purchase_source' => 'admin',
        'cycle_number' => 2,
        'shown_exhausted' => 1,
        'cycle_state' => 'active',
        'cycle_created_at' => '2026-05-06 00:00:00',
    ]));

    expect($result['class'])->toBe(CycleDriftClassifier::CLASS_CONFIRMED_BUG);
    expect($result['reason_ar'])->toContain('إداري');
});

it('admin source on cycle 2 without shown_exhausted stays in NEEDS_REVIEW', function () {
    // Same shape but the student is not currently impacted — keep out of
    // the auto-fix cohort. Drift exists but is latent.
    $result = CycleDriftClassifier::classify(classifierRow([
        'stored_used' => 7,
        'actual_counted' => 3,
        'purchase_source' => 'admin',
        'cycle_number' => 2,
        'shown_exhausted' => 0,
        'cycle_state' => 'active',
        'cycle_created_at' => '2026-05-06 00:00:00',
    ]));

    expect($result['class'])->toBe(CycleDriftClassifier::CLASS_NEEDS_REVIEW);
});

it('FORGIVING_UNDERCOUNT fires whenever stored < actual', function () {
    // Student currently sees 3 extra remaining sessions.
    $result = CycleDriftClassifier::classify(classifierRow([
        'stored_used' => 5,
        'actual_counted' => 8,
        'cycle_number' => 2,
    ]));

    expect($result['class'])->toBe(CycleDriftClassifier::CLASS_FORGIVING_UNDERCOUNT);
    expect($result['gap'])->toBe(-3);
});

it('PRE_REFACTOR_AMBIGUOUS fires for cycle-1 drift on a pre-2026-05-04 cycle', function () {
    $result = CycleDriftClassifier::classify(classifierRow([
        'stored_used' => 9,
        'actual_counted' => 5,
        'purchase_source' => 'web',
        'cycle_number' => 1,
        'cycle_state' => 'active',
        'cycle_created_at' => '2026-04-10 00:00:00',
        'shown_exhausted' => 0,
    ]));

    expect($result['class'])->toBe(CycleDriftClassifier::CLASS_PRE_REFACTOR_AMBIGUOUS);
});

it('post-refactor cycle 1 with shown_exhausted lands in CONFIRMED_BUG', function () {
    // Same shape, but cycle was created after 2026-05-04 and student is
    // currently impacted — bypass the pre-refactor escape hatch.
    $result = CycleDriftClassifier::classify(classifierRow([
        'stored_used' => 9,
        'actual_counted' => 5,
        'purchase_source' => 'web',
        'cycle_number' => 1,
        'cycle_state' => 'active',
        'cycle_created_at' => '2026-05-08 00:00:00',
        'shown_exhausted' => 1,
    ]));

    expect($result['class'])->toBe(CycleDriftClassifier::CLASS_CONFIRMED_BUG);
});

it('CONFIRMED_BUG requires shown_exhausted — without it, falls through', function () {
    $result = CycleDriftClassifier::classify(classifierRow([
        'stored_used' => 12,
        'actual_counted' => 8,
        'purchase_source' => 'web',
        'cycle_number' => 2,
        'cycle_state' => 'active',
        'cycle_created_at' => '2026-05-05 12:00:00',
        'shown_exhausted' => 0,
    ]));

    expect($result['class'])->not->toBe(CycleDriftClassifier::CLASS_CONFIRMED_BUG);
});

it('ARCHIVED_NOISE catches anything still drifting on an archived cycle', function () {
    $result = CycleDriftClassifier::classify(classifierRow([
        'stored_used' => 10,
        'actual_counted' => 7,
        'purchase_source' => 'web',
        'cycle_number' => 1,
        'cycle_state' => 'archived',
        'cycle_created_at' => '2026-05-05 00:00:00',
        'shown_exhausted' => 0,
    ]));

    expect($result['class'])->toBe(CycleDriftClassifier::CLASS_ARCHIVED_NOISE);
});

it('produces evidence chips for soft-deletes, admin source, and prior repairs', function () {
    $result = CycleDriftClassifier::classify(classifierRow([
        'stored_used' => 10,
        'actual_counted' => 8,
        'soft_deleted_counted' => 2,
        'prior_repairs' => 1,
        'purchase_source' => 'admin',
        'cycle_number' => 1,
        'shown_exhausted' => 1,
        'cycle_created_at' => '2026-04-01 00:00:00',
    ]));

    expect($result['evidence'])
        ->toContain('👻 2 soft-deleted')
        ->toContain('🛠️ repaired ×1')
        ->toContain('🛠️ admin-preset')
        ->toContain('📜 pre-refactor')
        ->toContain('🔴 shown-exhausted');
});

it('gap is reported as stored - actual', function () {
    $r1 = CycleDriftClassifier::classify(classifierRow([
        'stored_used' => 12,
        'actual_counted' => 8,
    ]));
    $r2 = CycleDriftClassifier::classify(classifierRow([
        'stored_used' => 5,
        'actual_counted' => 9,
    ]));

    expect($r1['gap'])->toBe(4);
    expect($r2['gap'])->toBe(-4);
});

it('NEEDS_REVIEW catches anything not covered by named rules', function () {
    // Zero-gap rows never even reach classify in production, but if one did
    // (e.g., from a stale forensic dump after a counter snapped back),
    // it should land in NEEDS_REVIEW rather than silently pretending OK.
    $result = CycleDriftClassifier::classify(classifierRow([
        'stored_used' => 5,
        'actual_counted' => 5,
        'cycle_state' => 'active',
        'cycle_number' => 2,
    ]));

    expect($result['class'])->toBe(CycleDriftClassifier::CLASS_NEEDS_REVIEW);
});
