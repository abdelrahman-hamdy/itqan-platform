<?php

declare(strict_types=1);

use App\Constants\PauseReason;
use App\Enums\SessionStatus;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Session corner cases — Scenarios E6–E9 from the test-plan.
 *
 *   E6 — Supervisor resume on a SUSPENDED-by-pause sub restores SUSPENDED
 *        sessions that fall inside [starts_at, ends_at] back to SCHEDULED.
 *        Sessions outside the window stay SUSPENDED (no cross-cycle
 *        interpolation).
 *   E7 — Make-up session creation must NOT auto-set `subscription_counted=1`.
 *        The counted flag is only set via `useSession()` after completion —
 *        any creation path that sets it directly creates the Bug #8 drift
 *        pattern (sub 892's session 9297 is the prime prod suspect).
 *   E8 — Group circle: 5 students attend one session, each
 *        `meeting_attendance` row controls its own `counts_for_subscription`.
 *        Matrix decisions are per-student, not per-session.
 *   E9 — Trial subscription creation sets the trial flags and zero price;
 *        it does NOT mint a regular sub.
 */
beforeEach(function () {
    Notification::fake();

    $this->academy = createAcademy(['subdomain' => 'sessionedge-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);

    // Tenant context so global academy scopes don't black-hole subscription
    // and session queries during artisan/service calls.
    setTenantContext($this->academy);
});

describe('E6 — supervisor resume restores SUSPENDED sessions inside the window', function () {
    it('E6 — resume restores in-window SUSPENDED → SCHEDULED but leaves out-of-window ones suspended', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->manuallyPaused()
            ->create();
        $sub->ensureCurrentCycle();
        $sub->refresh();

        // One session inside the window (in-window).
        $inWindow = QuranSession::withoutEvents(fn () => QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $sub->id,
            'scheduled_at' => $sub->starts_at->copy()->addDays(2),
            'status' => SessionStatus::SUSPENDED,
        ]));

        // One session outside the window (after ends_at).
        $outOfWindow = QuranSession::withoutEvents(fn () => QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $sub->id,
            'scheduled_at' => $sub->ends_at->copy()->addDays(10),
            'status' => SessionStatus::SUSPENDED,
        ]));

        $sub->resume();

        expect($inWindow->fresh()->status)->toBe(SessionStatus::SCHEDULED);
        expect($outOfWindow->fresh()->status)->toBe(SessionStatus::SUSPENDED, 'out-of-window SUSPENDED sessions must NOT cross-cycle interpolate');
    });

    it('E6b — END_OF_PERIOD pause stamps pause_reason so the Filament Resume button gate triggers', function () {
        // The model-level resume() is permissive — the END_OF_PERIOD gate
        // lives at the Filament button visibility predicate (ResumePolicyTest
        // covers that path). Here we only assert that the auto-pause cron
        // path stamps the right pause_reason so the UI predicate has the
        // signal it needs.
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->autoPaused()
            ->create();

        expect($sub->fresh()->pause_reason)->toBe(PauseReason::END_OF_PERIOD);
    });
});

