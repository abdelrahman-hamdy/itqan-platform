<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicTeacherProfile;
use App\Models\ParentProfile;
use App\Models\ParentStudentRelationship;
use App\Models\QuranTeacherProfile;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class)->group('api', 'auth');

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
        'allow_registration' => true,
    ]);

    $this->headers = [
        'X-Academy-Subdomain' => $this->academy->subdomain,
        'Accept' => 'application/json',
    ];
});

describe('LoginController', function () {
    describe('login', function () {
        it('successfully logs in a student with valid credentials', function () {
            $user = User::factory()
                ->student()
                ->forAcademy($this->academy)
                ->create([
                    'email' => 'student@test.com',
                    'password' => Hash::make('password123'),
                ]);

            $response = $this->postJson('/api/v1/login', [
                'email' => 'student@test.com',
                'password' => 'password123',
            ], $this->headers);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user',
                        'academy',
                        'token',
                        'token_type',
                        'expires_at',
                        'abilities',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'token_type' => 'Bearer',
                    ],
                ]);

            expect($response->json('data.abilities'))->toContain('student:*');
            expect($user->fresh()->last_login_at)->not->toBeNull();
        });

        it('successfully logs in a parent with valid credentials', function () {
            $user = User::factory()
                ->parent()
                ->forAcademy($this->academy)
                ->create([
                    'email' => 'parent@test.com',
                    'password' => Hash::make('password123'),
                ]);

            $response = $this->postJson('/api/v1/login', [
                'email' => 'parent@test.com',
                'password' => 'password123',
            ], $this->headers);

            $response->assertStatus(200);
            expect($response->json('data.abilities'))->toContain('parent:*');
        });

        it('successfully logs in a quran teacher with valid credentials', function () {
            $user = User::factory()
                ->quranTeacher()
                ->forAcademy($this->academy)
                ->create([
                    'email' => 'teacher@test.com',
                    'password' => Hash::make('password123'),
                ]);

            $response = $this->postJson('/api/v1/login', [
                'email' => 'teacher@test.com',
                'password' => 'password123',
            ], $this->headers);

            $response->assertStatus(200);
            expect($response->json('data.abilities'))->toContain('teacher:*', 'quran:*');
        });

        it('successfully logs in an academic teacher with valid credentials', function () {
            $user = User::factory()
                ->academicTeacher()
                ->forAcademy($this->academy)
                ->create([
                    'email' => 'academic@test.com',
                    'password' => Hash::make('password123'),
                ]);

            $response = $this->postJson('/api/v1/login', [
                'email' => 'academic@test.com',
                'password' => 'password123',
            ], $this->headers);

            $response->assertStatus(200);
            expect($response->json('data.abilities'))->toContain('teacher:*', 'academic:*');
        });

        it('fails to login with invalid password', function () {
            $user = User::factory()
                ->student()
                ->forAcademy($this->academy)
                ->create([
                    'email' => 'student@test.com',
                    'password' => Hash::make('password123'),
                ]);

            $response = $this->postJson('/api/v1/login', [
                'email' => 'student@test.com',
                'password' => 'wrongpassword',
            ], $this->headers);

            $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'error_code' => 'INVALID_CREDENTIALS',
                ]);
        });

        it('fails to login with non-existent email', function () {
            $response = $this->postJson('/api/v1/login', [
                'email' => 'nonexistent@test.com',
                'password' => 'password123',
            ], $this->headers);

            $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'error_code' => 'INVALID_CREDENTIALS',
                ]);
        });

        it('fails to login with inactive user account', function () {
            $user = User::factory()
                ->student()
                ->inactive()
                ->forAcademy($this->academy)
                ->create([
                    'email' => 'inactive@test.com',
                    'password' => Hash::make('password123'),
                ]);

            $response = $this->postJson('/api/v1/login', [
                'email' => 'inactive@test.com',
                'password' => 'password123',
            ], $this->headers);

            $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'error_code' => 'ACCOUNT_INACTIVE',
                ]);
        });

        it('fails to login with unsupported user type (admin)', function () {
            $user = User::factory()
                ->admin()
                ->forAcademy($this->academy)
                ->create([
                    'email' => 'admin@test.com',
                    'password' => Hash::make('password123'),
                ]);

            $response = $this->postJson('/api/v1/login', [
                'email' => 'admin@test.com',
                'password' => 'password123',
            ], $this->headers);

            $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'error_code' => 'UNSUPPORTED_USER_TYPE',
                ]);
        });

        it('validates required email field', function () {
            $response = $this->postJson('/api/v1/login', [
                'password' => 'password123',
            ], $this->headers);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('validates required password field', function () {
            $response = $this->postJson('/api/v1/login', [
                'email' => 'student@test.com',
            ], $this->headers);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('validates email format', function () {
            $response = $this->postJson('/api/v1/login', [
                'email' => 'invalid-email',
                'password' => 'password123',
            ], $this->headers);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('respects throttle limit on failed login attempts', function () {
            $user = User::factory()
                ->student()
                ->forAcademy($this->academy)
                ->create([
                    'email' => 'student@test.com',
                    'password' => Hash::make('password123'),
                ]);

            // Make 11 failed login attempts (throttle is 10 per minute)
            for ($i = 0; $i < 11; $i++) {
                $response = $this->postJson('/api/v1/login', [
                    'email' => 'student@test.com',
                    'password' => 'wrongpassword',
                ], $this->headers);

                if ($i < 10) {
                    $response->assertStatus(401);
                } else {
                    $response->assertStatus(429); // Too Many Requests
                }
            }
        });

        it('scopes users by academy_id', function () {
            $otherAcademy = Academy::factory()->create([
                'subdomain' => 'other-academy',
                'is_active' => true,
            ]);

            $user = User::factory()
                ->student()
                ->forAcademy($otherAcademy)
                ->create([
                    'email' => 'student@test.com',
                    'password' => Hash::make('password123'),
                ]);

            $response = $this->postJson('/api/v1/login', [
                'email' => 'student@test.com',
                'password' => 'password123',
            ], $this->headers);

            $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'error_code' => 'INVALID_CREDENTIALS',
                ]);
        });
    });

    describe('logout', function () {
        it('successfully logs out an authenticated user', function () {
            $user = User::factory()
                ->student()
                ->forAcademy($this->academy)
                ->create();

            Sanctum::actingAs($user, ['*']);

            $response = $this->postJson('/api/v1/logout', [], $this->headers);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);

            // Verify token was deleted
            expect($user->tokens()->count())->toBe(0);
        });

        it('fails to logout without authentication', function () {
            $response = $this->postJson('/api/v1/logout', [], $this->headers);

            $response->assertStatus(401);
        });
    });

    describe('me', function () {
        it('returns authenticated user info', function () {
            $user = User::factory()
                ->student()
                ->forAcademy($this->academy)
                ->create();

            Sanctum::actingAs($user, ['*']);

            $response = $this->getJson('/api/v1/me', $this->headers);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user',
                        'academy',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'email' => $user->email,
                        ],
                    ],
                ]);
        });

        it('fails without authentication', function () {
            $response = $this->getJson('/api/v1/me', $this->headers);

            $response->assertStatus(401);
        });
    });
});

