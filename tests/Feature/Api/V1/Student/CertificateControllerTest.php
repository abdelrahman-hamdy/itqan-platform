<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);

    $this->gradeLevel = AcademicGradeLevel::create([
        'academy_id' => $this->academy->id,
        'name' => 'Grade 1',
        'is_active' => true,
    ]);

    $this->student = User::factory()
        ->student()
        ->forAcademy($this->academy)
        ->create();

    $this->student->refresh();
});

describe('Certificate Index', function () {
    it('returns all certificates for student', function () {
        Sanctum::actingAs($this->student, ['*']);

        Certificate::factory()->create([
            'user_id' => $this->student->id,
            'type' => 'quran',
            'status' => 'issued',
            'issued_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/student/certificates', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'certificates',
                    'total',
                ],
            ]);
    });

    it('filters certificates by type', function () {
        Sanctum::actingAs($this->student, ['*']);

        Certificate::factory()->create([
            'user_id' => $this->student->id,
            'type' => 'quran',
            'status' => 'issued',
            'issued_at' => now(),
        ]);

        Certificate::factory()->create([
            'user_id' => $this->student->id,
            'type' => 'academic',
            'status' => 'issued',
            'issued_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/student/certificates?type=quran', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $certificates = $response->json('data.certificates');
        foreach ($certificates as $certificate) {
            expect($certificate['type'])->toBe('quran');
        }
    });

    it('only returns issued certificates', function () {
        Sanctum::actingAs($this->student, ['*']);

        Certificate::factory()->create([
            'user_id' => $this->student->id,
            'type' => 'quran',
            'status' => 'issued',
            'issued_at' => now(),
        ]);

        Certificate::factory()->create([
            'user_id' => $this->student->id,
            'type' => 'academic',
            'status' => 'draft',
        ]);

        $response = $this->getJson('/api/v1/student/certificates', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $certificates = $response->json('data.certificates');
        expect(count($certificates))->toBe(1);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/student/certificates', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(401);
    });
});

describe('Show Certificate', function () {
    it('returns certificate details', function () {
        Sanctum::actingAs($this->student, ['*']);

        $certificate = Certificate::factory()->create([
            'user_id' => $this->student->id,
            'type' => 'quran',
            'status' => 'issued',
            'issued_at' => now(),
            'certificate_number' => 'CERT-2025-001',
        ]);

        $response = $this->getJson("/api/v1/student/certificates/{$certificate->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'certificate' => [
                        'id',
                        'type',
                        'title',
                        'certificate_number',
                        'issued_at',
                        'status',
                        'issuer',
                        'recipient',
                    ],
                ],
            ]);
    });

    it('returns 404 for non-existent certificate', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson('/api/v1/student/certificates/99999', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('prevents accessing another student certificate', function () {
        $otherStudent = User::factory()
            ->student()
            ->forAcademy($this->academy)
            ->create();

        $certificate = Certificate::factory()->create([
            'user_id' => $otherStudent->id,
            'type' => 'quran',
            'status' => 'issued',
            'issued_at' => now(),
        ]);

        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson("/api/v1/student/certificates/{$certificate->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });
});

describe('Download Certificate', function () {
    it('generates download URL for certificate', function () {
        Sanctum::actingAs($this->student, ['*']);

        // Create a fake PDF file
        Storage::disk('public')->put('certificates/test-cert.pdf', 'fake certificate content');

        $certificate = Certificate::factory()->create([
            'user_id' => $this->student->id,
            'type' => 'quran',
            'status' => 'issued',
            'issued_at' => now(),
            'file_path' => 'certificates/test-cert.pdf',
            'certificate_number' => 'CERT-2025-001',
        ]);

        $response = $this->getJson("/api/v1/student/certificates/{$certificate->id}/download", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'download_url',
                    'filename',
                    'expires_at',
                ],
            ]);
    });

    it('returns error when file not found', function () {
        Sanctum::actingAs($this->student, ['*']);

        $certificate = Certificate::factory()->create([
            'user_id' => $this->student->id,
            'type' => 'quran',
            'status' => 'issued',
            'issued_at' => now(),
            'file_path' => 'certificates/non-existent.pdf',
        ]);

        $response = $this->getJson("/api/v1/student/certificates/{$certificate->id}/download", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'code' => 'FILE_NOT_FOUND',
            ]);
    });

    it('prevents downloading another student certificate', function () {
        $otherStudent = User::factory()
            ->student()
            ->forAcademy($this->academy)
            ->create();

        Storage::disk('public')->put('certificates/other-cert.pdf', 'fake content');

        $certificate = Certificate::factory()->create([
            'user_id' => $otherStudent->id,
            'type' => 'quran',
            'status' => 'issued',
            'issued_at' => now(),
            'file_path' => 'certificates/other-cert.pdf',
        ]);

        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson("/api/v1/student/certificates/{$certificate->id}/download", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });
});
