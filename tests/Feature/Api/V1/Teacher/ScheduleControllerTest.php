<?php

use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses()->group('api', 'teacher', 'schedule');

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);
});

describe('Schedule API', function () {
    describe('weekly schedule', function () {
        it('returns weekly schedule for quran teacher', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            // Create sessions throughout the week
            for ($i = 0; $i < 7; $i++) {
                QuranSession::factory()->create([
                    'quran_teacher_id' => $profile->id,
                    'student_id' => $student->id,
                    'academy_id' => $this->academy->id,
                    'scheduled_at' => now()->startOfWeek()->addDays($i)->setTime(10, 0),
                    'status' => 'scheduled',
                ]);
            }

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/schedule', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'period' => [
                            'start_date',
                            'end_date',
                        ],
                        'schedule',
                        'total_sessions',
                    ],
                ]);

            expect($response->json('data.total_sessions'))->toBeGreaterThanOrEqual(7);
        });

        it('groups sessions by date', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $today = now();

            // Create multiple sessions on the same day
            QuranSession::factory()->count(3)->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'scheduled_at' => $today,
                'status' => 'scheduled',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/schedule', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $schedule = $response->json('data.schedule');
            $todayDate = $today->toDateString();

            expect($schedule)->toHaveKey($todayDate);
            expect(count($schedule[$todayDate]))->toBe(3);
        });

        it('filters by custom date range', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            // Sessions in range
            QuranSession::factory()->count(2)->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->addDays(2),
                'status' => 'scheduled',
            ]);

            // Session out of range
            QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->addDays(15),
                'status' => 'scheduled',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $startDate = now()->toDateString();
            $endDate = now()->addDays(7)->toDateString();

            $response = $this->getJson("/api/v1/teacher/schedule?start_date={$startDate}&end_date={$endDate}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            expect($response->json('data.total_sessions'))->toBe(2);
        });

        it('includes both quran and academic sessions', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacher->user_type = 'quran_teacher'; // Make teacher both types
            $teacher->save();

            $quranProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $academicProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            QuranSession::factory()->create([
                'quran_teacher_id' => $quranProfile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->addDay(),
                'status' => 'scheduled',
            ]);

            AcademicSession::factory()->create([
                'academic_teacher_id' => $academicProfile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->addDay(),
                'status' => 'scheduled',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/schedule', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            expect($response->json('data.total_sessions'))->toBeGreaterThanOrEqual(2);
        });

        it('excludes cancelled sessions', function () {
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
                'scheduled_at' => now()->addDay(),
                'status' => 'scheduled',
            ]);

            QuranSession::factory()->count(3)->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->addDay(),
                'status' => 'cancelled',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/schedule', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            expect($response->json('data.total_sessions'))->toBe(2);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/schedule', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('day schedule', function () {
        it('returns schedule for specific day', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $targetDate = now()->addDays(2);

            QuranSession::factory()->count(4)->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'scheduled_at' => $targetDate,
                'status' => 'scheduled',
            ]);

            // Sessions on other days
            QuranSession::factory()->count(2)->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->addDays(5),
                'status' => 'scheduled',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson("/api/v1/teacher/schedule/day/{$targetDate->toDateString()}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'date',
                        'day_name',
                        'sessions',
                        'total',
                    ],
                ]);

            expect($response->json('data.total'))->toBe(4);
        });

        it('sorts sessions by time', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $targetDate = now()->addDay();

            QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'scheduled_at' => $targetDate->copy()->setTime(14, 0),
                'status' => 'scheduled',
            ]);

            QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'scheduled_at' => $targetDate->copy()->setTime(10, 0),
                'status' => 'scheduled',
            ]);

            QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'scheduled_at' => $targetDate->copy()->setTime(16, 0),
                'status' => 'scheduled',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson("/api/v1/teacher/schedule/day/{$targetDate->toDateString()}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $sessions = $response->json('data.sessions');
            expect(count($sessions))->toBe(3);

            // Verify sessions are sorted by time
            $times = array_map(fn($s) => strtotime($s['scheduled_at']), $sessions);
            expect($times[0])->toBeLessThan($times[1]);
            expect($times[1])->toBeLessThan($times[2]);
        });

        it('returns empty array for day with no sessions', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $futureDate = now()->addMonths(6);

            $response = $this->getJson("/api/v1/teacher/schedule/day/{$futureDate->toDateString()}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            expect($response->json('data.total'))->toBe(0);
            expect($response->json('data.sessions'))->toBe([]);
        });

        it('validates date format', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/schedule/day/invalid-date', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(400);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/schedule/day/2024-01-15', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('only shows teacher own schedule', function () {
        it('does not show other teachers sessions', function () {
            $teacher1 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile1 = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher1->id,
                'academy_id' => $this->academy->id,
            ]);

            $teacher2 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile2 = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher2->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            QuranSession::factory()->count(2)->create([
                'quran_teacher_id' => $profile1->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->addDay(),
                'status' => 'scheduled',
            ]);

            QuranSession::factory()->count(3)->create([
                'quran_teacher_id' => $profile2->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->addDay(),
                'status' => 'scheduled',
            ]);

            Sanctum::actingAs($teacher1, ['*']);

            $response = $this->getJson('/api/v1/teacher/schedule', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            expect($response->json('data.total_sessions'))->toBe(2);
        });
    });
});