describe('RegisterController', function () {
    describe('registerStudent', function () {
        it('successfully registers a new student', function () {
            $gradeLevel = AcademicGradeLevel::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $email = 'student' . time() . rand(1000, 9999) . '@test.com';

            $response = $this->postJson('/api/v1/register/student', [
                'first_name' => 'محمد',
                'last_name' => 'أحمد',
                'email' => $email,
                'phone' => '0501234567',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'birth_date' => '2010-01-01',
                'gender' => 'male',
                'nationality' => 'SA',
                'grade_level_id' => $gradeLevel->id,
            ], $this->headers);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user',
                        'academy',
                        'token',
                        'token_type',
                        'expires_at',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                ]);

            // Verify user was created
            $user = User::where('email', $email)->first();
            expect($user)->not->toBeNull();
            expect($user->user_type)->toBe('student');
            expect($user->academy_id)->toBe($this->academy->id);

            // Verify student profile was created
            $studentProfile = StudentProfile::where('user_id', $user->id)->first();
            expect($studentProfile)->not->toBeNull();
            expect($studentProfile->grade_level_id)->toBe($gradeLevel->id);
        });

        it('prevents duplicate email in same academy', function () {
            $gradeLevel = AcademicGradeLevel::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            User::factory()
                ->student()
                ->forAcademy($this->academy)
                ->create(['email' => 'existing@test.com']);

            $response = $this->postJson('/api/v1/register/student', [
                'first_name' => 'محمد',
                'last_name' => 'أحمد',
                'email' => 'existing@test.com',
                'phone' => '0501234567',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'birth_date' => '2010-01-01',
                'gender' => 'male',
                'grade_level_id' => $gradeLevel->id,
            ], $this->headers);

            $response->assertStatus(409)
                ->assertJson([
                    'success' => false,
                    'error_code' => 'EMAIL_EXISTS',
                ]);
        });

        it('validates required fields', function () {
            $response = $this->postJson('/api/v1/register/student', [], $this->headers);

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

        it('validates password confirmation', function () {
            $gradeLevel = AcademicGradeLevel::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $response = $this->postJson('/api/v1/register/student', [
                'first_name' => 'محمد',
                'last_name' => 'أحمد',
                'email' => 'student@test.com',
                'phone' => '0501234567',
                'password' => 'password123',
                'password_confirmation' => 'differentpassword',
                'birth_date' => '2010-01-01',
                'gender' => 'male',
                'grade_level_id' => $gradeLevel->id,
            ], $this->headers);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('validates minimum password length', function () {
            $gradeLevel = AcademicGradeLevel::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $response = $this->postJson('/api/v1/register/student', [
                'first_name' => 'محمد',
                'last_name' => 'أحمد',
                'email' => 'student@test.com',
                'phone' => '0501234567',
                'password' => 'short',
                'password_confirmation' => 'short',
                'birth_date' => '2010-01-01',
                'gender' => 'male',
                'grade_level_id' => $gradeLevel->id,
            ], $this->headers);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('validates gender values', function () {
            $gradeLevel = AcademicGradeLevel::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $response = $this->postJson('/api/v1/register/student', [
                'first_name' => 'محمد',
                'last_name' => 'أحمد',
                'email' => 'student@test.com',
                'phone' => '0501234567',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'birth_date' => '2010-01-01',
                'gender' => 'invalid',
                'grade_level_id' => $gradeLevel->id,
            ], $this->headers);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['gender']);
        });
    });

    describe('verifyStudentCode', function () {
        it('successfully verifies a valid student code', function () {
            $gradeLevel = AcademicGradeLevel::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $studentProfile = StudentProfile::factory()->create([
                'grade_level_id' => $gradeLevel->id,
                'student_code' => 'STU12345',
                'first_name' => 'محمد',
                'last_name' => 'أحمد',
            ]);

            $response = $this->postJson('/api/v1/register/parent/verify-student', [
                'student_code' => 'STU12345',
            ], $this->headers);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'student' => [
                            'id',
                            'name',
                            'student_code',
                            'grade_level',
                            'has_parent',
                        ],
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'student' => [
                            'student_code' => 'STU12345',
                        ],
                    ],
                ]);
        });

        it('fails with invalid student code', function () {
            $response = $this->postJson('/api/v1/register/parent/verify-student', [
                'student_code' => 'INVALID',
            ], $this->headers);

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'error_code' => 'STUDENT_CODE_NOT_FOUND',
                ]);
        });

        it('validates required student_code field', function () {
            $response = $this->postJson('/api/v1/register/parent/verify-student', [], $this->headers);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['student_code']);
        });
    });

    describe('registerParent', function () {
        it('successfully registers a new parent', function () {
            $gradeLevel = AcademicGradeLevel::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $studentCode = 'STU' . time() . rand(1000, 9999);
            $studentProfile = StudentProfile::factory()->create([
                'grade_level_id' => $gradeLevel->id,
                'student_code' => $studentCode,
            ]);

            $email = 'parent' . time() . rand(1000, 9999) . '@test.com';

            $response = $this->postJson('/api/v1/register/parent', [
                'first_name' => 'أحمد',
                'last_name' => 'محمد',
                'email' => $email,
                'phone' => '0501234567',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'student_code' => $studentCode,
                'relationship_type' => 'father',
                'occupation' => 'مهندس',
            ], $this->headers);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user',
                        'academy',
                        'token',
                        'token_type',
                        'expires_at',
                        'linked_student',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                ]);

            // Verify user was created
            $user = User::where('email', $email)->first();
            expect($user)->not->toBeNull();
            expect($user->user_type)->toBe('parent');

            // Verify parent profile was created
            $parentProfile = ParentProfile::where('user_id', $user->id)->first();
            expect($parentProfile)->not->toBeNull();

            // Verify parent-student relationship was created
            $relationship = ParentStudentRelationship::where('parent_id', $parentProfile->id)
                ->where('student_id', $studentProfile->id)
                ->first();
            expect($relationship)->not->toBeNull();
        });

        it('fails with invalid student code', function () {
            $response = $this->postJson('/api/v1/register/parent', [
                'first_name' => 'أحمد',
                'last_name' => 'محمد',
                'email' => 'parent@test.com',
                'phone' => '0501234567',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'student_code' => 'INVALID',
                'relationship_type' => 'father',
            ], $this->headers);

            $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'error_code' => 'STUDENT_CODE_NOT_FOUND',
                ]);
        });

        it('validates relationship_type values', function () {
            $response = $this->postJson('/api/v1/register/parent', [
                'first_name' => 'أحمد',
                'last_name' => 'محمد',
                'email' => 'parent@test.com',
                'phone' => '0501234567',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'student_code' => 'STU12345',
                'relationship_type' => 'invalid',
            ], $this->headers);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['relationship_type']);
        });
    });

    describe('teacherStep1', function () {
        it('successfully completes teacher registration step 1', function () {
            $response = $this->postJson('/api/v1/register/teacher/step1', [
                'teacher_type' => 'quran_teacher',
            ], $this->headers);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'teacher_type',
                        'registration_token',
                        'next_step',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'teacher_type' => 'quran_teacher',
                        'next_step' => 'step2',
                    ],
                ]);
        });

        it('validates teacher_type values', function () {
            $response = $this->postJson('/api/v1/register/teacher/step1', [
                'teacher_type' => 'invalid',
            ], $this->headers);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['teacher_type']);
        });
    });

    describe('teacherStep2', function () {
        it('successfully registers a quran teacher', function () {
            // Get registration token from step 1
            $step1Response = $this->postJson('/api/v1/register/teacher/step1', [
                'teacher_type' => 'quran_teacher',
            ], $this->headers);

            $registrationToken = $step1Response->json('data.registration_token');

            $response = $this->postJson('/api/v1/register/teacher/step2', [
                'registration_token' => $registrationToken,
                'first_name' => 'عبدالله',
                'last_name' => 'محمد',
                'email' => 'quran.teacher@test.com',
                'phone' => '0501234567',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'education_level' => 'bachelor',
                'university' => 'جامعة الإمام محمد بن سعود',
                'years_experience' => 5,
                'bio' => 'معلم قرآن مع خبرة 5 سنوات',
            ], $this->headers);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user',
                        'academy',
                        'requires_approval',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'requires_approval' => true,
                    ],
                ]);

            // Verify user was created but inactive
            $user = User::where('email', 'quran.teacher@test.com')->first();
            expect($user)->not->toBeNull();
            expect($user->user_type)->toBe('quran_teacher');
            expect($user->active_status)->toBeFalse();

            // Verify teacher profile was created
            $teacherProfile = QuranTeacherProfile::where('user_id', $user->id)->first();
            expect($teacherProfile)->not->toBeNull();
            expect($teacherProfile->approval_status)->toBe('pending');
        });

        it('successfully registers an academic teacher', function () {
            $gradeLevel = AcademicGradeLevel::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            // Create a simple subject in the database
            $subject = DB::table('academic_subjects')->insertGetId([
                'academy_id' => $this->academy->id,
                'name' => 'رياضيات',
                'name_en' => 'Mathematics',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Get registration token from step 1
            $step1Response = $this->postJson('/api/v1/register/teacher/step1', [
                'teacher_type' => 'academic_teacher',
            ], $this->headers);

            $registrationToken = $step1Response->json('data.registration_token');

            $response = $this->postJson('/api/v1/register/teacher/step2', [
                'registration_token' => $registrationToken,
                'first_name' => 'عبدالله',
                'last_name' => 'محمد',
                'email' => 'academic.teacher@test.com',
                'phone' => '0501234567',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'education_level' => 'master',
                'university' => 'جامعة الملك سعود',
                'years_experience' => 10,
                'bio' => 'معلم رياضيات مع خبرة 10 سنوات',
                'subject_ids' => [$subject],
                'grade_level_ids' => [$gradeLevel->id],
            ], $this->headers);

            $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'requires_approval' => true,
                    ],
                ]);

            // Verify user was created but inactive
            $user = User::where('email', 'academic.teacher@test.com')->first();
            expect($user)->not->toBeNull();
            expect($user->user_type)->toBe('academic_teacher');
            expect($user->active_status)->toBeFalse();

            // Verify teacher profile was created
            $teacherProfile = AcademicTeacherProfile::where('user_id', $user->id)->first();
            expect($teacherProfile)->not->toBeNull();
            expect($teacherProfile->approval_status)->toBe('pending');
        });

        it('fails with invalid registration token', function () {
            $response = $this->postJson('/api/v1/register/teacher/step2', [
                'registration_token' => 'invalid-token',
                'first_name' => 'عبدالله',
                'last_name' => 'محمد',
                'email' => 'teacher@test.com',
                'phone' => '0501234567',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'education_level' => 'bachelor',
                'years_experience' => 5,
            ], $this->headers);

            $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'error_code' => 'INVALID_REGISTRATION_TOKEN',
                ]);
        });

        it('validates required subject_ids for academic teacher', function () {
            // Get registration token from step 1
            $step1Response = $this->postJson('/api/v1/register/teacher/step1', [
                'teacher_type' => 'academic_teacher',
            ], $this->headers);

            $registrationToken = $step1Response->json('data.registration_token');

            $response = $this->postJson('/api/v1/register/teacher/step2', [
                'registration_token' => $registrationToken,
                'first_name' => 'عبدالله',
                'last_name' => 'محمد',
                'email' => 'teacher@test.com',
                'phone' => '0501234567',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'education_level' => 'bachelor',
                'years_experience' => 5,
                // Missing subject_ids and grade_level_ids
            ], $this->headers);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['subject_ids', 'grade_level_ids']);
        });
    });
});

