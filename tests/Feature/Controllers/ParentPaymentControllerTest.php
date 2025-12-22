<?php

use App\Models\Academy;
use App\Models\Payment;
use App\Models\User;

describe('ParentPaymentController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('index', function () {
        it('returns payments for linked children', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $parentProfile->students()->attach($student->studentProfileUnscoped->id);

            Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
            ]);

            $response = $this->actingAs($parent)->get(route('parent.payments.index', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });

        it('denies access to non-parent users', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($student)->get(route('parent.payments.index', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(403);
        });
    });

    describe('show', function () {
        it('shows payment details for linked child payment', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $parentProfile->students()->attach($student->studentProfileUnscoped->id);

            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
            ]);

            $response = $this->actingAs($parent)->get(route('parent.payments.show', [
                'subdomain' => $this->academy->subdomain,
                'payment' => $payment->id,
            ]));

            $response->assertStatus(200);
        });

        it('denies access to non-linked child payment', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            // No link

            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
            ]);

            $response = $this->actingAs($parent)->get(route('parent.payments.show', [
                'subdomain' => $this->academy->subdomain,
                'payment' => $payment->id,
            ]));

            $response->assertStatus(403);
        });
    });
});
