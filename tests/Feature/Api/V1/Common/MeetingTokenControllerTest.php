<?php

use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\BaseSessionMeeting;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Services\LiveKitService;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\Sanctum;

describe('Meeting Token API', function () {
    beforeEach(function () {
        $this->academy = createAcademy();
        $this->student = createUser('student', $this->academy);

        // Mock LiveKit configuration
        Config::set('livekit.api_key', 'test-api-key');
        Config::set('livekit.api_secret', 'test-api-secret');
        Config::set('livekit.server_url', 'wss://test.livekit.local');

        Sanctum::actingAs($this->student);
    });

    describe('Quran Session Tokens', function () {
        beforeEach(function () {
            $this->teacher = createUser('quran_teacher', $this->academy);
            $this->teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $this->session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->addMinutes(5),
                'duration_minutes' => 45,
                'status' => 'scheduled',
            ]);

            $this->meeting = BaseSessionMeeting::factory()->create([
                'sessionable_type' => QuranSession::class,
                'sessionable_id' => $this->session->id,
                'room_name' => 'test-quran-session-' . $this->session->id,
                'status' => 'scheduled',
            ]);
        });

        it('generates token for student in joinable session', function () {
            $response = $this->getJson("/api/v1/common/meetings/quran/{$this->session->id}/token");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'token',
                        'room_name',
                        'livekit_url',
                        'participant' => [
                            'identity',
                            'name',
                            'role',
                        ],
                        'session' => [
                            'id',
                            'type',
                            'title',
                            'duration_minutes',
                        ],
                        'expires_in',
                    ],
                ]);

            expect($response->json('data.participant.role'))->toBe('student')
                ->and($response->json('data.room_name'))->toBe($this->meeting->room_name)
                ->and($response->json('data.session.type'))->toBe('quran');
        });

        it('generates token for teacher in joinable session', function () {
            Sanctum::actingAs($this->teacher);

            $response = $this->getJson("/api/v1/common/meetings/quran/{$this->session->id}/token");

            $response->assertStatus(200);
            expect($response->json('data.participant.role'))->toBe('teacher');
        });

        it('returns 404 for non-existent session', function () {
            $response = $this->getJson('/api/v1/common/meetings/quran/99999/token');

            $response->assertStatus(404);
        });

        it('returns 404 when user is not authorized to access session', function () {
            $otherStudent = createUser('student', $this->academy);
            Sanctum::actingAs($otherStudent);

            $response = $this->getJson("/api/v1/common/meetings/quran/{$this->session->id}/token");

            $response->assertStatus(404);
        });

        it('returns error when session cannot be joined yet', function () {
            $this->session->update([
                'scheduled_at' => now()->addHours(2),
            ]);

            $response = $this->getJson("/api/v1/common/meetings/quran/{$this->session->id}/token");

            $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'error_code' => 'SESSION_NOT_JOINABLE',
                ]);
        });

        it('returns error when session is cancelled', function () {
            $this->session->update(['status' => 'cancelled']);

            $response = $this->getJson("/api/v1/common/meetings/quran/{$this->session->id}/token");

            $response->assertStatus(400);
        });

        it('returns error when session is completed', function () {
            $this->session->update(['status' => 'completed']);

            $response = $this->getJson("/api/v1/common/meetings/quran/{$this->session->id}/token");

            $response->assertStatus(400);
        });

        it('returns error when meeting is not available', function () {
            $this->meeting->delete();

            $response = $this->getJson("/api/v1/common/meetings/quran/{$this->session->id}/token");

            $response->assertStatus(400)
                ->assertJson([
                    'error_code' => 'MEETING_NOT_AVAILABLE',
                ]);
        });

        it('allows teacher to join early in preparation window', function () {
            Sanctum::actingAs($this->teacher);

            $this->session->update([
                'scheduled_at' => now()->addMinutes(8), // Within 10 min prep window
            ]);

            $response = $this->getJson("/api/v1/common/meetings/quran/{$this->session->id}/token");

            $response->assertStatus(200);
        });
    });

    describe('Academic Session Tokens', function () {
        beforeEach(function () {
            $this->teacher = createUser('academic_teacher', $this->academy);
            $this->teacherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $this->session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->addMinutes(5),
                'duration_minutes' => 60,
                'status' => 'scheduled',
            ]);

            $this->meeting = BaseSessionMeeting::factory()->create([
                'sessionable_type' => AcademicSession::class,
                'sessionable_id' => $this->session->id,
                'room_name' => 'test-academic-session-' . $this->session->id,
                'status' => 'scheduled',
            ]);
        });

        it('generates token for academic session', function () {
            $response = $this->getJson("/api/v1/common/meetings/academic/{$this->session->id}/token");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'token',
                        'room_name',
                        'participant',
                        'session',
                    ],
                ]);

            expect($response->json('data.session.type'))->toBe('academic');
        });

        it('handles academic teacher authorization correctly', function () {
            Sanctum::actingAs($this->teacher);

            $response = $this->getJson("/api/v1/common/meetings/academic/{$this->session->id}/token");

            $response->assertStatus(200);
            expect($response->json('data.participant.role'))->toBe('teacher');
        });
    });

    describe('Interactive Course Session Tokens', function () {
        beforeEach(function () {
            $this->teacher = createUser('academic_teacher', $this->academy);
            $this->teacherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $this->course = InteractiveCourse::factory()->create([
                'assigned_teacher_id' => $this->teacherProfile->id,
            ]);

            $this->course->enrollments()->create([
                'user_id' => $this->student->id,
                'status' => 'active',
            ]);

            $this->session = InteractiveCourseSession::factory()->create([
                'course_id' => $this->course->id,
                'scheduled_at' => now()->addMinutes(5),
                'duration_minutes' => 90,
                'status' => 'scheduled',
            ]);

            $this->meeting = BaseSessionMeeting::factory()->create([
                'sessionable_type' => InteractiveCourseSession::class,
                'sessionable_id' => $this->session->id,
                'room_name' => 'test-interactive-session-' . $this->session->id,
                'status' => 'scheduled',
            ]);
        });

        it('generates token for enrolled student', function () {
            $response = $this->getJson("/api/v1/common/meetings/interactive/{$this->session->id}/token");

            $response->assertStatus(200);
            expect($response->json('data.session.type'))->toBe('interactive');
        });

        it('returns 404 for non-enrolled student', function () {
            $otherStudent = createUser('student', $this->academy);
            Sanctum::actingAs($otherStudent);

            $response = $this->getJson("/api/v1/common/meetings/interactive/{$this->session->id}/token");

            $response->assertStatus(404);
        });

        it('allows course teacher to join', function () {
            Sanctum::actingAs($this->teacher);

            $response = $this->getJson("/api/v1/common/meetings/interactive/{$this->session->id}/token");

            $response->assertStatus(200);
            expect($response->json('data.participant.role'))->toBe('teacher');
        });
    });

    describe('GET /api/v1/common/meetings/{type}/{id}/info', function () {
        beforeEach(function () {
            $this->teacher = createUser('quran_teacher', $this->academy);
            $this->teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $this->session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->addMinutes(5),
                'duration_minutes' => 45,
                'status' => 'scheduled',
            ]);

            $this->meeting = BaseSessionMeeting::factory()->create([
                'sessionable_type' => QuranSession::class,
                'sessionable_id' => $this->session->id,
                'room_name' => 'test-session-' . $this->session->id,
                'status' => 'scheduled',
            ]);
        });

        it('returns meeting info without generating token', function () {
            $response = $this->getJson("/api/v1/common/meetings/quran/{$this->session->id}/info");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'session' => [
                            'id',
                            'type',
                            'title',
                            'status',
                            'scheduled_at',
                            'duration_minutes',
                        ],
                        'meeting' => [
                            'id',
                            'room_name',
                            'status',
                            'is_active',
                        ],
                        'participant' => [
                            'role',
                            'can_join',
                        ],
                        'join_window' => [
                            'opens_at',
                            'closes_at',
                        ],
                    ],
                ]);
        });

        it('shows correct can_join status based on timing', function () {
            // Session too far in future
            $this->session->update(['scheduled_at' => now()->addHours(2)]);

            $response = $this->getJson("/api/v1/common/meetings/quran/{$this->session->id}/info");

            $response->assertStatus(200);
            expect($response->json('data.participant.can_join'))->toBeFalse();

            // Session in joinable window
            $this->session->update(['scheduled_at' => now()->addMinutes(5)]);

            $response = $this->getJson("/api/v1/common/meetings/quran/{$this->session->id}/info");

            expect($response->json('data.participant.can_join'))->toBeTrue();
        });

        it('shows teacher role correctly', function () {
            Sanctum::actingAs($this->teacher);

            $response = $this->getJson("/api/v1/common/meetings/quran/{$this->session->id}/info");

            $response->assertStatus(200);
            expect($response->json('data.participant.role'))->toBe('teacher');
        });
    });

    describe('Authentication', function () {
        it('requires authentication for token generation', function () {
            Sanctum::actingAs(null);

            $response = $this->getJson('/api/v1/common/meetings/quran/1/token');

            $response->assertStatus(401);
        });

        it('requires authentication for meeting info', function () {
            Sanctum::actingAs(null);

            $response = $this->getJson('/api/v1/common/meetings/quran/1/info');

            $response->assertStatus(401);
        });
    });
});
