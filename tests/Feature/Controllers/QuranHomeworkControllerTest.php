<?php

use App\Models\Academy;
use App\Models\User;

describe('QuranHomeworkController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('index', function () {
        it('returns homework for authenticated student', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($student)->get(route('student.homework.quran.index', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });

        it('requires authentication', function () {
            $response = $this->get(route('student.homework.quran.index', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertRedirect();
        });
    });
});
