<?php

declare(strict_types=1);

use App\Enums\SessionSubscriptionStatus;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSubscription;

/**
 * End-to-end coverage of subscription ↔ Quran-circle lifecycle.
 *
 *   - Calling createIndividualCircle() on an individual sub spawns a
 *     QuranIndividualCircle linked back via education_unit_id +
 *     education_unit_type, mirrors session counts onto the circle, and
 *     stamps is_active = true.
 *   - syncLinkedEducationUnitActiveFlag(true|false) mirrors the active flag
 *     onto the circle.
 *   - resume() on a paused sub flips the linked circle's is_active back to true.
 *   - syncEducationUnitStatus() called via $sub->update(['status' => CANCELLED])
 *     flips the circle's is_active false.
 *   - the legacy createIndividualCircle re-uses an already-linked circle
 *     (idempotent).
 *
 * See `app/Models/QuranSubscription.php::createIndividualCircle()`,
 * `linkToEducationUnit()`, `syncEducationUnitStatus()`,
 * `BaseSubscription::syncLinkedEducationUnitActiveFlag()` (line 933).
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'circle-int-'.uniqid()]);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

/** Build an individual quran subscription. */
function makeIndividualSub(\App\Models\User $student, \App\Models\User $teacher): QuranSubscription
{
    return QuranSubscription::factory()
        ->forStudent($student)
        ->forTeacher($teacher)
        ->active()
        ->create([
            'subscription_type' => QuranSubscription::SUBSCRIPTION_TYPE_INDIVIDUAL,
            'sessions_remaining' => 8,
            'total_sessions' => 8,
        ]);
}

describe('subscription → circle creation', function () {
    it('CL1 — createIndividualCircle() spawns a QuranIndividualCircle linked back to the subscription', function () {
        $sub = makeIndividualSub($this->student, $this->teacher);

        $circle = $sub->createIndividualCircle();

        expect($circle)->toBeInstanceOf(QuranIndividualCircle::class);
        expect($circle->student_id)->toBe($this->student->id);
        expect($circle->quran_teacher_id)->toBe($this->teacher->id);
        expect($circle->total_sessions)->toBe(8);
        expect($circle->is_active)->toBeTrue();

        // Subscription is linked back via polymorphic FK.
        $sub = $sub->fresh();
        expect($sub->education_unit_id)->toBe($circle->id);
        expect($sub->education_unit_type)->toBe($circle->getMorphClass());
    });

    it('CL2 — createIndividualCircle() is idempotent — second call reuses the existing circle', function () {
        $sub = makeIndividualSub($this->student, $this->teacher);

        $circle1 = $sub->createIndividualCircle();
        $circle2 = $sub->fresh()->createIndividualCircle();

        expect($circle1->id)->toBe($circle2->id);
        expect(QuranIndividualCircle::where('subscription_id', $sub->id)->count())->toBe(1);
    });
});

describe('linked circle is_active mirroring', function () {
    it('CL3 — syncLinkedEducationUnitActiveFlag(true) flips the linked circle is_active=true', function () {
        $sub = makeIndividualSub($this->student, $this->teacher);
        $circle = $sub->createIndividualCircle();
        // Manually flip is_active=false on the circle to simulate a prior pause.
        $circle->update(['is_active' => false]);

        $sub->fresh()->syncLinkedEducationUnitActiveFlag(true);

        expect($circle->fresh()->is_active)->toBeTrue();
    });

    it('CL4 — syncLinkedEducationUnitActiveFlag(false) flips the linked circle is_active=false', function () {
        $sub = makeIndividualSub($this->student, $this->teacher);
        $circle = $sub->createIndividualCircle();
        expect($circle->is_active)->toBeTrue();

        $sub->fresh()->syncLinkedEducationUnitActiveFlag(false);

        expect($circle->fresh()->is_active)->toBeFalse();
    });

    it('CL5 — resume() on a manually paused sub flips the linked circle back to active', function () {
        // Build the circle while ACTIVE, then manually transition to PAUSED so
        // the link is in place.
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->manuallyPaused()
            ->create([
                'subscription_type' => QuranSubscription::SUBSCRIPTION_TYPE_INDIVIDUAL,
            ]);
        // Manually create + link the circle on a paused sub (factories don't
        // create circles automatically).
        $circle = QuranIndividualCircle::create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'subscription_id' => $sub->id,
            'total_sessions' => 8,
            'sessions_remaining' => 8,
            'default_duration_minutes' => 30,
            'is_active' => false,
        ]);
        $sub->linkToEducationUnit($circle);

        $sub->fresh()->resume();

        expect($circle->fresh()->is_active)->toBeTrue();
        expect($sub->fresh()->status)->toBe(SessionSubscriptionStatus::ACTIVE);
    });
});

describe('status-driven mirroring via syncEducationUnitStatus', function () {
    it('CL6 — flipping subscription to CANCELLED via syncEducationUnitStatus deactivates the circle', function () {
        $sub = makeIndividualSub($this->student, $this->teacher);
        $circle = $sub->createIndividualCircle();
        expect($circle->is_active)->toBeTrue();

        // Flip subscription status to CANCELLED, then call syncEducationUnitStatus
        // explicitly (in production this is wired through observers).
        $sub->status = SessionSubscriptionStatus::CANCELLED;
        $sub->syncEducationUnitStatus();

        expect($circle->fresh()->is_active)->toBeFalse();
    });
});

describe('subscription_type=group does NOT auto-create an individual circle', function () {
    it('CL7 — group subscription does NOT spawn a QuranIndividualCircle from createIndividualCircle()', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create([
                'subscription_type' => QuranSubscription::SUBSCRIPTION_TYPE_GROUP,
            ]);

        $result = $sub->createIndividualCircle();

        expect($result)->toBeNull();
        expect(QuranIndividualCircle::where('subscription_id', $sub->id)->count())->toBe(0);
    });
});
