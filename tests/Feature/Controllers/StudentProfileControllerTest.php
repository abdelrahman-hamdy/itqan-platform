<?php

use App\Models\Academy;
use App\Models\User;

describe('StudentProfileController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('show', function () {
        it('shows profile for authenticated student', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($student)->get(route('student.profile.show', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });

        it('requires authentication', function () {
            $response = $this->get(route('student.profile.show', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertRedirect();
        });
    });

    describe('edit', function () {
        it('shows edit form for authenticated student', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($student)->get(route('student.profile.edit', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });
    });

    describe('update', function () {
        it('updates student profile', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($student)->put(route('student.profile.update', [
                'subdomain' => $this->academy->subdomain,
            ]), [
                'name' => 'Updated Name',
                'email' => $student->email,
                'phone' => '0512345678',
            ]);

            $response->assertRedirect();
            expect($student->fresh()->name)->toBe('Updated Name');
        });
    });
});
