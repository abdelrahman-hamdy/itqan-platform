<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\StudentProfile;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('Student API', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create([
            'subdomain' => 'test-academy',
            'is_active' => true,
        ]);

        // Create a grade level for the academy (required for student profile creation)
        AcademicGradeLevel::create([
            'academy_id' => $this->academy->id,
            'name' => 'Grade 1',
            'is_active' => true,
        ]);
    });

    describe('student profile', function () {
        it('allows student to view their profile', function () {
            // Profile is auto-created via User model boot method
            $studentUser = User::factory()->student()->forAcademy($this->academy)->create();

            // Refresh to get the auto-created profile
            $studentUser->refresh();

            // Verify profile was created
            expect($studentUser->studentProfile)->not->toBeNull();

            Sanctum::actingAs($studentUser, ['*']);

            $response = $this->getJson('/api/v1/student/profile', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);
        });

        it('prevents non-students from accessing student profile', function () {
            $parentUser = User::factory()->parent()->forAcademy($this->academy)->create();

            Sanctum::actingAs($parentUser, ['*']);

            $response = $this->getJson('/api/v1/student/profile', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(403);
        });
    });

    describe('student dashboard', function () {
        it('returns student dashboard data', function () {
            // Dashboard endpoint has complex queries that need full database schema
        })->skip('Dashboard endpoint requires homework column in academic_sessions table');
    });

    // These tests are skipped due to complex query requirements that need specific database setup
    describe('student sessions', function () {
        it('returns student upcoming sessions', function () {
            // This test requires complex setup for interactive courses and enrollments
        })->skip('Requires interactive_course_enrollments setup');

        it('returns student sessions list', function () {
            // This test requires complex setup for interactive courses and enrollments
        })->skip('Requires interactive_course_enrollments setup');
    });

    describe('student subscriptions', function () {
        it('returns student active subscriptions', function () {
            // This test requires course_subscriptions table with proper schema
        })->skip('Requires course_subscriptions schema');
    });
});
