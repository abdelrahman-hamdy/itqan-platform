<?php

use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\User;

describe('ParentSessionController', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
    });

    describe('index', function () {
        it('returns sessions for linked children', function () {
            $parent = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentProfile = $parent->parentProfile;
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $parentProfile->students()->attach($student->studentProfileUnscoped->id);

            $subscription = QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_profile_id' => $student->studentProfileUnscoped->id,
                'quran_teacher_profile_id' => $teacherProfile->id,
                'subscription_code' => 'QS-' . uniqid(),
                'status' => 'active',
            ]);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'quran_subscription_id' => $subscription->id,
            ]);

            $response = $this->actingAs($parent)->get(route('parent.sessions.index', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(200);
        });

        it('denies access to non-parent users', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->actingAs($student)->get(route('parent.sessions.index', [
                'subdomain' => $this->academy->subdomain,
            ]));

            $response->assertStatus(403);
        });
    });
});
