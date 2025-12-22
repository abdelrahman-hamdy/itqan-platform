<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\Certificate;
use App\Models\ParentProfile;
use App\Models\ParentStudentRelationship;
use App\Models\StudentProfile;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses()->group('api', 'parent-api', 'certificates');

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);

    // Create parent user with profile
    $this->parentUser = User::factory()->parent()->forAcademy($this->academy)->create();
    $this->parentProfile = ParentProfile::factory()->create([
        'user_id' => $this->parentUser->id,
        'academy_id' => $this->academy->id,
    ]);

    // Create student with user
    $this->studentUser = User::factory()->student()->forAcademy($this->academy)->create();
    $this->gradeLevel = AcademicGradeLevel::factory()->create([
        'academy_id' => $this->academy->id,
    ]);
    $this->student = StudentProfile::factory()->create([
        'user_id' => $this->studentUser->id,
        'grade_level_id' => $this->gradeLevel->id,
    ]);

    // Link student to parent
    ParentStudentRelationship::create([
        'parent_id' => $this->parentProfile->id,
        'student_id' => $this->student->id,
        'relationship_type' => 'father',
    ]);
});

describe('index (list all certificates)', function () {
    it('returns empty list when no certificates exist', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->getJson('/api/v1/parent/certificates', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'certificates',
                    'pagination',
                ],
            ])
            ->assertJson([
                'data' => [
                    'certificates' => [],
                ],
            ]);
    });

    it('returns certificates for linked children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        Certificate::factory()->create([
            'student_id' => $this->student->id,
            'academy_id' => $this->academy->id,
            'title' => 'Quran Memorization Certificate',
            'type' => 'completion',
            'issued_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/parent/certificates', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.certificates')
            ->assertJsonStructure([
                'data' => [
                    'certificates' => [
                        '*' => [
                            'id',
                            'child_id',
                            'child_name',
                            'title',
                            'type',
                            'description',
                            'certificate_number',
                            'issued_at',
                            'expires_at',
                            'is_expired',
                            'thumbnail_url',
                            'created_at',
                        ],
                    ],
                ],
            ]);
    });

    it('filters certificates by child_id', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        // Create another child
        $student2User = User::factory()->student()->forAcademy($this->academy)->create();
        $student2 = StudentProfile::factory()->create([
            'user_id' => $student2User->id,
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        ParentStudentRelationship::create([
            'parent_id' => $this->parentProfile->id,
            'student_id' => $student2->id,
            'relationship_type' => 'mother',
        ]);

        Certificate::factory()->create([
            'student_id' => $this->student->id,
            'academy_id' => $this->academy->id,
        ]);

        Certificate::factory()->create([
            'student_id' => $student2->id,
            'academy_id' => $this->academy->id,
        ]);

        $response = $this->getJson('/api/v1/parent/certificates?child_id=' . $this->student->id, [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.certificates');
    });

    it('paginates results', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        Certificate::factory()->count(20)->create([
            'student_id' => $this->student->id,
            'academy_id' => $this->academy->id,
        ]);

        $response = $this->getJson('/api/v1/parent/certificates?per_page=10', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data.certificates')
            ->assertJsonStructure([
                'data' => [
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                        'total_pages',
                        'has_more',
                    ],
                ],
            ]);
    });

    it('does not show certificates of non-linked children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $otherStudent = StudentProfile::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        Certificate::factory()->create([
            'student_id' => $otherStudent->id,
            'academy_id' => $this->academy->id,
        ]);

        $response = $this->getJson('/api/v1/parent/certificates', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.certificates');
    });

    it('sorts certificates by issued date descending', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $cert1 = Certificate::factory()->create([
            'student_id' => $this->student->id,
            'academy_id' => $this->academy->id,
            'issued_at' => now()->subDays(10),
        ]);

        $cert2 = Certificate::factory()->create([
            'student_id' => $this->student->id,
            'academy_id' => $this->academy->id,
            'issued_at' => now()->subDays(2),
        ]);

        $response = $this->getJson('/api/v1/parent/certificates', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $certificates = $response->json('data.certificates');
        expect($certificates[0]['id'])->toBe($cert2->id);
    });

    it('indicates if certificate is expired', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        Certificate::factory()->create([
            'student_id' => $this->student->id,
            'academy_id' => $this->academy->id,
            'issued_at' => now()->subYear(),
            'expires_at' => now()->subMonth(),
        ]);

        $response = $this->getJson('/api/v1/parent/certificates', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $certificates = $response->json('data.certificates');
        expect($certificates[0]['is_expired'])->toBeTrue();
    });
});

