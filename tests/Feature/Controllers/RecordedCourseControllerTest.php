<?php

use App\Models\Academy;
use App\Models\CourseSubscription;
use App\Models\RecordedCourse;
use App\Models\User;

describe('RecordedCourseController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('index', function () {
        it('returns courses for authenticated student', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($student)->get(route('student.courses.index', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });
    });

    describe('show', function () {
        it('shows course details for subscribed student', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $course = RecordedCourse::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            CourseSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
                'recorded_course_id' => $course->id,
                'subscription_code' => 'CS-' . uniqid(),
                'status' => 'active',
                'start_date' => now(),
                'end_date' => now()->addMonth(),
            ]);

            $response = $this->actingAs($student)->get(route('student.courses.show', [
                'subdomain' => $this->academy->subdomain,
                'course' => $course->id,
            ]));

            $response->assertStatus(200);
        });
    });
});
