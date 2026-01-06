<?php

use App\Models\AcademicGradeLevel;
use App\Models\Academy;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

/**
 * API V1 Authentication - Registration Tests
 *
 * Tests the registration endpoints for:
 * - Student registration
 * - Parent registration
 * - Teacher registration (2-step)
 * - Validation rules
 * - Academy context
 */
beforeEach(function () {
    // Fake notifications to avoid multi-tenancy issues with queued jobs
    Notification::fake();

    // Create a test academy with registration enabled
    $this->academy = Academy::factory()->create([
        'name' => 'Test Academy',
        'subdomain' => 'test-academy',
        'is_active' => true,
        'allow_registration' => true,
    ]);

    // Create a grade level
    $this->gradeLevel = AcademicGradeLevel::factory()->create([
        'academy_id' => $this->academy->id,
        'name' => 'Grade 1',
    ]);
});

describe('POST /api/v1/register/student', function () {

    it('successfully registers a new student', function () {
        $uniqueEmail = 'newstudent_'.uniqid().'@test.com';

        $response = $this->postJson('/api/v1/register/student', [
            'first_name' => 'Test',
            'last_name' => 'Student',
            'email' => $uniqueEmail,
            'phone' => '+966500000001',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
            'birth_date' => '2010-01-15',
            'gender' => 'male',
            'nationality' => 'Saudi',
            'grade_level_id' => $this->gradeLevel->id,
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'token',
                    'token_type',
                    'expires_at',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => $uniqueEmail,
            'user_type' => 'student',
            'academy_id' => $this->academy->id,
        ]);

        // Verify student profile was created
        $this->assertDatabaseHas('student_profiles', [
            'email' => $uniqueEmail,
            'first_name' => 'Test',
            'last_name' => 'Student',
        ]);
    });

    it('rejects duplicate email in same academy', function () {
        User::factory()->create([
            'academy_id' => $this->academy->id,
            'email' => 'existing@test.com',
        ]);

        $response = $this->postJson('/api/v1/register/student', [
            'first_name' => 'Another',
            'last_name' => 'User',
            'email' => 'existing@test.com',
            'phone' => '+966500000002',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
            'birth_date' => '2010-01-15',
            'gender' => 'female',
            'grade_level_id' => $this->gradeLevel->id,
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
            ]);
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/v1/register/student', [], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'email',
                'phone',
                'password',
                'birth_date',
                'gender',
                'grade_level_id',
            ]);
    });

    it('validates password requirements', function () {
        $response = $this->postJson('/api/v1/register/student', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'weak@test.com',
            'phone' => '+966500000003',
            'password' => 'weak', // Too short, no mixed case, no numbers
            'password_confirmation' => 'weak',
            'birth_date' => '2010-01-15',
            'gender' => 'male',
            'grade_level_id' => $this->gradeLevel->id,
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('validates password confirmation', function () {
        $response = $this->postJson('/api/v1/register/student', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'mismatch@test.com',
            'phone' => '+966500000004',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'DifferentPassword123',
            'birth_date' => '2010-01-15',
            'gender' => 'male',
            'grade_level_id' => $this->gradeLevel->id,
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('validates birth date is in the past', function () {
        $response = $this->postJson('/api/v1/register/student', [
            'first_name' => 'Future',
            'last_name' => 'Child',
            'email' => 'future@test.com',
            'phone' => '+966500000005',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
            'birth_date' => now()->addYear()->toDateString(),
            'gender' => 'male',
            'grade_level_id' => $this->gradeLevel->id,
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['birth_date']);
    });

    it('validates grade level exists', function () {
        $response = $this->postJson('/api/v1/register/student', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'invalid-grade@test.com',
            'phone' => '+966500000006',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
            'birth_date' => '2010-01-15',
            'gender' => 'male',
            'grade_level_id' => 99999,
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['grade_level_id']);
    });

});

describe('POST /api/v1/register/parent/verify-student', function () {

    it('successfully verifies valid student code', function () {
        // Create a student with code
        $student = StudentProfile::factory()->create([
            'student_code' => 'STU-12345',
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        $response = $this->postJson('/api/v1/register/parent/verify-student', [
            'student_code' => 'STU-12345',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'student' => [
                        'id',
                        'name',
                        'student_code',
                    ],
                ],
            ]);
    });

    it('rejects invalid student code', function () {
        $response = $this->postJson('/api/v1/register/parent/verify-student', [
            'student_code' => 'INVALID-CODE',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(404);
    });

    it('validates required student code', function () {
        $response = $this->postJson('/api/v1/register/parent/verify-student', [], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student_code']);
    });

});

describe('POST /api/v1/register/parent', function () {

    beforeEach(function () {
        // Create a student to link with parent
        $this->student = StudentProfile::factory()->create([
            'student_code' => 'STU-PARENT-TEST',
            'first_name' => 'Child',
            'last_name' => 'Name',
            'grade_level_id' => $this->gradeLevel->id,
        ]);
    });

    it('successfully registers parent with valid student code', function () {
        $uniqueEmail = 'parent_'.uniqid().'@test.com';

        $response = $this->postJson('/api/v1/register/parent', [
            'first_name' => 'Parent',
            'last_name' => 'Name',
            'email' => $uniqueEmail,
            'phone' => '+966500000010',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
            'student_code' => 'STU-PARENT-TEST',
            'relationship_type' => 'father',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'token',
                    'linked_student',
                ],
            ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => $uniqueEmail,
            'user_type' => 'parent',
        ]);
    });

    it('rejects invalid student code', function () {
        $response = $this->postJson('/api/v1/register/parent', [
            'first_name' => 'Parent',
            'last_name' => 'Name',
            'email' => 'parent2@test.com',
            'phone' => '+966500000011',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
            'student_code' => 'WRONG-CODE',
            'relationship_type' => 'mother',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(404);
    });

    it('validates relationship type', function () {
        $response = $this->postJson('/api/v1/register/parent', [
            'first_name' => 'Parent',
            'last_name' => 'Name',
            'email' => 'parent3@test.com',
            'phone' => '+966500000012',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
            'student_code' => 'STU-PARENT-TEST',
            'relationship_type' => 'invalid',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['relationship_type']);
    });

});

describe('POST /api/v1/register/teacher/step1', function () {

    it('successfully completes step 1 for quran teacher', function () {
        $response = $this->postJson('/api/v1/register/teacher/step1', [
            'teacher_type' => 'quran_teacher',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'teacher_type',
                    'registration_token',
                    'next_step',
                ],
            ]);
    });

    it('successfully completes step 1 for academic teacher', function () {
        $response = $this->postJson('/api/v1/register/teacher/step1', [
            'teacher_type' => 'academic_teacher',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'teacher_type' => 'academic_teacher',
                    'next_step' => 'step2',
                ],
            ]);
    });

    it('validates teacher type', function () {
        $response = $this->postJson('/api/v1/register/teacher/step1', [
            'teacher_type' => 'invalid_type',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['teacher_type']);
    });

    it('requires teacher type', function () {
        $response = $this->postJson('/api/v1/register/teacher/step1', [], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['teacher_type']);
    });

});
