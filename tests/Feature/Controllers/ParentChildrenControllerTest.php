<?php

use App\Models\Academy;
use App\Models\User;

describe('ParentChildrenController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('index', function () {
        it('returns children list for authenticated parent', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $parentProfile->students()->attach($student->studentProfileUnscoped->id);

            $response = $this->actingAs($parent)->get(route('parent.children.index', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });

        it('denies access to non-parent users', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($student)->get(route('parent.children.index', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(403);
        });
    });

    describe('show', function () {
        it('shows child details for linked parent', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $parentProfile->students()->attach($student->studentProfileUnscoped->id);

            $response = $this->actingAs($parent)->get(route('parent.children.show', [
                'subdomain' => $this->academy->subdomain,
                'child' => $student->studentProfileUnscoped->id,
            ]));

            $response->assertStatus(200);
        });

        it('denies access to unlinked child', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            // No link between parent and student

            $response = $this->actingAs($parent)->get(route('parent.children.show', [
                'subdomain' => $this->academy->subdomain,
                'child' => $student->studentProfileUnscoped->id,
            ]));

            $response->assertStatus(403);
        });
    });
});
