<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\TeacherEarning;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Reconciliation cron coverage — Scenarios D1–D5.
 *
 *   D1 — `subscriptions:reconcile-missed` increments cycle.sessions_used for
 *        a COMPLETED session that was missed by the observer (the safety
 *        net for queue failures).
 *   D2 — `subscriptions:audit-cycle-counts` dry-run posture — reports drift
 *        but does NOT mutate rows.
 *   D3 — `subscriptions:audit-cycle-counts --apply` corrects the mismatched
 *        cycle and synchronizes the subscription columns.
 *   D4 — `earnings:calculate-missed --days=7` dispatches the earnings job
 *        for COMPLETED sessions that have no TeacherEarning row yet.
 *   D5 — `teachers:recalculate-counters` rebuilds total_students /
 *        total_sessions on the teacher profile from actual session data.
 *
 * Tenant context is set in beforeEach because the cron paths apply global
 * scopes; without context, the commands would walk zero rows.
 */
beforeEach(function () {
    Notification::fake();

    $this->academy = createAcademy(['subdomain' => 'reconcile-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    setTenantContext($this->academy);

    $this->sub = QuranSubscription::factory()
        ->forStudent($this->student)
        ->forTeacher($this->teacher)
        ->active()
        ->create([
            'sessions_used' => 0,
            'sessions_remaining' => 8,
            'total_sessions' => 8,
        ]);
    $this->sub->ensureCurrentCycle();
});

/** Build a completed session with subscription_counted=false (the safety-net target). */
function uncountedSession(int $subId, ?int $cycleId): QuranSession
{
    return QuranSession::withoutEvents(fn () => QuranSession::factory()->create([
        'academy_id' => test()->academy->id,
        'student_id' => test()->student->id,
        'quran_teacher_id' => test()->teacher->id,
        'quran_subscription_id' => $subId,
        'subscription_cycle_id' => $cycleId,
        'status' => SessionStatus::COMPLETED,
        'scheduled_at' => now()->subHours(2),
        'ended_at' => now()->subHour(),
        'duration_minutes' => 30,
        'actual_duration_minutes' => 30,
        'counts_for_teacher' => true,
        'subscription_counted' => false,
    ]));
}

describe('D1 — subscriptions:reconcile-missed', function () {
    it('D1 — completed session with subscription_counted=false is reconciled and flag flips to true', function () {
        $session = uncountedSession($this->sub->id, $this->sub->fresh()->current_cycle_id);
        // Backdate ended_at past the cron's --minutes=10 default so it's
        // eligible.
        DB::table('quran_sessions')->where('id', $session->id)->update([
            'ended_at' => now()->subMinutes(30),
        ]);

        Artisan::call('subscriptions:reconcile-missed');

        // CORRECT: the cron flips subscription_counted=true after
        // calling updateSubscriptionUsage. The subscription's sessions_used
        // counter increments via useSession.
        $freshSession = $session->fresh();
        expect($freshSession->subscription_counted)->toBeTrue(
            'reconcile-missed must flip subscription_counted=true after successful count'
        );
    });

    it('D1b — recent completion (within --minutes window) is NOT reconciled', function () {
        $session = uncountedSession($this->sub->id, $this->sub->fresh()->current_cycle_id);
        // Override ended_at to NOW so the session is under the
        // 10-minute window default and shouldn't be picked up.
        DB::table('quran_sessions')->where('id', $session->id)->update([
            'ended_at' => now(),
        ]);

        Artisan::call('subscriptions:reconcile-missed');

        // CORRECT: recent sessions are left for the primary path to handle
        // first; the cron only kicks in for stale stragglers (>= 10 min old).
        expect($session->fresh()->subscription_counted)->toBeFalse();
    });
});

describe('D2/D3 — subscriptions:audit-cycle-counts', function () {
    it('D2 — dry-run (default) reports drift but does NOT mutate rows', function () {
        $cycle = $this->sub->fresh()->currentCycle;
        // Manufacture drift: 3 sessions are COMPLETED+counted, but
        // cycle.sessions_used = 5.
        $cycle->update(['sessions_used' => 5, 'sessions_completed' => 5]);
        for ($i = 0; $i < 3; $i++) {
            QuranSession::withoutEvents(fn () => QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'quran_subscription_id' => $this->sub->id,
                'subscription_cycle_id' => $cycle->id,
                'status' => SessionStatus::COMPLETED,
                'subscription_counted' => true,
                'scheduled_at' => now()->subHours($i + 2),
                'ended_at' => now()->subHours($i + 1),
                'duration_minutes' => 30,
                'actual_duration_minutes' => 30,
                'counts_for_teacher' => true,
            ]));
        }

        // Run WITHOUT --apply.
        Artisan::call('subscriptions:audit-cycle-counts');

        // CORRECT: dry-run does not mutate. cycle.sessions_used stays at 5
        // (the drifted value) even though the actual count is 3.
        expect((int) $cycle->fresh()->sessions_used)->toBe(5, 'dry-run must NOT correct drift');
    });

    it('D3 — --apply corrects the cycle counter to the actual counted-session count', function () {
        $cycle = $this->sub->fresh()->currentCycle;
        $cycle->update(['sessions_used' => 7, 'sessions_completed' => 7]);
        // 2 sessions actually counted.
        for ($i = 0; $i < 2; $i++) {
            QuranSession::withoutEvents(fn () => QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->teacher->id,
                'quran_subscription_id' => $this->sub->id,
                'subscription_cycle_id' => $cycle->id,
                'status' => SessionStatus::COMPLETED,
                'subscription_counted' => true,
                'scheduled_at' => now()->subHours($i + 2),
                'ended_at' => now()->subHours($i + 1),
                'duration_minutes' => 30,
                'actual_duration_minutes' => 30,
                'counts_for_teacher' => true,
            ]));
        }

        Artisan::call('subscriptions:audit-cycle-counts', ['--apply' => true]);

        expect((int) $cycle->fresh()->sessions_used)->toBe(2, '--apply must correct drifted sessions_used to actual count');
    });
});

