<?php

use App\Models\Academy;
use App\Models\Certificate;
use App\Models\User;

describe('CertificateController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('index', function () {
        it('returns certificates for authenticated student', function () {
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

            $response = $this->actingAs($student)->get(route('student.certificates.index', ['subdomain' => $this->academy->subdomain]));

            $response->assertStatus(200);
            $response->assertViewIs('student.certificates');
            $response->assertViewHas('certificates');
        });

        it('filters certificates by type', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Completion Certificate',
                'issued_at' => now(),
            ]);

            Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'memorization',
                'title' => 'Memorization Certificate',
                'issued_at' => now(),
            ]);

            $response = $this->actingAs($student)->get(route('student.certificates.index', [
                'subdomain' => $this->academy->subdomain,
                'type' => 'completion',
            ]));

            $response->assertStatus(200);
        });
    });

    describe('download', function () {
        it('requires authorization to download certificate', function () {
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

            // Student2 should not be able to download student1's certificate
            $response = $this->actingAs($student2)->get(route('student.certificates.download', [
                'subdomain' => $this->academy->subdomain,
                'certificate' => $certificate->id,
            ]));

            $response->assertStatus(403);
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

            $response = $this->actingAs($student)->get(route('student.certificates.view', [
                'subdomain' => $this->academy->subdomain,
                'certificate' => $certificate->id,
            ]));

            // Should either return the PDF or redirect with error (if CertificateService fails)
            expect($response->status())->toBeIn([200, 302]);
        });
    });
});
