<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
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

describe('Show Profile', function () {
    it('returns student profile', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson('/api/v1/student/profile', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'profile' => [
                        'id',
                        'user_id',
                        'student_code',
                        'first_name',
                        'last_name',
                        'full_name',
                        'email',
                        'phone',
                        'avatar',
                        'grade_level',
                    ],
                    'user' => [
                        'id',
                        'email',
                        'user_type',
                        'active_status',
                    ],
                ],
            ]);
    });

    it('returns error when profile not found', function () {
        $user = User::factory()
            ->student()
            ->forAcademy($this->academy)
            ->create();

        // Delete the student profile
        $user->studentProfile()->delete();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/student/profile', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/student/profile', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(401);
    });
});

describe('Update Profile', function () {
    it('updates profile information', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->putJson('/api/v1/student/profile', [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'phone' => '0501234567',
            'address' => '123 Test Street',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.updated', true);

        $this->student->studentProfile->refresh();
        expect($this->student->studentProfile->first_name)->toBe('Updated');
        expect($this->student->studentProfile->last_name)->toBe('Name');
    });

    it('validates birth date is before today', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->putJson('/api/v1/student/profile', [
            'birth_date' => now()->addDay()->toDateString(),
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['birth_date']);
    });

    it('validates gender values', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->putJson('/api/v1/student/profile', [
            'gender' => 'invalid',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['gender']);
    });

    it('updates password when provided with correct current password', function () {
        $currentPassword = 'password';
        $newPassword = 'new-password-123';

        $student = User::factory()
            ->student()
            ->forAcademy($this->academy)
            ->withPassword($currentPassword)
            ->create();

        $student->refresh();

        Sanctum::actingAs($student, ['*']);

        $response = $this->putJson('/api/v1/student/profile', [
            'current_password' => $currentPassword,
            'new_password' => $newPassword,
            'new_password_confirmation' => $newPassword,
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $student->refresh();
        expect(Hash::check($newPassword, $student->password))->toBeTrue();
    });

    it('rejects password update with wrong current password', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->putJson('/api/v1/student/profile', [
            'current_password' => 'wrong-password',
            'new_password' => 'new-password-123',
            'new_password_confirmation' => 'new-password-123',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'code' => 'INVALID_PASSWORD',
            ]);
    });

    it('requires password confirmation', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->putJson('/api/v1/student/profile', [
            'current_password' => 'password',
            'new_password' => 'new-password-123',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    });
});

describe('Update Avatar', function () {
    it('uploads and updates avatar', function () {
        Sanctum::actingAs($this->student, ['*']);

        $file = UploadedFile::fake()->image('avatar.jpg', 500, 500);

        $response = $this->postJson('/api/v1/student/profile/avatar', [
            'avatar' => $file,
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'avatar',
                ],
            ]);

        $this->student->studentProfile->refresh();
        expect($this->student->studentProfile->avatar)->not->toBeNull();

        Storage::disk('public')->assertExists($this->student->studentProfile->avatar);
    });

    it('requires avatar file', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->postJson('/api/v1/student/profile/avatar', [], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    });

    it('validates avatar is an image', function () {
        Sanctum::actingAs($this->student, ['*']);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/v1/student/profile/avatar', [
            'avatar' => $file,
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    });

    it('validates avatar size limit', function () {
        Sanctum::actingAs($this->student, ['*']);

        $file = UploadedFile::fake()->image('huge-avatar.jpg')->size(3000);

        $response = $this->postJson('/api/v1/student/profile/avatar', [
            'avatar' => $file,
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    });

    it('deletes old avatar when uploading new one', function () {
        Sanctum::actingAs($this->student, ['*']);

        // Upload first avatar
        $oldAvatar = 'avatars/students/old-avatar.jpg';
        Storage::disk('public')->put($oldAvatar, 'old content');

        $this->student->studentProfile->update(['avatar' => $oldAvatar]);
        $this->student->update(['avatar' => $oldAvatar]);

        // Upload new avatar
        $newFile = UploadedFile::fake()->image('new-avatar.jpg');

        $response = $this->postJson('/api/v1/student/profile/avatar', [
            'avatar' => $newFile,
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        // Old avatar should be deleted
        Storage::disk('public')->assertMissing($oldAvatar);
    });
});