describe('ForgotPasswordController', function () {
    describe('sendResetLink', function () {
        it('returns success even for non-existent email (security)', function () {
            $response = $this->postJson('/api/v1/forgot-password', [
                'email' => 'nonexistent@test.com',
            ], $this->headers);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);
        });

        it('creates password reset token for existing user', function () {
            $user = User::factory()
                ->student()
                ->forAcademy($this->academy)
                ->create(['email' => 'student@test.com']);

            $response = $this->postJson('/api/v1/forgot-password', [
                'email' => 'student@test.com',
            ], $this->headers);

            $response->assertStatus(200);

            // Verify reset token was created
            $token = DB::table('password_reset_tokens')
                ->where('email', 'student@test.com')
                ->first();
            expect($token)->not->toBeNull();
        });

        it('validates required email field', function () {
            $response = $this->postJson('/api/v1/forgot-password', [], $this->headers);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('validates email format', function () {
            $response = $this->postJson('/api/v1/forgot-password', [
                'email' => 'invalid-email',
            ], $this->headers);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });
    });

    describe('verifyToken', function () {
        it('successfully verifies a valid token', function () {
            $user = User::factory()
                ->student()
                ->forAcademy($this->academy)
                ->create(['email' => 'student@test.com']);

            $token = \Illuminate\Support\Str::random(64);
            DB::table('password_reset_tokens')->insert([
                'email' => 'student@test.com',
                'token' => Hash::make($token),
                'created_at' => now(),
            ]);

            $response = $this->postJson('/api/v1/verify-reset-token', [
                'email' => 'student@test.com',
                'token' => $token,
            ], $this->headers);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'valid' => true,
                    ],
                ]);
        });

        it('fails with invalid token', function () {
            $user = User::factory()
                ->student()
                ->forAcademy($this->academy)
                ->create(['email' => 'student@test.com']);

            $token = \Illuminate\Support\Str::random(64);
            DB::table('password_reset_tokens')->insert([
                'email' => 'student@test.com',
                'token' => Hash::make($token),
                'created_at' => now(),
            ]);

            $response = $this->postJson('/api/v1/verify-reset-token', [
                'email' => 'student@test.com',
                'token' => 'wrong-token',
            ], $this->headers);

            $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'error_code' => 'INVALID_RESET_TOKEN',
                ]);
        });

        it('fails with expired token', function () {
            $user = User::factory()
                ->student()
                ->forAcademy($this->academy)
                ->create(['email' => 'student@test.com']);

            $token = \Illuminate\Support\Str::random(64);
            DB::table('password_reset_tokens')->insert([
                'email' => 'student@test.com',
                'token' => Hash::make($token),
                'created_at' => now()->subHours(2), // Expired (60 minutes)
            ]);

            $response = $this->postJson('/api/v1/verify-reset-token', [
                'email' => 'student@test.com',
                'token' => $token,
            ], $this->headers);

            $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'error_code' => 'RESET_TOKEN_EXPIRED',
                ]);
        });
    });

    describe('resetPassword', function () {
        it('successfully resets password with valid token', function () {
            $user = User::factory()
                ->student()
                ->forAcademy($this->academy)
                ->create([
                    'email' => 'student@test.com',
                    'password' => Hash::make('oldpassword'),
                ]);

            $token = \Illuminate\Support\Str::random(64);
            DB::table('password_reset_tokens')->insert([
                'email' => 'student@test.com',
                'token' => Hash::make($token),
                'created_at' => now(),
            ]);

            $response = $this->postJson('/api/v1/reset-password', [
                'email' => 'student@test.com',
                'token' => $token,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ], $this->headers);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);

            // Verify password was changed
            $user->refresh();
            expect(Hash::check('newpassword123', $user->password))->toBeTrue();

            // Verify reset token was deleted
            $tokenExists = DB::table('password_reset_tokens')
                ->where('email', 'student@test.com')
                ->exists();
            expect($tokenExists)->toBeFalse();

            // Verify all tokens were revoked
            expect($user->tokens()->count())->toBe(0);
        });

        it('fails with invalid token', function () {
            $user = User::factory()
                ->student()
                ->forAcademy($this->academy)
                ->create(['email' => 'student@test.com']);

            $response = $this->postJson('/api/v1/reset-password', [
                'email' => 'student@test.com',
                'token' => 'invalid-token',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ], $this->headers);

            $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'error_code' => 'INVALID_RESET_TOKEN',
                ]);
        });

        it('validates password confirmation', function () {
            $user = User::factory()
                ->student()
                ->forAcademy($this->academy)
                ->create(['email' => 'student@test.com']);

            $token = \Illuminate\Support\Str::random(64);
            DB::table('password_reset_tokens')->insert([
                'email' => 'student@test.com',
                'token' => Hash::make($token),
                'created_at' => now(),
            ]);

            $response = $this->postJson('/api/v1/reset-password', [
                'email' => 'student@test.com',
                'token' => $token,
                'password' => 'newpassword123',
                'password_confirmation' => 'differentpassword',
            ], $this->headers);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });
    });
});

