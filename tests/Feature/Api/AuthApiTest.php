<?php

use App\Models\Academy;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('API Authentication', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create([
            'subdomain' => 'test-academy',
            'is_active' => true,
        ]);
    });

    describe('token generation', function () {
        it('allows users to get API token with valid credentials', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create([
                'email' => 'api@example.com',
                'password' => bcrypt('password'),
            ]);

            $response = $this->postJson('/api/v1/login', [
                'email' => 'api@example.com',
                'password' => 'password',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure(['data' => ['token']]);
        });

        it('prevents token generation with invalid credentials', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create([
                'email' => 'api@example.com',
                'password' => bcrypt('password'),
            ]);

            $response = $this->postJson('/api/v1/login', [
                'email' => 'api@example.com',
                'password' => 'wrong-password',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('protected endpoints', function () {
        it('allows authenticated users to access protected endpoints', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Sanctum::actingAs($user, ['*']);

            $response = $this->getJson('/api/v1/me', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);
        });

        it('prevents unauthenticated access to protected endpoints', function () {
            $response = $this->getJson('/api/v1/me', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('token logout', function () {
        it('allows users to logout and revoke token', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Sanctum::actingAs($user, ['*']);

            $response = $this->postJson('/api/v1/logout', [], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);
        });
    });
});
