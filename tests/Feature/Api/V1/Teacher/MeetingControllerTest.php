<?php

use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses()->group('api', 'teacher', 'meetings');

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);
});

describe('Meeting API', function () {
    describe('create meeting', function () {
        it('creates meeting for quran session', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'status' => 'scheduled',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson('/api/v1/teacher/meetings/create', [
                'session_type' => 'quran',
                'session_id' => $session->id,
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'meeting_link',
                        'room_name',
                    ],
                ]);
        });

        it('creates meeting for academic session', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academic_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'status' => 'scheduled',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson('/api/v1/teacher/meetings/create', [
                'session_type' => 'academic',
                'session_id' => $session->id,
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(201);
        });

        it('validates session type', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson('/api/v1/teacher/meetings/create', [
                'session_type' => 'invalid',
                'session_id' => 1,
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['session_type']);
        });

        it('prevents creating meeting for other teachers session', function () {
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

            $session = QuranSession::factory()->create([
                'quran_teacher_id' => $profile2->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher1, ['*']);

            $response = $this->postJson('/api/v1/teacher/meetings/create', [
                'session_type' => 'quran',
                'session_id' => $session->id,
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $response = $this->postJson('/api/v1/teacher/meetings/create', [
                'session_type' => 'quran',
                'session_id' => 1,
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('get meeting token', function () {
        it('generates token for teacher', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'status' => 'scheduled',
                'meeting_room_name' => 'test-room-123',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson("/api/v1/teacher/meetings/quran/{$session->id}/token", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'token',
                        'room_name',
                        'server_url',
                        'participant' => [
                            'identity',
                            'name',
                            'is_teacher',
                        ],
                    ],
                ]);

            expect($response->json('data.participant.is_teacher'))->toBeTrue();
        });

        it('returns error if no meeting exists', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'status' => 'scheduled',
                'meeting_room_name' => null,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson("/api/v1/teacher/meetings/quran/{$session->id}/token", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(400);
        });

        it('prevents token generation for cancelled session', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'status' => 'cancelled',
                'meeting_room_name' => 'test-room-123',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson("/api/v1/teacher/meetings/quran/{$session->id}/token", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(400);
        });

        it('prevents access to other teachers session', function () {
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

            $session = QuranSession::factory()->create([
                'quran_teacher_id' => $profile2->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'meeting_room_name' => 'test-room-123',
            ]);

            Sanctum::actingAs($teacher1, ['*']);

            $response = $this->getJson("/api/v1/teacher/meetings/quran/{$session->id}/token", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/meetings/quran/1/token', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });
});
