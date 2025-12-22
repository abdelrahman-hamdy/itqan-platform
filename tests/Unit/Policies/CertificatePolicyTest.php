<?php

use App\Models\Academy;
use App\Models\Certificate;
use App\Models\User;
use App\Policies\CertificatePolicy;

describe('CertificatePolicy', function () {
    beforeEach(function () {
        $this->policy = new CertificatePolicy();
        $this->academy = Academy::factory()->create();
    });

    describe('viewAny', function () {
        it('allows any user to view certificates', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows admin to view any certificates', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();
            expect($this->policy->viewAny($user))->toBeTrue();
        });
    });

    describe('view', function () {
        it('allows student to view own certificate', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            expect($this->policy->view($student, $certificate))->toBeTrue();
        });

        it('allows teacher to view certificate they issued', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            expect($this->policy->view($teacher, $certificate))->toBeTrue();
        });

        it('allows admin to view any certificate in academy', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            expect($this->policy->view($admin, $certificate))->toBeTrue();
        });

        it('allows super admin to view any certificate', function () {
            $superAdmin = User::factory()->superAdmin()->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            expect($this->policy->view($superAdmin, $certificate))->toBeTrue();
        });

        it('denies other student from viewing certificate', function () {
            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student1->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            expect($this->policy->view($student2, $certificate))->toBeFalse();
        });

        it('allows supervisor in same academy to view certificate', function () {
            $supervisor = User::factory()->supervisor()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            expect($this->policy->view($supervisor, $certificate))->toBeTrue();
        });
    });

    describe('create', function () {
        it('allows quran teacher to create certificates', function () {
            $user = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows academic teacher to create certificates', function () {
            $user = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows admin to create certificates', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows super admin to create certificates', function () {
            $user = User::factory()->superAdmin()->create();
            expect($this->policy->create($user))->toBeTrue();
        });

        it('denies student from creating certificates', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeFalse();
        });

        it('denies supervisor from creating certificates', function () {
            $user = User::factory()->supervisor()->forAcademy($this->academy)->create();
            expect($this->policy->create($user))->toBeFalse();
        });
    });

    describe('update', function () {
        it('allows admin to update certificate', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            expect($this->policy->update($admin, $certificate))->toBeTrue();
        });

        it('allows super admin to update certificate', function () {
            $superAdmin = User::factory()->superAdmin()->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            expect($this->policy->update($superAdmin, $certificate))->toBeTrue();
        });

        it('denies teacher from updating certificate', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            expect($this->policy->update($teacher, $certificate))->toBeFalse();
        });

        it('denies supervisor from updating certificate', function () {
            $supervisor = User::factory()->supervisor()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            expect($this->policy->update($supervisor, $certificate))->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows admin to delete certificate', function () {
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            expect($this->policy->delete($admin, $certificate))->toBeTrue();
        });

        it('denies teacher from deleting certificate', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            expect($this->policy->delete($teacher, $certificate))->toBeFalse();
        });
    });

    describe('restore', function () {
        it('allows only super admin to restore certificate', function () {
            $superAdmin = User::factory()->superAdmin()->create();
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            expect($this->policy->restore($superAdmin, $certificate))->toBeTrue();
            expect($this->policy->restore($admin, $certificate))->toBeFalse();
        });
    });

    describe('forceDelete', function () {
        it('allows only super admin to force delete certificate', function () {
            $superAdmin = User::factory()->superAdmin()->create();
            $admin = User::factory()->admin()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            expect($this->policy->forceDelete($superAdmin, $certificate))->toBeTrue();
            expect($this->policy->forceDelete($admin, $certificate))->toBeFalse();
        });
    });

    describe('download', function () {
        it('delegates to view permission', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            expect($this->policy->download($student, $certificate))->toBeTrue();
            expect($this->policy->download($teacher, $certificate))->toBeTrue();
        });
    });
});
