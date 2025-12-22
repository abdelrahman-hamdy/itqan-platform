<?php

use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\StudentProfile;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses()->group('api', 'teacher', 'dashboard');

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);
});

describe('Teacher Dashboard API', function () {
    it('returns dashboard data for quran teacher', function () {
        $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $profile = QuranTeacherProfile::factory()->create([
            'user_id' => $teacher->id,
            'academy_id' => $this->academy->id,
        ]);

        Sanctum::actingAs($teacher, ['*']);

        $response = $this->getJson('/api/v1/teacher/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'teacher' => [
                        'id',
                        'name',
                        'avatar',
                        'is_quran_teacher',
                        'is_academic_teacher',
                    ],
                    'stats' => [
                        'total_students',
                        'today_sessions',
                        'upcoming_sessions',
                        'completed_sessions_this_month',
                    ],
                    'today_sessions',
                    'upcoming_sessions',
                    'recent_activity',
                ],
                'message',
            ]);

        expect($response->json('data.teacher.is_quran_teacher'))->toBeTrue();
        expect($response->json('data.teacher.is_academic_teacher'))->toBeFalse();
    });

    it('returns dashboard data for academic teacher', function () {
        $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
        $profile = AcademicTeacherProfile::factory()->create([
            'user_id' => $teacher->id,
            'academy_id' => $this->academy->id,
        ]);

        Sanctum::actingAs($teacher, ['*']);

        $response = $this->getJson('/api/v1/teacher/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        expect($response->json('data.teacher.is_quran_teacher'))->toBeFalse();
        expect($response->json('data.teacher.is_academic_teacher'))->toBeTrue();
    });

    it('includes quran session stats for quran teacher', function () {
        $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $profile = QuranTeacherProfile::factory()->create([
            'user_id' => $teacher->id,
            'academy_id' => $this->academy->id,
        ]);

        $student = User::factory()->student()->forAcademy($this->academy)->create();

        // Create sessions
        QuranSession::factory()->count(3)->create([
            'quran_teacher_id' => $profile->id,
            'student_id' => $student->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        QuranSession::factory()->count(2)->create([
            'quran_teacher_id' => $profile->id,
            'student_id' => $student->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now(),
            'status' => 'scheduled',
        ]);

        QuranSession::factory()->count(5)->create([
            'quran_teacher_id' => $profile->id,
            'student_id' => $student->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->startOfMonth(),
            'status' => 'completed',
        ]);

        Sanctum::actingAs($teacher, ['*']);

        $response = $this->getJson('/api/v1/teacher/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $stats = $response->json('data.stats');
        expect($stats['today_sessions'])->toBe(2);
        expect($stats['upcoming_sessions'])->toBeGreaterThanOrEqual(3);
        expect($stats['completed_sessions_this_month'])->toBeGreaterThanOrEqual(5);
    });

    it('includes academic session stats for academic teacher', function () {
        $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
        $profile = AcademicTeacherProfile::factory()->create([
            'user_id' => $teacher->id,
            'academy_id' => $this->academy->id,
        ]);

        $student = User::factory()->student()->forAcademy($this->academy)->create();

        // Create sessions
        AcademicSession::factory()->count(2)->create([
            'academic_teacher_id' => $profile->id,
            'student_id' => $student->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now(),
            'status' => 'scheduled',
        ]);

        Sanctum::actingAs($teacher, ['*']);

        $response = $this->getJson('/api/v1/teacher/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $stats = $response->json('data.stats');
        expect($stats['today_sessions'])->toBe(2);
    });

    it('returns today sessions ordered by time', function () {
        $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $profile = QuranTeacherProfile::factory()->create([
            'user_id' => $teacher->id,
            'academy_id' => $this->academy->id,
        ]);

        $student = User::factory()->student()->forAcademy($this->academy)->create();

        QuranSession::factory()->create([
            'quran_teacher_id' => $profile->id,
            'student_id' => $student->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->setTime(14, 0),
            'status' => 'scheduled',
        ]);

        QuranSession::factory()->create([
            'quran_teacher_id' => $profile->id,
            'student_id' => $student->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->setTime(10, 0),
            'status' => 'scheduled',
        ]);

        Sanctum::actingAs($teacher, ['*']);

        $response = $this->getJson('/api/v1/teacher/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $todaySessions = $response->json('data.today_sessions');
        expect(count($todaySessions))->toBe(2);

        // Verify sessions are ordered by time
        $firstTime = strtotime($todaySessions[0]['scheduled_at']);
        $secondTime = strtotime($todaySessions[1]['scheduled_at']);
        expect($firstTime)->toBeLessThan($secondTime);
    });

    it('excludes cancelled sessions from stats', function () {
        $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $profile = QuranTeacherProfile::factory()->create([
            'user_id' => $teacher->id,
            'academy_id' => $this->academy->id,
        ]);

        $student = User::factory()->student()->forAcademy($this->academy)->create();

        QuranSession::factory()->count(2)->create([
            'quran_teacher_id' => $profile->id,
            'student_id' => $student->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now(),
            'status' => 'cancelled',
        ]);

        Sanctum::actingAs($teacher, ['*']);

        $response = $this->getJson('/api/v1/teacher/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $stats = $response->json('data.stats');
        expect($stats['today_sessions'])->toBe(0);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/teacher/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(401);
    });

    it('prevents non-teachers from accessing dashboard', function () {
        $student = User::factory()->student()->forAcademy($this->academy)->create();

        Sanctum::actingAs($student, ['*']);

        $response = $this->getJson('/api/v1/teacher/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(403);
    });
});
