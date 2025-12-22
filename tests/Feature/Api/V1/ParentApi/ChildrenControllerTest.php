<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\ParentProfile;
use App\Models\ParentStudentRelationship;
use App\Models\StudentProfile;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\assertDatabaseHas;

uses()->group('api', 'parent-api', 'children');

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

    // Create grade level for students
    $this->gradeLevel = AcademicGradeLevel::factory()->create([
        'academy_id' => $this->academy->id,
    ]);
});

describe('index (list children)', function () {
    it('returns empty list when parent has no linked children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->getJson('/api/v1/parent/children', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'children',
                    'total',
                ],
            ])
            ->assertJson([
                'data' => [
                    'total' => 0,
                    'children' => [],
                ],
            ]);
    });

    it('returns list of linked children with details', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        // Create students and link to parent
        $student1 = StudentProfile::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
        ]);
        $student2 = StudentProfile::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        ParentStudentRelationship::create([
            'parent_id' => $this->parentProfile->id,
            'student_id' => $student1->id,
            'relationship_type' => 'father',
        ]);

        ParentStudentRelationship::create([
            'parent_id' => $this->parentProfile->id,
            'student_id' => $student2->id,
            'relationship_type' => 'mother',
        ]);

        $response = $this->getJson('/api/v1/parent/children', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'total' => 2,
                ],
            ])
            ->assertJsonCount(2, 'data.children')
            ->assertJsonStructure([
                'data' => [
                    'children' => [
                        '*' => [
                            'id',
                            'name',
                            'student_code',
                            'avatar',
                            'grade_level',
                            'relationship',
                            'email',
                            'phone',
                            'linked_at',
                        ],
                    ],
                ],
            ]);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/parent/children', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(401);
    });

    it('returns 404 when parent has no profile', function () {
        $userWithoutProfile = User::factory()->parent()->forAcademy($this->academy)->create();
        Sanctum::actingAs($userWithoutProfile, ['*']);

        $response = $this->getJson('/api/v1/parent/children', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error_code' => 'PARENT_PROFILE_NOT_FOUND',
            ]);
    });
});

describe('link (link child by student code)', function () {
    it('successfully links a child using valid student code', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $student = StudentProfile::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        $response = $this->postJson('/api/v1/parent/children/link', [
            'student_code' => $student->student_code,
            'relationship_type' => 'father',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'child' => [
                        'id',
                        'name',
                        'student_code',
                        'grade_level',
                    ],
                ],
            ]);

        assertDatabaseHas('parent_student_relationships', [
            'parent_id' => $this->parentProfile->id,
            'student_id' => $student->id,
            'relationship_type' => 'father',
        ]);
    });

    it('validates required fields', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->postJson('/api/v1/parent/children/link', [], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student_code', 'relationship_type']);
    });

    it('validates relationship type enum', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $student = StudentProfile::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        $response = $this->postJson('/api/v1/parent/children/link', [
            'student_code' => $student->student_code,
            'relationship_type' => 'invalid_type',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['relationship_type']);
    });

    it('returns 404 when student code does not exist', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->postJson('/api/v1/parent/children/link', [
            'student_code' => 'INVALID-CODE-999',
            'relationship_type' => 'father',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'error_code' => 'STUDENT_CODE_NOT_FOUND',
            ]);
    });

    it('prevents linking the same child twice', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $student = StudentProfile::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        // First link
        ParentStudentRelationship::create([
            'parent_id' => $this->parentProfile->id,
            'student_id' => $student->id,
            'relationship_type' => 'father',
        ]);

        // Try to link again
        $response = $this->postJson('/api/v1/parent/children/link', [
            'student_code' => $student->student_code,
            'relationship_type' => 'mother',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error_code' => 'CHILD_ALREADY_LINKED',
            ]);
    });
});

describe('show (get specific child)', function () {
    it('returns child details for linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $student = StudentProfile::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        ParentStudentRelationship::create([
            'parent_id' => $this->parentProfile->id,
            'student_id' => $student->id,
            'relationship_type' => 'father',
        ]);

        $response = $this->getJson("/api/v1/parent/children/{$student->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'child' => [
                        'id',
                        'user_id',
                        'name',
                        'first_name',
                        'last_name',
                        'student_code',
                        'avatar',
                        'email',
                        'phone',
                        'birth_date',
                        'age',
                        'gender',
                        'nationality',
                        'grade_level',
                        'enrollment_date',
                        'relationship',
                        'linked_at',
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'child' => [
                        'id' => $student->id,
                        'relationship' => 'father',
                    ],
                ],
            ]);
    });

    it('returns 404 for non-linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $otherStudent = StudentProfile::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        $response = $this->getJson("/api/v1/parent/children/{$otherStudent->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('prevents viewing another parents child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        // Create another parent and link a student
        $otherParentUser = User::factory()->parent()->forAcademy($this->academy)->create();
        $otherParentProfile = ParentProfile::factory()->create([
            'user_id' => $otherParentUser->id,
            'academy_id' => $this->academy->id,
        ]);

        $student = StudentProfile::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        ParentStudentRelationship::create([
            'parent_id' => $otherParentProfile->id,
            'student_id' => $student->id,
            'relationship_type' => 'mother',
        ]);

        $response = $this->getJson("/api/v1/parent/children/{$student->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });
});

describe('setActive (set active child)', function () {
    it('sets a linked child as active', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $student = StudentProfile::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        ParentStudentRelationship::create([
            'parent_id' => $this->parentProfile->id,
            'student_id' => $student->id,
            'relationship_type' => 'father',
        ]);

        $response = $this->putJson("/api/v1/parent/children/{$student->id}/active", [], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'active_child_id' => $student->id,
                ],
            ]);
    });

    it('returns 404 for non-linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $otherStudent = StudentProfile::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        $response = $this->putJson("/api/v1/parent/children/{$otherStudent->id}/active", [], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });
});

describe('unlink (unlink child)', function () {
    it('successfully unlinks a child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $student = StudentProfile::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        ParentStudentRelationship::create([
            'parent_id' => $this->parentProfile->id,
            'student_id' => $student->id,
            'relationship_type' => 'father',
        ]);

        $response = $this->deleteJson("/api/v1/parent/children/{$student->id}/unlink", [], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'unlinked' => true,
                ],
            ]);

        $this->assertDatabaseMissing('parent_student_relationships', [
            'parent_id' => $this->parentProfile->id,
            'student_id' => $student->id,
        ]);
    });

    it('returns 404 when trying to unlink non-linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $otherStudent = StudentProfile::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        $response = $this->deleteJson("/api/v1/parent/children/{$otherStudent->id}/unlink", [], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });
});
