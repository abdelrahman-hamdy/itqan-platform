<?php

use App\Filament\Teacher\Resources\CertificateResource;
use App\Models\Academy;
use App\Models\Certificate;
use App\Models\QuranTeacherProfile;
use App\Models\User;

describe('CertificateResource', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $this->teacherProfile = QuranTeacherProfile::factory()->create([
            'user_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);
    });

    describe('getEloquentQuery', function () {
        it('scopes certificates to current teacher only', function () {
            $this->actingAs($this->teacher);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            // Create certificate for this teacher
            $ownCertificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $this->teacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Test Certificate',
                'issued_at' => now(),
            ]);

            // Create certificate for another teacher
            $otherTeacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $otherCertificate = Certificate::create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $otherTeacher->id,
                'certificate_code' => 'CERT-' . uniqid(),
                'certificate_type' => 'completion',
                'title' => 'Other Certificate',
                'issued_at' => now(),
            ]);

            $query = CertificateResource::getEloquentQuery();

            expect($query->pluck('id')->toArray())->toContain($ownCertificate->id);
            expect($query->pluck('id')->toArray())->not->toContain($otherCertificate->id);
        });
    });

    describe('navigation', function () {
        it('has correct navigation icon', function () {
            expect(CertificateResource::getNavigationIcon())->toBe('heroicon-o-academic-cap');
        });
    });
});
