<?php

use App\Models\Academy;
use App\Models\ParentProfile;
use App\Models\Payment;
use App\Models\User;
use App\Policies\PaymentPolicy;

describe('PaymentPolicy', function () {
    beforeEach(function () {
        $this->policy = new PaymentPolicy();
        $this->academy = Academy::factory()->create();
    });

    describe('viewAny', function () {
        it('allows super admin to view any payments', function () {
            $user = User::factory()->superAdmin()->create();
            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows admin to view any payments', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();
            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows supervisor to view any payments', function () {
            $user = User::factory()->supervisor()->forAcademy($this->academy)->create();
            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows student to view any payments', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('denies teacher from viewing any payments', function () {
            $user = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            expect($this->policy->viewAny($user))->toBeFalse();
        });
    });

    describe('view', function () {
        it('allows admin to view payment in same academy', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
            ]);

            expect($this->policy->view($admin, $payment))->toBeTrue();
        });

        it('allows user to view own payment', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
            ]);

            expect($this->policy->view($student, $payment))->toBeTrue();
        });

        it('denies other user from viewing payment', function () {
            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();

            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student1->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
            ]);

            expect($this->policy->view($student2, $payment))->toBeFalse();
        });

        it('allows parent to view child payment', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            // Link parent to student
            $parentProfile->students()->attach($student->studentProfileUnscoped->id);

            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
            ]);

            expect($this->policy->view($parent, $payment))->toBeTrue();
        });

        it('denies parent from viewing non-child payment', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            // No link between parent and student
            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
            ]);

            expect($this->policy->view($parent, $payment))->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows admin to create payments', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows super admin to create payments', function () {
            $user = User::factory()->superAdmin()->create();
            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows student to create payments', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows parent to create payments', function () {
            $user = User::factory()->parent()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeTrue();
        });

        it('denies supervisor from creating payments', function () {
            $user = User::factory()->supervisor()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeFalse();
        });

        it('denies teacher from creating payments', function () {
            $user = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeFalse();
        });
    });

    describe('update', function () {
        it('allows admin to update payment in same academy', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
            ]);

            expect($this->policy->update($admin, $payment))->toBeTrue();
        });

        it('allows super admin to update any payment', function () {
            $superAdmin = User::factory()->superAdmin()->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
            ]);

            expect($this->policy->update($superAdmin, $payment))->toBeTrue();
        });

        it('denies student from updating payment', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
            ]);

            expect($this->policy->update($student, $payment))->toBeFalse();
        });

        it('denies supervisor from updating payment', function () {
            $supervisor = User::factory()->supervisor()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
            ]);

            expect($this->policy->update($supervisor, $payment))->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows super admin to delete payment', function () {
            $superAdmin = User::factory()->superAdmin()->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
            ]);

            expect($this->policy->delete($superAdmin, $payment))->toBeTrue();
        });

        it('denies admin from deleting payment', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
            ]);

            expect($this->policy->delete($admin, $payment))->toBeFalse();
        });
    });

    describe('refund', function () {
        it('allows admin to refund completed payment', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
            ]);

            expect($this->policy->refund($admin, $payment))->toBeTrue();
        });

        it('denies refund for already refunded payment', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
                'refunded_at' => now(),
            ]);

            expect($this->policy->refund($admin, $payment))->toBeFalse();
        });

        it('denies refund for pending payment', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'pending',
            ]);

            expect($this->policy->refund($admin, $payment))->toBeFalse();
        });

        it('denies supervisor from refunding payment', function () {
            $supervisor = User::factory()->supervisor()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
            ]);

            expect($this->policy->refund($supervisor, $payment))->toBeFalse();
        });

        it('denies student from refunding payment', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
            ]);

            expect($this->policy->refund($student, $payment))->toBeFalse();
        });
    });

    describe('downloadReceipt', function () {
        it('delegates to view permission', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
            ]);

            expect($this->policy->downloadReceipt($student, $payment))->toBeTrue();
        });

        it('allows parent to download child receipt', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            // Link parent to student
            $parentProfile->students()->attach($student->studentProfileUnscoped->id);

            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
            ]);

            expect($this->policy->downloadReceipt($parent, $payment))->toBeTrue();
        });
    });
});
