<?php

use App\Models\Academy;
use App\Models\ParentProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses()->group('api', 'parent-api', 'profile');

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);

    // Create parent user with profile
    $this->parentUser = User::factory()->parent()->forAcademy($this->academy)->create([
        'email' => 'parent@example.com',
        'password' => Hash::make('password123'),
    ]);

    $this->parentProfile = ParentProfile::factory()->create([
        'user_id' => $this->parentUser->id,
        'academy_id' => $this->academy->id,
        'first_name' => 'Ahmed',
        'last_name' => 'Ali',
        'phone' => '0501234567',
        'preferred_language' => 'ar',
    ]);
});

describe('show (get profile)', function () {
    it('returns parent profile data', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->getJson('/api/v1/parent/profile', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'profile' => [
                        'id',
                        'user_id',
                        'first_name',
                        'last_name',
                        'full_name',
                        'email',
                        'phone',
                        'avatar',
                        'address',
                        'city',
                        'country',
                        'nationality',
                        'preferred_language',
                        'notification_preferences',
                        'created_at',
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'profile' => [
                        'first_name' => 'Ahmed',
                        'last_name' => 'Ali',
                        'email' => 'parent@example.com',
                        'phone' => '0501234567',
                        'preferred_language' => 'ar',
                    ],
                ],
            ]);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/parent/profile', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(401);
    });

    it('returns 404 when parent has no profile', function () {
        $userWithoutProfile = User::factory()->parent()->forAcademy($this->academy)->create();
        Sanctum::actingAs($userWithoutProfile, ['*']);

        $response = $this->getJson('/api/v1/parent/profile', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error_code' => 'PARENT_PROFILE_NOT_FOUND',
            ]);
    });
});

