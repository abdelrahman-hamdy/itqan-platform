<?php

use App\Models\Academy;
use App\Models\Certificate;
use App\Models\ParentProfile;
use App\Models\Payment;
use App\Models\StudentProfile;
use App\Models\User;
use App\Policies\ParentPolicy;

describe('ParentPolicy', function () {
    beforeEach(function () {
        $this->policy = new ParentPolicy();
        $this->academy = Academy::factory()->create();
    });

    describe('viewChild', function () {
        it('allows parent to view linked child', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = $student->studentProfileUnscoped;

            // Link parent to student
            $parentProfile->students()->attach($studentProfile->id);

            expect($this->policy->viewChild($parent, $studentProfile))->toBeTrue();
        });

        it('denies parent from viewing unlinked child', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = $student->studentProfileUnscoped;

            // No link
            expect($this->policy->viewChild($parent, $studentProfile))->toBeFalse();
        });

        it('denies non-parent user from viewing child', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = $student->studentProfileUnscoped;

            expect($this->policy->viewChild($teacher, $studentProfile))->toBeFalse();
        });

        it('denies parent from viewing child in different academy', function () {
            $otherAcademy = Academy::factory()->create();
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($otherAcademy)->create();
            $studentProfile = $student->studentProfileUnscoped;

            expect($this->policy->viewChild($parent, $studentProfile))->toBeFalse();
        });
    });

    describe('viewChildSubscriptions', function () {
        it('delegates to viewChild permission', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = $student->studentProfileUnscoped;

            // Link parent to student
            $parentProfile->students()->attach($studentProfile->id);

            expect($this->policy->viewChildSubscriptions($parent, $studentProfile))->toBeTrue();
        });

        it('denies for unlinked child', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = $student->studentProfileUnscoped;

            expect($this->policy->viewChildSubscriptions($parent, $studentProfile))->toBeFalse();
        });
    });

    describe('viewChildSessions', function () {
        it('delegates to viewChild permission', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = $student->studentProfileUnscoped;

            // Link parent to student
            $parentProfile->students()->attach($studentProfile->id);

            expect($this->policy->viewChildSessions($parent, $studentProfile))->toBeTrue();
        });
    });

    describe('viewChildPayments', function () {
        it('delegates to viewChild permission', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = $student->studentProfileUnscoped;

            // Link parent to student
            $parentProfile->students()->attach($studentProfile->id);

            expect($this->policy->viewChildPayments($parent, $studentProfile))->toBeTrue();
        });
    });

    describe('viewChildCertificates', function () {
        it('delegates to viewChild permission', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = $student->studentProfileUnscoped;

            // Link parent to student
            $parentProfile->students()->attach($studentProfile->id);

            expect($this->policy->viewChildCertificates($parent, $studentProfile))->toBeTrue();
        });
    });

    describe('viewChildQuizResults', function () {
        it('delegates to viewChild permission', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = $student->studentProfileUnscoped;

            // Link parent to student
            $parentProfile->students()->attach($studentProfile->id);

            expect($this->policy->viewChildQuizResults($parent, $studentProfile))->toBeTrue();
        });
    });

    describe('viewChildHomework', function () {
        it('delegates to viewChild permission', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = $student->studentProfileUnscoped;

            // Link parent to student
            $parentProfile->students()->attach($studentProfile->id);

            expect($this->policy->viewChildHomework($parent, $studentProfile))->toBeTrue();
        });
    });

    describe('viewChildReports', function () {
        it('delegates to viewChild permission', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = $student->studentProfileUnscoped;

            // Link parent to student
            $parentProfile->students()->attach($studentProfile->id);

            expect($this->policy->viewChildReports($parent, $studentProfile))->toBeTrue();
        });
    });

    describe('viewCertificate', function () {
        it('allows parent to view child certificate', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = $student->studentProfileUnscoped;
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            // Link parent to student
            $parentProfile->students()->attach($studentProfile->id);

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            expect($this->policy->viewCertificate($parent, $certificate))->toBeTrue();
        });

        it('denies parent from viewing non-child certificate', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            // No link between parent and student
            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            expect($this->policy->viewCertificate($parent, $certificate))->toBeFalse();
        });

        it('denies non-parent from viewing certificate', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            expect($this->policy->viewCertificate($teacher, $certificate))->toBeFalse();
        });
    });

    describe('downloadCertificate', function () {
        it('delegates to viewCertificate permission', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = $student->studentProfileUnscoped;
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            // Link parent to student
            $parentProfile->students()->attach($studentProfile->id);

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            expect($this->policy->downloadCertificate($parent, $certificate))->toBeTrue();
        });
    });

    describe('viewPayment', function () {
        it('allows parent to view child payment', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = $student->studentProfileUnscoped;

            // Link parent to student
            $parentProfile->students()->attach($studentProfile->id);

            $payment = Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => 100,
                'currency' => 'SAR',
                'payment_method' => 'card',
                'status' => 'completed',
            ]);

            expect($this->policy->viewPayment($parent, $payment))->toBeTrue();
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

            expect($this->policy->viewPayment($parent, $payment))->toBeFalse();
        });
    });

    describe('downloadReceipt', function () {
        it('delegates to viewPayment permission', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = $student->studentProfileUnscoped;

            // Link parent to student
            $parentProfile->students()->attach($studentProfile->id);

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

    describe('update', function () {
        it('always denies parent from updating student', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = $student->studentProfileUnscoped;

            // Link parent to student
            $parentProfile->students()->attach($studentProfile->id);

            expect($this->policy->update($parent, $studentProfile))->toBeFalse();
        });
    });

    describe('delete', function () {
        it('always denies parent from deleting student', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = $student->studentProfileUnscoped;

            // Link parent to student
            $parentProfile->students()->attach($studentProfile->id);

            expect($this->policy->delete($parent, $studentProfile))->toBeFalse();
        });
    });

    describe('create', function () {
        it('always denies parent from creating', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();

            expect($this->policy->create($parent))->toBeFalse();
        });
    });
});