describe('TokenController', function () {
    describe('refresh', function () {
        it('successfully refreshes token', function () {
            $user = User::factory()
                ->student()
                ->forAcademy($this->academy)
                ->create();

            $oldToken = $user->createToken('mobile-app', ['read', 'write', 'student:*'])->plainTextToken;

            Sanctum::actingAs($user, ['read', 'write', 'student:*']);

            $response = $this->postJson('/api/v1/token/refresh', [], $this->headers);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user',
                        'academy',
                        'token',
                        'token_type',
                        'expires_at',
                        'abilities',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                ]);

            // Verify new token is different
            expect($response->json('data.token'))->not->toBe($oldToken);
        });

        it('fails without authentication', function () {
            $response = $this->postJson('/api/v1/token/refresh', [], $this->headers);

            $response->assertStatus(401);
        });
    });

    describe('validateToken', function () {
        it('successfully validates current token', function () {
            $user = User::factory()
                ->student()
                ->forAcademy($this->academy)
                ->create();

            Sanctum::actingAs($user, ['read', 'write', 'student:*']);

            $response = $this->getJson('/api/v1/token/validate', $this->headers);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'valid',
                        'user',
                        'academy',
                        'token_info' => [
                            'name',
                            'abilities',
                        ],
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'valid' => true,
                    ],
                ]);
        });

        it('fails without authentication', function () {
            $response = $this->getJson('/api/v1/token/validate', $this->headers);

            $response->assertStatus(401);
        });
    });

    describe('revoke', function () {
        it('successfully revokes current token', function () {
            $user = User::factory()
                ->student()
                ->forAcademy($this->academy)
                ->create();

            Sanctum::actingAs($user, ['*']);

            $response = $this->deleteJson('/api/v1/token/revoke', [], $this->headers);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);

            // Verify token was deleted
            expect($user->tokens()->count())->toBe(0);
        });

        it('fails without authentication', function () {
            $response = $this->deleteJson('/api/v1/token/revoke', [], $this->headers);

            $response->assertStatus(401);
        });
    });

    describe('revokeAll', function () {
        it('successfully revokes all user tokens', function () {
            $user = User::factory()
                ->student()
                ->forAcademy($this->academy)
                ->create();

            // Create multiple tokens
            $user->createToken('token1', ['*']);
            $user->createToken('token2', ['*']);
            $user->createToken('token3', ['*']);

            Sanctum::actingAs($user, ['*']);

            $response = $this->deleteJson('/api/v1/token/revoke-all', [], $this->headers);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'revoked_count',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'revoked_count' => 3,
                    ],
                ]);

            // Verify all tokens were deleted
            expect($user->fresh()->tokens()->count())->toBe(0);
        });

        it('fails without authentication', function () {
            $response = $this->deleteJson('/api/v1/token/revoke-all', [], $this->headers);

            $response->assertStatus(401);
        });
    });
});
