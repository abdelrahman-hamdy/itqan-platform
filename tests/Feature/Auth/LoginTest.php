<?php

use App\Models\Academy;
use App\Models\User;

describe('Login Feature', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create([
            'subdomain' => 'test-academy',
            'is_active' => true,
        ]);
    });

    describe('login page', function () {
        it('displays the login page', function () {
            // The login route requires a subdomain parameter
            $response = $this->get(route('login', ['subdomain' => $this->academy->subdomain]));

            $response->assertStatus(200);
        });

        it('shows login page or redirects authenticated users', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($user)->get(route('login', ['subdomain' => $this->academy->subdomain]));

            // Application may either redirect or show a different view for authenticated users
            expect($response->status())->toBeIn([200, 302]);
        });
    });

    describe('login attempts', function () {
        it('allows users to authenticate with valid credentials', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create([
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);

            $response = $this->post(route('login.post', ['subdomain' => $this->academy->subdomain]), [
                'email' => 'test@example.com',
                'password' => 'password',
            ]);

            $this->assertAuthenticated();
        });

        it('prevents authentication with invalid password', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create([
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);

            $response = $this->post(route('login.post', ['subdomain' => $this->academy->subdomain]), [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);

            $this->assertGuest();
        });

        it('prevents authentication with non-existent email', function () {
            $response = $this->post(route('login.post', ['subdomain' => $this->academy->subdomain]), [
                'email' => 'nonexistent@example.com',
                'password' => 'password',
            ]);

            $this->assertGuest();
        });

        it('prevents inactive users from logging in', function () {
            $user = User::factory()->inactive()->forAcademy($this->academy)->create([
                'email' => 'inactive@example.com',
                'password' => bcrypt('password'),
            ]);

            $response = $this->post(route('login.post', ['subdomain' => $this->academy->subdomain]), [
                'email' => 'inactive@example.com',
                'password' => 'password',
            ]);

            // Either guest or redirected with error
            $this->assertGuest();
        });
    });

    describe('logout', function () {
        it('allows authenticated users to logout', function () {
            $user = User::factory()->forAcademy($this->academy)->create();

            $response = $this->actingAs($user)->post(route('logout', ['subdomain' => $this->academy->subdomain]));

            $this->assertGuest();
        });
    });

    describe('role-based authentication', function () {
        it('authenticates students successfully', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create([
                'email' => 'student@example.com',
                'password' => bcrypt('password'),
            ]);

            $response = $this->post(route('login.post', ['subdomain' => $this->academy->subdomain]), [
                'email' => 'student@example.com',
                'password' => 'password',
            ]);

            $this->assertAuthenticated();
            expect(auth()->user()->isStudent())->toBeTrue();
        });

        it('authenticates teachers successfully', function () {
            $user = User::factory()->quranTeacher()->forAcademy($this->academy)->create([
                'email' => 'teacher@example.com',
                'password' => bcrypt('password'),
            ]);

            $response = $this->post(route('login.post', ['subdomain' => $this->academy->subdomain]), [
                'email' => 'teacher@example.com',
                'password' => 'password',
            ]);

            $this->assertAuthenticated();
            expect(auth()->user()->isQuranTeacher())->toBeTrue();
        });

        it('authenticates parents successfully', function () {
            $user = User::factory()->parent()->forAcademy($this->academy)->create([
                'email' => 'parent@example.com',
                'password' => bcrypt('password'),
            ]);

            $response = $this->post(route('login.post', ['subdomain' => $this->academy->subdomain]), [
                'email' => 'parent@example.com',
                'password' => 'password',
            ]);

            $this->assertAuthenticated();
            expect(auth()->user()->isParent())->toBeTrue();
        });
    });
});
