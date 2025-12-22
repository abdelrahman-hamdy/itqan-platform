<?php

use App\Models\Academy;
use App\Models\User;

describe('ParentCalendarController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('index', function () {
        it('shows calendar for authenticated parent', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $parentProfile->students()->attach($student->studentProfileUnscoped->id);

            $response = $this->actingAs($parent)->get(route('parent.calendar', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });

        it('denies access to non-parent users', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($student)->get(route('parent.calendar', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(403);
        });
    });

    describe('events', function () {
        it('returns calendar events as json', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $parentProfile->students()->attach($student->studentProfileUnscoped->id);

            $response = $this->actingAs($parent)->getJson(route('parent.calendar.events', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });
    });
});
