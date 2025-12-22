<?php

use App\Models\Academy;
use App\Models\User;

describe('ParentProfileController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('show', function () {
        it('shows profile for authenticated parent', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();

            $response = $this->actingAs($parent)->get(route('parent.profile.show', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });

        it('denies access to non-parent users', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($student)->get(route('parent.profile.show', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(403);
        });
    });

    describe('edit', function () {
        it('shows edit form for authenticated parent', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();

            $response = $this->actingAs($parent)->get(route('parent.profile.edit', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });
    });

    describe('update', function () {
        it('updates parent profile', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();

            $response = $this->actingAs($parent)->put(route('parent.profile.update', [
                'subdomain' => $this->academy->subdomain,
            ]), [
                'name' => 'Updated Parent Name',
                'email' => $parent->email,
                'phone' => '0512345678',
            ]);

            $response->assertRedirect();
            expect($parent->fresh()->name)->toBe('Updated Parent Name');
        });
    });
});
