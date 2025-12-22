<?php

use App\Models\Academy;
use App\Models\User;

describe('StudentCalendarController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('index', function () {
        it('shows calendar for authenticated student', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($student)->get(route('student.calendar', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });

        it('requires authentication', function () {
            $response = $this->get(route('student.calendar', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertRedirect();
        });
    });

    describe('events', function () {
        it('returns calendar events as json', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($student)->getJson(route('student.calendar.events', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
            $response->assertJsonStructure([]);
        });
    });
});
