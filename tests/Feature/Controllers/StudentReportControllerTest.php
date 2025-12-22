<?php

use App\Models\Academy;
use App\Models\User;

describe('StudentReportController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('index', function () {
        it('shows reports for authenticated student', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($student)->get(route('student.reports.index', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });

        it('requires authentication', function () {
            $response = $this->get(route('student.reports.index', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertRedirect();
        });
    });

    describe('show', function () {
        it('shows specific report for authenticated student', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($student)->get(route('student.reports.show', [
                'subdomain' => $this->academy->subdomain,
                'type' => 'quran',
            ]));

            $response->assertStatus(200);
        });
    });
});
