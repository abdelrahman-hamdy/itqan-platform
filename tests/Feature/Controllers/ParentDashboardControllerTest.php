<?php

use App\Models\Academy;
use App\Models\User;

describe('ParentDashboardController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('index', function () {
        it('returns dashboard for authenticated parent', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $parentProfile->students()->attach($student->studentProfileUnscoped->id);

            $response = $this->actingAs($parent)->get(route('parent.dashboard', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });

        it('denies access to non-parent users', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($student)->get(route('parent.dashboard', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(403);
        });
    });
});
