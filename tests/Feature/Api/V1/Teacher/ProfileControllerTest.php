<?php

use App\Models\Academy;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses()->group('api', 'teacher', 'profile');

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);
});

describe('Teacher Profile API', function () {
    describe('show profile', function () {
        it('returns quran teacher profile', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
                'bio' => 'Test bio',
                'qualifications' => 'Test qualifications',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/profile', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'profile' => [
                            'id',
                            'name',
                            'email',
                            'phone',
                            'avatar',
                            'is_quran_teacher',
                            'is_academic_teacher',
                            'quran_profile' => [
                                'id',
                                'bio',
                                'qualifications',
                                'hourly_rate',
                                'rating',
                                'status',
                            ],
                        ],
                    ],
                ]);

            expect($response->json('data.profile.is_quran_teacher'))->toBeTrue();
        });

        it('returns academic teacher profile', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/profile', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'profile' => [
                            'id',
                            'name',
                            'email',
                            'academic_profile' => [
                                'id',
                                'bio',
                                'qualifications',
                                'subjects',
                                'grade_levels',
                            ],
                        ],
                    ],
                ]);

            expect($response->json('data.profile.is_academic_teacher'))->toBeTrue();
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/profile', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('update profile', function () {
        it('updates user basic information', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create([
                'first_name' => 'Old',
                'last_name' => 'Name',
            ]);
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->putJson('/api/v1/teacher/profile', [
                'name' => 'New Name',
                'phone' => '0512345678',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $teacher->refresh();
            expect($teacher->name)->toBe('New Name');
            expect($teacher->phone)->toBe('0512345678');
        });

        it('updates quran teacher profile fields', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
                'bio' => 'Old bio',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->putJson('/api/v1/teacher/profile', [
                'quran_bio' => 'New bio',
                'quran_bio_arabic' => 'السيرة الذاتية الجديدة',
                'quran_qualifications' => 'New qualifications',
                'quran_certifications' => ['Ijazah', 'Qira\'at'],
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $profile->refresh();
            expect($profile->bio)->toBe('New bio');
            expect($profile->bio_arabic)->toBe('السيرة الذاتية الجديدة');
            expect($profile->qualifications)->toBe('New qualifications');
            expect($profile->certifications)->toBeArray();
        });

        it('updates academic teacher profile fields', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->putJson('/api/v1/teacher/profile', [
                'academic_bio' => 'New academic bio',
                'academic_subjects' => ['Math', 'Science'],
                'academic_grade_levels' => ['Grade 1', 'Grade 2'],
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $profile->refresh();
            expect($profile->bio)->toBe('New academic bio');
        });

        it('validates profile data', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->putJson('/api/v1/teacher/profile', [
                'quran_bio' => str_repeat('a', 2500), // Too long
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['quran_bio']);
        });

        it('requires authentication', function () {
            $response = $this->putJson('/api/v1/teacher/profile', [
                'name' => 'New Name',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('update avatar', function () {
        it('updates teacher avatar', function () {
            Storage::fake('public');

            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $file = UploadedFile::fake()->image('avatar.jpg', 500, 500);

            $response = $this->postJson('/api/v1/teacher/profile/avatar', [
                'avatar' => $file,
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => ['avatar'],
                ]);

            $teacher->refresh();
            expect($teacher->avatar)->not->toBeNull();
            Storage::disk('public')->assertExists($teacher->avatar);
        });

        it('validates avatar file', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson('/api/v1/teacher/profile/avatar', [
                'avatar' => 'not-a-file',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['avatar']);
        });

        it('deletes old avatar when uploading new one', function () {
            Storage::fake('public');

            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create([
                'avatar' => 'avatars/old-avatar.jpg',
            ]);
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Storage::disk('public')->put('avatars/old-avatar.jpg', 'content');

            Sanctum::actingAs($teacher, ['*']);

            $file = UploadedFile::fake()->image('new-avatar.jpg');

            $response = $this->postJson('/api/v1/teacher/profile/avatar', [
                'avatar' => $file,
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            Storage::disk('public')->assertMissing('avatars/old-avatar.jpg');
        });

        it('requires authentication', function () {
            $file = UploadedFile::fake()->image('avatar.jpg');

            $response = $this->postJson('/api/v1/teacher/profile/avatar', [
                'avatar' => $file,
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('change password', function () {
        it('changes teacher password', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create([
                'password' => Hash::make('old-password'),
            ]);
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson('/api/v1/teacher/profile/password', [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $teacher->refresh();
            expect(Hash::check('new-password', $teacher->password))->toBeTrue();
        });

        it('validates current password', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create([
                'password' => Hash::make('correct-password'),
            ]);
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson('/api/v1/teacher/profile/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(422);
        });

        it('requires password confirmation', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create([
                'password' => Hash::make('old-password'),
            ]);
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson('/api/v1/teacher/profile/password', [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'different-password',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('validates password strength', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create([
                'password' => Hash::make('old-password'),
            ]);
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson('/api/v1/teacher/profile/password', [
                'current_password' => 'old-password',
                'password' => '123', // Too short
                'password_confirmation' => '123',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('requires authentication', function () {
            $response = $this->postJson('/api/v1/teacher/profile/password', [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });
});