describe('D4 — earnings:calculate-missed', function () {
    it('D4 — completed session without a TeacherEarning row dispatches the earnings job', function () {
        \Illuminate\Support\Facades\Queue::fake();

        // Set teacher prices so the earnings calc would succeed if dispatched.
        QuranTeacherProfile::where('user_id', $this->teacher->id)->update([
            'individual_session_prices' => ['30' => 50],
            'session_price_individual' => 50,
        ]);

        $session = QuranSession::withoutEvents(fn () => QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $this->sub->id,
            'subscription_cycle_id' => $this->sub->fresh()->current_cycle_id,
            'status' => SessionStatus::COMPLETED,
            'scheduled_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
            'duration_minutes' => 30,
            'actual_duration_minutes' => 30,
            'counts_for_teacher' => true,
            'subscription_counted' => true,
            'session_type' => 'individual',
        ]));

        Artisan::call('earnings:calculate-missed', ['--days' => 7]);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\CalculateSessionEarningsJob::class);
    });

    it('D4b — skips sessions that already have a TeacherEarning row', function () {
        \Illuminate\Support\Facades\Queue::fake();

        $session = QuranSession::withoutEvents(fn () => QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $this->sub->id,
            'subscription_cycle_id' => $this->sub->fresh()->current_cycle_id,
            'status' => SessionStatus::COMPLETED,
            'scheduled_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
            'duration_minutes' => 30,
            'actual_duration_minutes' => 30,
            'counts_for_teacher' => true,
            'session_type' => 'individual',
        ]));

        // Pre-existing earning row for this session.
        // NOTE: use the FQCN here — the calculate-missed cron's lookup
        // (`TeacherEarning::forSession(QuranSession::class, ...)`) compares
        // session_type by FQCN, not morph alias. See Bug #5 for the
        // wider FQCN/alias mismatch.
        TeacherEarning::create([
            'academy_id' => $session->academy_id,
            'teacher_type' => 'quran_teacher',
            'teacher_id' => QuranTeacherProfile::where('user_id', $this->teacher->id)->value('id'),
            'session_type' => QuranSession::class,
            'session_id' => $session->id,
            'amount' => 50,
            'calculation_method' => 'individual_rate',
            'rate_snapshot' => json_encode([]),
            'calculation_metadata' => json_encode([]),
            'earning_month' => now()->startOfMonth()->toDateString(),
            'session_completed_at' => $session->scheduled_at,
            'calculated_at' => now(),
            'is_finalized' => true,
            'is_disputed' => false,
        ]);

        Artisan::call('earnings:calculate-missed', ['--days' => 7]);

        \Illuminate\Support\Facades\Queue::assertNotPushed(\App\Jobs\CalculateSessionEarningsJob::class);
    });
});

describe('D5 — teachers:recalculate-counters', function () {
    it('D5 — completed sessions rebuild total_students + total_sessions on the profile', function () {
        $profile = QuranTeacherProfile::where('user_id', $this->teacher->id)->first();
        // Reset counters to zero to prove the cron rebuilds them.
        DB::table('quran_teacher_profiles')->where('id', $profile->id)->update([
            'total_students' => 0,
            'total_sessions' => 0,
        ]);

        // Build 3 completed sessions across 2 distinct students.
        $studentB = createStudent($this->academy);
        $studentsAndCounts = [
            [$this->student, 2],
            [$studentB, 1],
        ];
        foreach ($studentsAndCounts as [$student, $count]) {
            for ($i = 0; $i < $count; $i++) {
                QuranSession::withoutEvents(fn () => QuranSession::factory()->create([
                    'academy_id' => $this->academy->id,
                    'student_id' => $student->id,
                    'quran_teacher_id' => $this->teacher->id,
                    'status' => 'completed',
                    'scheduled_at' => now()->subDays(1)->addHours($i),
                    'ended_at' => now()->subDays(1)->addHours($i)->addMinutes(30),
                    'duration_minutes' => 30,
                    'actual_duration_minutes' => 30,
                ]));
            }
        }

        Artisan::call('teachers:recalculate-counters');

        $fresh = QuranTeacherProfile::find($profile->id);
        expect((int) $fresh->total_sessions)->toBe(3, 'total_sessions = COUNT(completed sessions)');
        expect((int) $fresh->total_students)->toBe(2, 'total_students = DISTINCT student_id over completed sessions');
    });
});
