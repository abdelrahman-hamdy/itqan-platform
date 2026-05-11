<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use Illuminate\Support\Facades\DB;

/**
 * Bug #5 — duplicate `teacher_earnings` rows from morph-map FQCN/alias mismatch.
 *
 * Asserts the CORRECT expected behavior: exactly one live earnings row per
 * (session, teacher) tuple, regardless of historical FQCN vs alias storage.
 *
 * Root cause (suspected):
 *   - `EarningsCalculationService::findExistingEarning()` line 619-625 uses
 *     `$session->getMorphClass()` (alias `quran_session`).
 *   - `BaseSessionObserver::reverseSubscriptionAndEarnings()` line 428 uses
 *     `get_class($session)` (FQCN `App\Models\QuranSession`).
 *   - Historical earnings rows inserted BEFORE the morph map was added carry
 *     the FQCN. Post-morph-map, dedup lookups use the alias and never find
 *     the FQCN row → re-firing creates a duplicate alias row → 2 alive rows
 *     for the same (session, teacher).
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'earn-dedup-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    $this->sub = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->active()
        ->create([
            'sessions_used' => 0,
            'sessions_remaining' => 8,
            'total_sessions' => 8,
            'payment_status' => SubscriptionPaymentStatus::PAID,
        ]);
    $this->sub->ensureCurrentCycle();
    $this->profileId = QuranTeacherProfile::where('user_id', $this->teacher->id)->value('id');
});

/** Build a completed session row for testing. */
function dedupSession(array $extra = []): QuranSession
{
    $sub = test()->sub;
    return QuranSession::factory()->create(array_merge([
        'academy_id' => $sub->academy_id,
        'student_id' => $sub->student_id,
        'quran_teacher_id' => $sub->quran_teacher_id,
        'quran_subscription_id' => $sub->id,
        'subscription_cycle_id' => $sub->fresh()->current_cycle_id,
        'scheduled_at' => now()->subMinutes(30),
        'status' => SessionStatus::COMPLETED,
        'counts_for_teacher' => true,
        'actual_duration_minutes' => 30,
    ], $extra));
}

/** Insert a teacher_earnings row directly with a specified session_type form. */
function insertEarning(\App\Models\QuranSession $session, int $profileId, string $sessionType, float $amount = 100.00): int
{
    return DB::table('teacher_earnings')->insertGetId([
        'academy_id' => $session->academy_id,
        'teacher_type' => 'quran_teacher',
        'teacher_id' => $profileId,
        'session_type' => $sessionType,
        'session_id' => $session->id,
        'amount' => $amount,
        'calculation_method' => 'individual_rate',
        'rate_snapshot' => json_encode([]),
        'calculation_metadata' => json_encode([]),
        'earning_month' => now()->startOfMonth()->toDateString(),
        'session_completed_at' => $session->scheduled_at,
        'calculated_at' => now(),
        'is_finalized' => true,
        'is_disputed' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

describe('Bug #5 — earnings deduplication invariant', function () {
    it('B5-1 — earnings lookup uses morph alias, NOT FQCN', function () {
        $session = dedupSession();
        // Insert an FQCN-form earning (simulates pre-morph-map state).
        $fqcnId = insertEarning($session, $this->profileId, 'App\\Models\\QuranSession');

        // Now call findExistingEarning (or proxy via isAlreadyCalculated).
        $service = app(\App\Services\EarningsCalculationService::class);
        $reflection = new \ReflectionMethod($service, 'findExistingEarning');
        $reflection->setAccessible(true);
        $found = $reflection->invoke($service, $session);

        // CORRECT behavior: lookup should detect the FQCN row even when
        // searching with the alias (they're semantically the same earning).
        expect($found?->id)->toBe($fqcnId, 'findExistingEarning must dedup across FQCN+alias variants');
    });

    it('B5-2 — UNIQUE index spans FQCN/alias variants via normalization trigger', function () {
        // Bug #5 fix: a BEFORE INSERT trigger normalizes session_type from FQCN
        // → alias before storage, so the existing unique index on
        // (session_type, session_id) catches the duplicate even when callers
        // submit different surface forms.
        $session = dedupSession();
        $idFqcn = insertEarning($session, $this->profileId, 'App\\Models\\QuranSession');

        $threw = null;
        $idAlias = null;
        try {
            $idAlias = insertEarning($session, $this->profileId, 'quran_session');
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            $threw = $e;
        }

        expect($threw)->not->toBeNull(
            'second insert (alias form, normalized to same value as the first) must hit the UNIQUE constraint'
        );
        expect($idAlias)->toBeNull('the alias-form insert must NOT have produced a row id');

        $count = DB::table('teacher_earnings')->where('session_id', $session->id)->whereNull('deleted_at')->count();
        expect($count)->toBe(1, sprintf(
            'expected exactly 1 earning row after FQCN+alias collision, got %d',
            $count
        ));

        // The surviving row must hold the normalized alias form (proof that
        // the trigger ran on the FQCN insert too).
        $survivor = DB::table('teacher_earnings')->where('id', $idFqcn)->first();
        expect($survivor->session_type)->toBe('quran_session');
    });

    it('B5-3 — reverseSubscriptionAndEarnings cleanup uses get_class() (FQCN) not morph alias', function () {
        // Documents the exact line of the bug at BaseSessionObserver:428.
        // Build a session with an alias-form earning, then flip COMPLETED→CANCELLED.
        // Observer's cleanup will try to delete via FQCN and miss the alias row.
        $session = dedupSession();
        $earningId = insertEarning($session, $this->profileId, 'quran_session');

        // Manually mark the session as subscription_counted so the reverse
        // path fires (observer condition: isSubscriptionCounted=true).
        $session->update(['subscription_counted' => true]);
        $session = $session->fresh();

        // Flip to CANCELLED — observer's reverseSubscriptionAndEarnings fires.
        $session->update(['status' => SessionStatus::CANCELLED]);

        $earningStillAlive = DB::table('teacher_earnings')
            ->where('id', $earningId)
            ->whereNull('deleted_at')
            ->exists();

        // CORRECT behavior: cleanup must use the morph alias to find and
        // delete the row. Current: cleanup uses FQCN, doesn't match alias
        // row, so the earning survives the cancellation.
        expect($earningStillAlive)->toBeFalse(
            'after CANCELLED, the alias-form earning must be cleaned up (Bug #5 — uses FQCN, misses alias)'
        );
    });
});
