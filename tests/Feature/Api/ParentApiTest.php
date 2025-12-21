<?php

use App\Models\Academy;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('Parent API', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create([
            'subdomain' => 'test-academy',
            'is_active' => true,
        ]);
    });

    describe('children endpoints', function () {
        it('allows parent to view their children list', function () {
            $parentUser = User::factory()->parent()->forAcademy($this->academy)->create();

            Sanctum::actingAs($parentUser, ['*']);

            $response = $this->getJson('/api/v1/parent/children', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);
        });

        it('prevents non-parents from accessing children endpoints', function () {
            $studentUser = User::factory()->student()->forAcademy($this->academy)->create();

            Sanctum::actingAs($studentUser, ['*']);

            $response = $this->getJson('/api/v1/parent/children', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            // Should be forbidden for non-parent users
            $response->assertStatus(403);
        });
    });

    describe('parent dashboard', function () {
        it('returns parent dashboard data', function () {
            $parentUser = User::factory()->parent()->forAcademy($this->academy)->create();

            Sanctum::actingAs($parentUser, ['*']);

            $response = $this->getJson('/api/v1/parent/dashboard', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);
        });
    });

    // Parent notifications endpoint does not exist in current API
    // Skipping notifications tests
});