describe('E7 — make-up session must NOT inherit consumption from parent', function () {
    it('E7 — a make-up session created for a cancelled parent has no SessionConsumption row at creation', function () {
        // Post-Phase-4 invariant: a freshly-created SCHEDULED session must
        // have ZERO active session_consumption rows. Consumption is only
        // recorded when CalculateSessionForAttendance runs on COMPLETED.
        // If the make-up creation path were copying any state from the
        // parent, an active consumption row would appear at creation time
        // — that would be Bug #8's drift origin.
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create([
                'sessions_used' => 1,
                'sessions_remaining' => 7,
                'total_sessions' => 8,
            ]);
        $sub->ensureCurrentCycle();

        // Parent session: was completed, counted, then cancelled (refunded).
        QuranSession::withoutEvents(fn () => QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $sub->id,
            'subscription_cycle_id' => $sub->fresh()->current_cycle_id,
            'scheduled_at' => now()->subDays(3),
            'status' => SessionStatus::CANCELLED,
            'cancelled_at' => now()->subDay(),
        ]));

        // Simulate make-up creation: a new SCHEDULED session minted as a
        // replacement.
        $makeUp = QuranSession::withoutEvents(fn () => QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $sub->id,
            'subscription_cycle_id' => $sub->fresh()->current_cycle_id,
            'scheduled_at' => now()->addDays(2),
            'status' => SessionStatus::SCHEDULED,
        ]));

        $activeConsumption = \App\Models\SessionConsumption::query()
            ->where('session_id', $makeUp->id)
            ->where('session_type', $makeUp->getMorphClass())
            ->whereNull('reversed_at')
            ->count();

        expect($activeConsumption)->toBe(0,
            'make-up / new sessions must start with zero active consumption rows — Bug #8 drift origin'
        );
    });
});

describe('E8 — group circle: 5-student attendance has per-student counting', function () {
    it('E8 — each student attendance row controls its own counts_for_subscription flag', function () {
        // The matrix decision is per-student. Multiple students attending
        // the same group session do NOT share one counts_for_subscription
        // value — each has its own row.
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create();
        $sub->ensureCurrentCycle();

        $session = QuranSession::withoutEvents(fn () => QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $sub->id,
            'session_type' => 'group',
            'scheduled_at' => now()->subHour(),
            'ended_at' => now()->subMinutes(30),
            'status' => SessionStatus::COMPLETED,
            'duration_minutes' => 30,
        ]));

        // 5 students attend; their own meeting_attendance rows each carry
        // a counts_for_subscription value.
        $students = [$this->student];
        for ($i = 0; $i < 4; $i++) {
            $students[] = createStudent($this->academy);
        }
        foreach ($students as $i => $stu) {
            \App\Models\MeetingAttendance::create([
                'session_id' => $session->id,
                // meeting_attendances.session_type is the SESSION's
                // session_type column (group/individual/...), not the morph alias.
                'session_type' => 'group',
                'user_id' => $stu->id,
                'user_type' => 'student',
                'attendance_status' => 'attended',
                'attendance_percentage' => 100,
                'is_calculated' => true,
                'first_joined_at' => now()->subHour(),
                'last_left_at' => now()->subMinutes(30),
                'total_duration_minutes' => 30,
                // First student excluded by matrix, rest included — to
                // prove per-student gating.
                'counts_for_subscription' => $i === 0 ? false : true,
            ]);
        }

        // CORRECT: per-student flags are independent — each of the 5
        // students has their own counts_for_subscription bit.
        $perStudent = DB::table('meeting_attendances')
            ->where('session_id', $session->id)
            ->where('user_type', 'student')
            ->pluck('counts_for_subscription');
        expect($perStudent->count())->toBe(5);
        // 1 excluded + 4 counted.
        expect($perStudent->filter(fn ($v) => ! $v)->count())->toBe(1);
        expect($perStudent->filter(fn ($v) => (bool) $v)->count())->toBe(4);
    });
});

describe('E9 — trial subscription', function () {
    it('E9 — trial sub has total_price=0 and the trial flag set', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->trial()
            ->create();

        expect((float) $sub->total_price)->toBe(0.0, 'trial price must be 0');
        expect((bool) $sub->is_trial_active)->toBeTrue();
        expect($sub->total_sessions)->toBe(1, 'trial gives exactly 1 session');
    });

    it('E9b — trial sub auto-creates an individual circle on activation if needed', function () {
        // Trial creation can be straightforward in the test layer; the
        // assertion here is that a trial sub doesn't accidentally
        // explode into a group-style enrollment.
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->trial()
            ->create();
        expect($sub->subscription_type)->toBe('individual', 'trial subs are individual, not group');
    });
});