describe('update (update profile)', function () {
    it('successfully updates parent profile', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->putJson('/api/v1/parent/profile', [
            'first_name' => 'Mohammed',
            'last_name' => 'Hassan',
            'phone' => '0509876543',
            'address' => '123 Main St',
            'city' => 'Riyadh',
            'country' => 'Saudi Arabia',
            'nationality' => 'Saudi',
            'preferred_language' => 'en',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'profile' => [
                        'first_name' => 'Mohammed',
                        'last_name' => 'Hassan',
                        'phone' => '0509876543',
                        'address' => '123 Main St',
                        'city' => 'Riyadh',
                        'country' => 'Saudi Arabia',
                        'preferred_language' => 'en',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('parent_profiles', [
            'id' => $this->parentProfile->id,
            'first_name' => 'Mohammed',
            'last_name' => 'Hassan',
            'phone' => '0509876543',
        ]);
    });

    it('updates user name when first or last name changes', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->putJson('/api/v1/parent/profile', [
            'first_name' => 'NewFirst',
            'last_name' => 'NewLast',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $this->parentUser->refresh();
        expect($this->parentUser->name)->toBe('NewFirst NewLast');
    });

    it('validates field lengths', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->putJson('/api/v1/parent/profile', [
            'first_name' => str_repeat('a', 300), // Too long
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name']);
    });

    it('validates preferred language enum', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->putJson('/api/v1/parent/profile', [
            'preferred_language' => 'fr', // Invalid
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['preferred_language']);
    });

    it('updates notification preferences', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->putJson('/api/v1/parent/profile', [
            'notification_preferences' => [
                'email' => true,
                'sms' => false,
                'push' => true,
            ],
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $this->parentProfile->refresh();
        expect($this->parentProfile->notification_preferences['email'])->toBeTrue();
        expect($this->parentProfile->notification_preferences['sms'])->toBeFalse();
    });

    it('allows partial updates', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $originalLastName = $this->parentProfile->last_name;

        $response = $this->putJson('/api/v1/parent/profile', [
            'first_name' => 'Updated',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $this->parentProfile->refresh();
        expect($this->parentProfile->first_name)->toBe('Updated');
        expect($this->parentProfile->last_name)->toBe($originalLastName);
    });
});

describe('updateAvatar (update avatar)', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('successfully uploads avatar', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $file = UploadedFile::fake()->image('avatar.jpg', 500, 500);

        $response = $this->postJson('/api/v1/parent/profile/avatar', [
            'avatar' => $file,
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'avatar',
                ],
            ]);

        $this->parentUser->refresh();
        expect($this->parentUser->avatar)->not()->toBeNull();
        Storage::disk('public')->assertExists($this->parentUser->avatar);
    });

    it('validates avatar is an image', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/v1/parent/profile/avatar', [
            'avatar' => $file,
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    });

    it('validates avatar size', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $file = UploadedFile::fake()->image('avatar.jpg')->size(3000); // Too large

        $response = $this->postJson('/api/v1/parent/profile/avatar', [
            'avatar' => $file,
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    });

    it('deletes old avatar when uploading new one', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        // Upload first avatar
        $oldFile = UploadedFile::fake()->image('old-avatar.jpg');
        $this->parentUser->update(['avatar' => Storage::disk('public')->putFile('avatars', $oldFile)]);
        $oldPath = $this->parentUser->avatar;

        // Upload new avatar
        $newFile = UploadedFile::fake()->image('new-avatar.jpg');
        $response = $this->postJson('/api/v1/parent/profile/avatar', [
            'avatar' => $newFile,
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        Storage::disk('public')->assertMissing($oldPath);
    });
});

describe('changePassword (change password)', function () {
    it('successfully changes password', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->postJson('/api/v1/parent/profile/change-password', [
            'current_password' => 'password123',
            'password' => 'newPassword456',
            'password_confirmation' => 'newPassword456',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $this->parentUser->refresh();
        expect(Hash::check('newPassword456', $this->parentUser->password))->toBeTrue();
    });

    it('validates current password is correct', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->postJson('/api/v1/parent/profile/change-password', [
            'current_password' => 'wrongPassword',
            'password' => 'newPassword456',
            'password_confirmation' => 'newPassword456',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'error_code' => 'INVALID_CURRENT_PASSWORD',
            ]);
    });

    it('validates password confirmation matches', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->postJson('/api/v1/parent/profile/change-password', [
            'current_password' => 'password123',
            'password' => 'newPassword456',
            'password_confirmation' => 'differentPassword',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('validates password minimum length', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->postJson('/api/v1/parent/profile/change-password', [
            'current_password' => 'password123',
            'password' => 'short',
            'password_confirmation' => 'short',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });
});

describe('deleteAccount (soft delete account)', function () {
    it('successfully deletes account with correct password', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $userId = $this->parentUser->id;

        $response = $this->deleteJson('/api/v1/parent/profile', [
            'password' => 'password123',
            'confirmation' => 'DELETE',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'deleted' => true,
                ],
            ]);

        $this->assertSoftDeleted('users', [
            'id' => $userId,
        ]);
    });

    it('accepts Arabic confirmation text', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->deleteJson('/api/v1/parent/profile', [
            'password' => 'password123',
            'confirmation' => 'حذف',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);
    });

    it('validates password is correct', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->deleteJson('/api/v1/parent/profile', [
            'password' => 'wrongPassword',
            'confirmation' => 'DELETE',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'error_code' => 'INVALID_PASSWORD',
            ]);
    });

    it('validates confirmation text', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->deleteJson('/api/v1/parent/profile', [
            'password' => 'password123',
            'confirmation' => 'INVALID',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['confirmation']);
    });

    it('revokes all tokens on account deletion', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        // Create multiple tokens
        $this->parentUser->createToken('token1');
        $this->parentUser->createToken('token2');

        expect($this->parentUser->tokens()->count())->toBeGreaterThan(0);

        $response = $this->deleteJson('/api/v1/parent/profile', [
            'password' => 'password123',
            'confirmation' => 'DELETE',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        expect($this->parentUser->tokens()->count())->toBe(0);
    });
});
