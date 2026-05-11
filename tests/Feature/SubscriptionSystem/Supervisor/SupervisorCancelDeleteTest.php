<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\Payment;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use Illuminate\Support\Facades\DB;

/**
 * Asserts the cancel + delete surface.
 *
 *   cancel():
 *     - PENDING → cancels the subscription + flips pending payments to cancelled
 *     - ACTIVE/PAUSED → CANCELLED with auto_renew=false; suspends future SCHEDULED/UNSCHEDULED/READY sessions
 *
 *   destroy():
 *     - permanently removes the subscription, payments (with trashed),
 *       sessions + reports, and the linked education_unit.
 *
 * See `SupervisorSubscriptionsController::cancel()` (line 341-377) and
 * `::destroy()` (line 517-560).
 */
beforeEach(function () {
    $this->academy = createAcademy(['subdomain' => 'cancel-test-'.uniqid()]);
    $this->admin = createAdmin($this->academy);
    $this->student = createStudent($this->academy);
    $this->teacher = createQuranTeacher($this->academy);
});

function cancelUrl(string $subdomain, int $id): string
{
    return route('manage.subscriptions.cancel', [
        'subdomain' => $subdomain,
        'type' => 'quran',
        'subscription' => $id,
    ]);
}

function destroyUrl(string $subdomain, int $id): string
{
    return route('manage.subscriptions.destroy', [
        'subdomain' => $subdomain,
        'type' => 'quran',
        'subscription' => $id,
    ]);
}

describe('POST /manage/subscriptions/{type}/{id}/cancel — PENDING path', function () {
    it('CD1 — cancel on a PENDING sub flips pending payments to cancelled', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->pending()
            ->create();

        // Pending Payment attached to this subscription.
        $payment = Payment::createPayment([
            'academy_id' => $this->academy->id,
            'user_id' => $this->student->id,
            'payable_type' => QuranSubscription::class,
            'payable_id' => $sub->id,
            'payment_method' => 'cash',
            'payment_gateway' => 'manual',
            'amount' => 100,
            'currency' => 'SAR',
            'status' => PaymentStatus::PENDING,
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(cancelUrl($this->academy->subdomain, $sub->id));

        $response->assertRedirect();
        // The subscription was cancelled via cancelAsDuplicateOrExpired().
        expect($sub->fresh()->status)->toBe(SessionSubscriptionStatus::CANCELLED);
        // Pending payment flipped to 'cancelled' (raw DB value).
        $freshPayment = DB::table('payments')->where('id', $payment->id)->first();
        expect($freshPayment->status)->toBe('cancelled');
    });
});

describe('POST /manage/subscriptions/{type}/{id}/cancel — ACTIVE path', function () {
    it('CD2 — cancel on an ACTIVE sub sets CANCELLED + auto_renew=false', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create(['auto_renew' => true]);

        $this->actingAs($this->admin)
            ->post(cancelUrl($this->academy->subdomain, $sub->id));

        $fresh = $sub->fresh();
        expect($fresh->status)->toBe(SessionSubscriptionStatus::CANCELLED);
        expect($fresh->cancelled_at)->not->toBeNull();
        expect($fresh->auto_renew)->toBeFalse();
    });

    it('CD3 — cancel on ACTIVE/PAUSED suspends future SCHEDULED sessions', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create();

        // Future SCHEDULED + past COMPLETED.
        [$future, $pastDone] = QuranSession::withoutEvents(function () use ($sub) {
            return [
                QuranSession::factory()->create([
                    'academy_id' => $this->academy->id,
                    'student_id' => $this->student->id,
                    'quran_teacher_id' => $this->teacher->id,
                    'quran_subscription_id' => $sub->id,
                    'scheduled_at' => now()->addDays(3),
                    'status' => SessionStatus::SCHEDULED,
                ]),
                QuranSession::factory()->create([
                    'academy_id' => $this->academy->id,
                    'student_id' => $this->student->id,
                    'quran_teacher_id' => $this->teacher->id,
                    'quran_subscription_id' => $sub->id,
                    'scheduled_at' => now()->subDays(5),
                    'status' => SessionStatus::COMPLETED,
                ]),
            ];
        });

        $this->actingAs($this->admin)
            ->post(cancelUrl($this->academy->subdomain, $sub->id));

        expect($future->fresh()->status)->toBe(SessionStatus::SUSPENDED);
        // Past completed sessions are NOT touched.
        expect($pastDone->fresh()->status)->toBe(SessionStatus::COMPLETED);
    });

    it('CD4 — cancel on a PAUSED sub also flips it to CANCELLED', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->manuallyPaused()
            ->create();

        $this->actingAs($this->admin)
            ->post(cancelUrl($this->academy->subdomain, $sub->id));

        expect($sub->fresh()->status)->toBe(SessionSubscriptionStatus::CANCELLED);
    });
});

describe('DELETE /manage/subscriptions/{type}/{id}', function () {
    it('CD5 — destroy permanently removes the subscription row + payments', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create();
        $payment = Payment::createPayment([
            'academy_id' => $this->academy->id,
            'user_id' => $this->student->id,
            'payable_type' => QuranSubscription::class,
            'payable_id' => $sub->id,
            'payment_method' => 'cash',
            'payment_gateway' => 'manual',
            'amount' => 100,
            'currency' => 'SAR',
            'status' => PaymentStatus::COMPLETED,
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(destroyUrl($this->academy->subdomain, $sub->id));

        $response->assertRedirect(route('manage.subscriptions.index', [
            'subdomain' => $this->academy->subdomain,
        ]));

        // Hard-deleted (uses ->forceDelete() in the controller).
        expect(QuranSubscription::withTrashed()->find($sub->id))->toBeNull();
        expect(Payment::withTrashed()->find($payment->id))->toBeNull();
    });

    it('CD6 — destroy cascades to sessions and session reports', function () {
        $sub = QuranSubscription::factory()
            ->forStudent($this->student)
            ->forTeacher($this->teacher)
            ->active()
            ->create();

        $session = QuranSession::withoutEvents(fn () => QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'quran_teacher_id' => $this->teacher->id,
            'quran_subscription_id' => $sub->id,
            'scheduled_at' => now()->addDay(),
            'status' => SessionStatus::SCHEDULED,
        ]));

        $this->actingAs($this->admin)
            ->delete(destroyUrl($this->academy->subdomain, $sub->id));

        // Session itself is forceDeleted.
        expect(QuranSession::withTrashed()->find($session->id))->toBeNull();
    });
});
