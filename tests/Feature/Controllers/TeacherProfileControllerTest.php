<?php

use App\Models\Academy;
use App\Models\QuranTeacherProfile;
use App\Models\User;

describe('TeacherProfileController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('show', function () {
        it('shows profile for authenticated teacher', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $response = $this->actingAs($teacher)->get(route('teacher.profile.show', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });

        it('denies access to non-teacher users', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($student)->get(route('teacher.profile.show', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(403);
        });
    });

    describe('edit', function () {
        it('shows edit form for authenticated teacher', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $response = $this->actingAs($teacher)->get(route('teacher.profile.edit', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });
    });

    describe('update', function () {
        it('updates teacher profile', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $response = $this->actingAs($teacher)->put(route('teacher.profile.update', [
                'subdomain' => $this->academy->subdomain,
            ]), [
                'name' => 'Updated Teacher Name',
                'email' => $teacher->email,
                'phone' => '0512345678',
            ]);

            $response->assertRedirect();
            expect($teacher->fresh()->name)->toBe('Updated Teacher Name');
        });
    });
});
