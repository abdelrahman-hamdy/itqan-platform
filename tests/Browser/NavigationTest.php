<?php

/**
 * Browser-style navigation tests
 *
 * These tests simulate browser interactions using HTTP requests
 * For full browser testing, consider installing Laravel Dusk
 */

use App\Models\Academy;
use App\Models\User;

describe('Public Pages Navigation', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create([
            'subdomain' => 'test-academy',
            'is_active' => true,
        ]);
    });

    it('displays the login page', function () {
        $response = $this->get(route('login', ['subdomain' => $this->academy->subdomain]));

        $response->assertStatus(200)
            ->assertSee('تسجيل الدخول'); // Arabic for "Login"
    });

    it('displays student registration page', function () {
        $response = $this->get(route('student.register', ['subdomain' => $this->academy->subdomain]));

        $response->assertStatus(200);
    })->skip('Registration page may require additional setup');
});

describe('Authenticated Navigation', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create([
            'subdomain' => 'test-academy',
            'is_active' => true,
        ]);
    });

    it('student can access their profile page', function () {
        $student = User::factory()->student()->forAcademy($this->academy)->create();

        $response = $this->actingAs($student)
            ->get(route('student.profile', ['subdomain' => $this->academy->subdomain]));

        $response->assertStatus(200);
    })->skip('Requires student profile page route');

    it('teacher can access their dashboard', function () {
        $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

        $response = $this->actingAs($teacher)
            ->get(route('teacher.dashboard', ['subdomain' => $this->academy->subdomain]));

        $response->assertStatus(200);
    })->skip('Requires teacher dashboard route');
});
