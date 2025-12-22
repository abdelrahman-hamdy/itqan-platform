<?php

use App\Models\Academy;
use App\Models\Certificate;
use App\Models\User;

describe('ParentCertificateController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('index', function () {
        it('returns certificates for linked children', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $parentProfile->students()->attach($student->studentProfileUnscoped->id);

            Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            $response = $this->actingAs($parent)->get(route('parent.certificates.index', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });

        it('denies access to non-parent users', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($student)->get(route('parent.certificates.index', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(403);
        });
    });

    describe('show', function () {
        it('shows certificate details for linked child', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            $parentProfile->students()->attach($student->studentProfileUnscoped->id);

            $certificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            $response = $this->actingAs($parent)->get(route('parent.certificates.show', [
                'subdomain' => $this->academy->subdomain,
                'certificate' => $certificate->id,
            ]));

            $response->assertStatus(200);
        });
    });
});