describe('show (get specific certificate)', function () {
    it('returns certificate details for linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $certificate = Certificate::factory()->create([
            'student_id' => $this->student->id,
            'academy_id' => $this->academy->id,
            'title' => 'Excellence Award',
            'type' => 'achievement',
            'certificate_number' => 'CERT-2024-001',
            'issued_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/parent/certificates/{$certificate->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'certificate' => [
                        'id',
                        'child',
                        'title',
                        'type',
                        'description',
                        'certificate_number',
                        'issued_at',
                        'expires_at',
                        'is_expired',
                        'issuer',
                        'thumbnail_url',
                        'download_url',
                        'verification_url',
                        'metadata',
                        'certificatable',
                        'created_at',
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'certificate' => [
                        'id' => $certificate->id,
                        'title' => 'Excellence Award',
                        'certificate_number' => 'CERT-2024-001',
                    ],
                ],
            ]);
    });

    it('returns 404 for certificate of non-linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $otherStudent = StudentProfile::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        $certificate = Certificate::factory()->create([
            'student_id' => $otherStudent->id,
            'academy_id' => $this->academy->id,
        ]);

        $response = $this->getJson("/api/v1/parent/certificates/{$certificate->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('returns 404 for non-existent certificate', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->getJson('/api/v1/parent/certificates/99999', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });
});

describe('childCertificates (get certificates for specific child)', function () {
    it('returns certificates for specific linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        Certificate::factory()->count(3)->create([
            'student_id' => $this->student->id,
            'academy_id' => $this->academy->id,
        ]);

        $response = $this->getJson("/api/v1/parent/children/{$this->student->id}/certificates", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.certificates')
            ->assertJsonStructure([
                'data' => [
                    'child' => [
                        'id',
                        'name',
                    ],
                    'certificates' => [
                        '*' => [
                            'id',
                            'title',
                            'type',
                            'description',
                            'certificate_number',
                            'issued_at',
                            'expires_at',
                            'is_expired',
                            'thumbnail_url',
                            'download_url',
                        ],
                    ],
                    'pagination',
                ],
            ])
            ->assertJson([
                'data' => [
                    'child' => [
                        'id' => $this->student->id,
                    ],
                ],
            ]);
    });

    it('returns 404 for non-linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $otherStudent = StudentProfile::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        $response = $this->getJson("/api/v1/parent/children/{$otherStudent->id}/certificates", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('paginates child certificates', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        Certificate::factory()->count(20)->create([
            'student_id' => $this->student->id,
            'academy_id' => $this->academy->id,
        ]);

        $response = $this->getJson("/api/v1/parent/children/{$this->student->id}/certificates?per_page=10", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data.certificates')
            ->assertJsonStructure([
                'data' => [
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                        'total_pages',
                        'has_more',
                    ],
                ],
            ]);
    });
});

describe('authorization', function () {
    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/parent/certificates', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(401);
    });

    it('returns 404 when parent has no profile', function () {
        $userWithoutProfile = User::factory()->parent()->forAcademy($this->academy)->create();
        Sanctum::actingAs($userWithoutProfile, ['*']);

        $response = $this->getJson('/api/v1/parent/certificates', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error_code' => 'PARENT_PROFILE_NOT_FOUND',
            ]);
    });
});
