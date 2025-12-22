<?php

use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\Circle;
use App\Models\IndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\SessionAttendance;
use Laravel\Sanctum\Sanctum;

describe('Session Status API', function () {
    beforeEach(function () {
        $this->academy = createAcademy();
        $this->student = createUser('student', $this->academy);
        Sanctum::actingAs($this->student);
    });

    describe('Academic Session Status', function () {
        beforeEach(function () {
            $this->teacher = createUser('academic_teacher', $this->academy);
            $this->teacherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
            ]);
        });

        it('returns status for scheduled session in future', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->addHours(2),
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $response = $this->getJson("/api/sessions/academic/{$session->id}/status");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'button_text',
                    'button_class',
                    'can_join',
                    'session_type',
                ]);

            expect($response->json('status'))->toBe('scheduled')
                ->and($response->json('can_join'))->toBeFalse()
                ->and($response->json('session_type'))->toBe('academic');
        });

        it('returns joinable status for ongoing session', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->subMinutes(10),
                'duration_minutes' => 60,
                'status' => SessionStatus::ONGOING,
            ]);

            $response = $this->getJson("/api/sessions/academic/{$session->id}/status");

            $response->assertStatus(200);
            expect($response->json('status'))->toBe('ongoing')
                ->and($response->json('can_join'))->toBeTrue()
                ->and($response->json('button_text'))->toBe('انضم للجلسة');
        });

        it('returns non-joinable status for completed session', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->subHours(2),
                'status' => SessionStatus::COMPLETED,
            ]);

            $response = $this->getJson("/api/sessions/academic/{$session->id}/status");

            $response->assertStatus(200);
            expect($response->json('status'))->toBe('completed')
                ->and($response->json('can_join'))->toBeFalse()
                ->and($response->json('button_text'))->toBe('الجلسة منتهية');
        });

        it('returns non-joinable status for cancelled session', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::CANCELLED,
            ]);

            $response = $this->getJson("/api/sessions/academic/{$session->id}/status");

            $response->assertStatus(200);
            expect($response->json('status'))->toBe('cancelled')
                ->and($response->json('can_join'))->toBeFalse()
                ->and($response->json('button_class'))->toContain('red');
        });

        it('allows teacher to join in preparation window', function () {
            Sanctum::actingAs($this->teacher);

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->addMinutes(10), // 10 minutes in future
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $response = $this->getJson("/api/sessions/academic/{$session->id}/status");

            $response->assertStatus(200);
            expect($response->json('can_join'))->toBeTrue();
        });

        it('prevents student from joining too early', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->addMinutes(20),
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $response = $this->getJson("/api/sessions/academic/{$session->id}/status");

            $response->assertStatus(200);
            expect($response->json('can_join'))->toBeFalse();
        });

        it('requires authentication', function () {
            Sanctum::actingAs(null);

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $response = $this->getJson("/api/sessions/academic/{$session->id}/status");

            $response->assertStatus(401)
                ->assertJson([
                    'status' => 'unauthenticated',
                    'can_join' => false,
                ]);
        });

        it('returns 404 for non-existent session', function () {
            $response = $this->getJson('/api/sessions/academic/99999/status');

            $response->assertStatus(404);
        });
    });

    describe('Quran Session Status', function () {
        beforeEach(function () {
            $this->teacher = createUser('quran_teacher', $this->academy);
            $this->teacherProfile = QuranTeacherProfile::factory()->create([
                'user_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
            ]);
        });

        it('returns status for quran session', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->addMinutes(5),
                'duration_minutes' => 45,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $response = $this->getJson("/api/sessions/quran/{$session->id}/status");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'button_text',
                    'button_class',
                    'can_join',
                    'session_type',
                ]);

            expect($response->json('session_type'))->toBe('quran');
        });

        it('uses custom preparation minutes from circle settings', function () {
            Sanctum::actingAs($this->teacher);

            $circle = Circle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'preparation_minutes' => 20, // Custom prep time
            ]);

            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'circle_id' => $circle->id,
                'scheduled_at' => now()->addMinutes(18), // 18 minutes in future
                'status' => SessionStatus::SCHEDULED,
            ]);

            $response = $this->getJson("/api/sessions/quran/{$session->id}/status");

            $response->assertStatus(200);
            // Teacher should be able to join (18 < 20)
            expect($response->json('can_join'))->toBeTrue();
        });

        it('uses preparation minutes from individual circle', function () {
            Sanctum::actingAs($this->teacher);

            $individualCircle = IndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacherProfile->id,
                'preparation_minutes' => 25,
            ]);

            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'individual_circle_id' => $individualCircle->id,
                'scheduled_at' => now()->addMinutes(22),
                'status' => SessionStatus::SCHEDULED,
            ]);

            $response = $this->getJson("/api/sessions/quran/{$session->id}/status");

            $response->assertStatus(200);
            expect($response->json('can_join'))->toBeTrue();
        });

        it('handles ready status correctly', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::READY,
            ]);

            $response = $this->getJson("/api/sessions/quran/{$session->id}/status");

            $response->assertStatus(200);
            expect($response->json('status'))->toBe('ready')
                ->and($response->json('can_join'))->toBeFalse();
        });
    });

    describe('Academic Session Attendance Status', function () {
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
            ]);
        });

        it('returns attendance status when attendance exists', function () {
            $attendance = SessionAttendance::factory()->create([
                'attendanceable_type' => AcademicSession::class,
                'attendanceable_id' => $this->session->id,
                'user_id' => $this->student->id,
                'first_join_time' => now()->subMinutes(30),
                'last_leave_time' => now()->subMinutes(10),
                'total_duration_minutes' => 20,
                'attendance_percentage' => 90,
                'attendance_status' => 'present',
            ]);

            $response = $this->getJson("/api/sessions/academic/{$this->session->id}/attendance");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'has_attendance',
                    'attendance_status',
                    'first_join_time',
                    'last_leave_time',
                    'total_duration_minutes',
                    'attendance_percentage',
                ]);

            expect($response->json('has_attendance'))->toBeTrue()
                ->and($response->json('attendance_status'))->toBe('present')
                ->and($response->json('total_duration_minutes'))->toBe(20);
        });

        it('returns no attendance message when no attendance record exists', function () {
            $response = $this->getJson("/api/sessions/academic/{$this->session->id}/attendance");

            $response->assertStatus(200)
                ->assertJson([
                    'has_attendance' => false,
                    'attendance_status' => null,
                ]);
        });

        it('requires authentication', function () {
            Sanctum::actingAs(null);

            $response = $this->getJson("/api/sessions/academic/{$this->session->id}/attendance");

            $response->assertStatus(401);
        });
    });

    describe('Quran Session Attendance Status', function () {
        beforeEach(function () {
            $this->teacher = createUser('quran_teacher', $this->academy);

            $this->session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
            ]);
        });

        it('returns attendance status for quran session', function () {
            SessionAttendance::factory()->create([
                'attendanceable_type' => QuranSession::class,
                'attendanceable_id' => $this->session->id,
                'user_id' => $this->student->id,
                'attendance_status' => 'present',
                'total_duration_minutes' => 40,
                'attendance_percentage' => 95,
            ]);

            $response = $this->getJson("/api/sessions/quran/{$this->session->id}/attendance");

            $response->assertStatus(200);
            expect($response->json('has_attendance'))->toBeTrue()
                ->and($response->json('attendance_status'))->toBe('present');
        });
    });

    describe('Session Status Display', function () {
        beforeEach(function () {
            $this->teacher = createUser('academic_teacher', $this->academy);
            $this->teacherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
            ]);
        });

        it('shows correct button styling for ongoing session', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->subMinutes(5),
                'status' => SessionStatus::ONGOING,
            ]);

            $response = $this->getJson("/api/sessions/academic/{$session->id}/status");

            $response->assertStatus(200);
            expect($response->json('button_class'))->toContain('green');
        });

        it('shows correct button styling for cancelled session', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::CANCELLED,
            ]);

            $response = $this->getJson("/api/sessions/academic/{$session->id}/status");

            $response->assertStatus(200);
            expect($response->json('button_class'))->toContain('red');
        });

        it('returns Arabic messages', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            $response = $this->getJson("/api/sessions/academic/{$session->id}/status");

            $response->assertStatus(200);
            expect($response->json('message'))->toContain('تم');
        });
    });

    describe('Join Window Logic', function () {
        beforeEach(function () {
            $this->teacher = createUser('academic_teacher', $this->academy);
            $this->teacherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $this->teacher->id,
                'academy_id' => $this->academy->id,
            ]);
        });

        it('allows joining during scheduled time', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->subMinutes(5),
                'duration_minutes' => 60,
                'status' => SessionStatus::ONGOING,
            ]);

            $response = $this->getJson("/api/sessions/academic/{$session->id}/status");

            $response->assertStatus(200);
            expect($response->json('can_join'))->toBeTrue();
        });

        it('allows joining with grace period after end time', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->subMinutes(50),
                'duration_minutes' => 45,
                'status' => SessionStatus::ONGOING,
            ]);

            $response = $this->getJson("/api/sessions/academic/{$session->id}/status");

            $response->assertStatus(200);
            // Should still be joinable (within 5 min grace period)
            expect($response->json('can_join'))->toBeTrue();
        });

        it('prevents joining after grace period ends', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'scheduled_at' => now()->subMinutes(70),
                'duration_minutes' => 45,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $response = $this->getJson("/api/sessions/academic/{$session->id}/status");

            $response->assertStatus(200);
            expect($response->json('can_join'))->toBeFalse();
        });
    });
});
