<?php

use App\Models\Academy;
use App\Models\User;

describe('MeetingController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('join', function () {
        it('requires authentication to join meeting', function () {
            $response = $this->get(route('meetings.join', [
                'subdomain' => $this->academy->subdomain,
                'meetingId' => 'test-meeting-123',
            ]));

            $response->assertRedirect();
        });

        it('allows authenticated user to attempt join', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($student)->get(route('meetings.join', [
                'subdomain' => $this->academy->subdomain,
                'meetingId' => 'test-meeting-123',
            ]));

            // Should either show meeting page or return error if meeting doesn't exist
            expect($response->status())->toBeIn([200, 302, 404]);
        });
    });
});
