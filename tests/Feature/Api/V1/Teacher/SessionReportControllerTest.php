<?php

use App\Enums\AttendanceStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\StudentSessionReport;
use App\Models\User;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Sanctum\Sanctum;

/**
 * Per-student session report editor — mirrors the web's "Edit Report" modal.
 *
 * Routes covered:
 * - GET    /api/v1/teacher/{type}/sessions/{id}/reports
 * - PUT    /api/v1/teacher/{type}/sessions/{id}/reports/{studentId}
 * - PUT    /api/v1/teacher/{type}/sessions/{id}/lesson-content
 *
 * Source-of-truth check: the columns we read/write on the report must be
 * the actual columns the web app exposes. No invented fields.
 */
beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);

    $this->teacher = User::factory()->create([
        'academy_id' => $this->academy->id,
        'user_type' => 'quran_teacher',
        'active_status' => true,
    ]);
    QuranTeacherProfile::factory()->create([
        'user_id' => $this->teacher->id,
        'academy_id' => $this->academy->id,
    ]);

    $this->student = User::factory()->create([
        'academy_id' => $this->academy->id,
        'user_type' => 'student',
        'active_status' => true,
    ]);

    $this->session = QuranSession::factory()->individual()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
    ]);

    Sanctum::actingAs($this->teacher, ['teacher:*']);
});

describe('GET /api/v1/teacher/{type}/sessions/{id}/reports', function () {

    it('returns one entry per enrolled student for an individual quran session', function () {
        $response = $this->getJson(
            "/api/v1/teacher/quran/sessions/{$this->session->id}/reports",
            ['X-Academy-Subdomain' => 'test-academy'],
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'reports' => [
                        '*' => [
                            'student' => ['id', 'name'],
                            'attendance_status',
                            'memorization_degree',
                            'revision_degree',
                            'notes',
                        ],
                    ],
                    'lesson_content',
                ],
            ]);

        expect($response->json('data.reports'))->toHaveCount(1);
        expect($response->json('data.reports.0.student.id'))->toBe($this->student->id);
    });

    it('rejects access from a different teacher', function () {
        $otherTeacher = User::factory()->create([
            'academy_id' => $this->academy->id,
            'user_type' => 'quran_teacher',
        ]);
        QuranTeacherProfile::factory()->create([
            'user_id' => $otherTeacher->id,
            'academy_id' => $this->academy->id,
        ]);
        Sanctum::actingAs($otherTeacher, ['teacher:*']);

        $response = $this->getJson(
            "/api/v1/teacher/quran/sessions/{$this->session->id}/reports",
            ['X-Academy-Subdomain' => 'test-academy'],
        );

        $response->assertStatus(404);
    });

    it('rejects an academic teacher hitting the quran type prefix', function () {
        $academicTeacher = User::factory()->create([
            'academy_id' => $this->academy->id,
            'user_type' => 'academic_teacher',
        ]);
        AcademicTeacherProfile::factory()->create([
            'user_id' => $academicTeacher->id,
            'academy_id' => $this->academy->id,
        ]);
        Sanctum::actingAs($academicTeacher, ['teacher:*']);

        $response = $this->getJson(
            "/api/v1/teacher/quran/sessions/{$this->session->id}/reports",
            ['X-Academy-Subdomain' => 'test-academy'],
        );

        $response->assertStatus(404);
    });
});

describe('PUT /api/v1/teacher/{type}/sessions/{id}/reports/{studentId}', function () {

    it('upserts the per-student report with degree fields', function () {
        $response = $this->putJson(
            "/api/v1/teacher/quran/sessions/{$this->session->id}/reports/{$this->student->id}",
            [
                'attendance_status' => 'attended',
                'memorization_degree' => 8.5,
                'revision_degree' => 7.0,
                'notes' => 'Great progress on Al-Baqarah',
            ],
            ['X-Academy-Subdomain' => 'test-academy'],
        );

        $response->assertStatus(200);

        $report = StudentSessionReport::where('session_id', $this->session->id)
            ->where('student_id', $this->student->id)
            ->first();

        expect($report)->not->toBeNull();
        expect((float) $report->new_memorization_degree)->toBe(8.5);
        expect((float) $report->reservation_degree)->toBe(7.0);
        expect($report->notes)->toBe('Great progress on Al-Baqarah');
        expect($report->attendance_status)->toBe(AttendanceStatus::ATTENDED);
        expect((bool) $report->manually_evaluated)->toBeTrue();
    });

    it('rejects writable attendance values outside the {attended, partially_attended, absent} set', function () {
        $response = $this->putJson(
            "/api/v1/teacher/quran/sessions/{$this->session->id}/reports/{$this->student->id}",
            ['attendance_status' => 'late'],
            ['X-Academy-Subdomain' => 'test-academy'],
        );

        $response->assertStatus(422);
    });

    it('rejects degree values outside 0..10', function () {
        $response = $this->putJson(
            "/api/v1/teacher/quran/sessions/{$this->session->id}/reports/{$this->student->id}",
            ['memorization_degree' => 11.5],
            ['X-Academy-Subdomain' => 'test-academy'],
        );

        $response->assertStatus(422);
    });

    it('returns 404 when the student is not part of the session', function () {
        $strangerStudent = User::factory()->create([
            'academy_id' => $this->academy->id,
            'user_type' => 'student',
        ]);

        $response = $this->putJson(
            "/api/v1/teacher/quran/sessions/{$this->session->id}/reports/{$strangerStudent->id}",
            ['attendance_status' => 'attended'],
            ['X-Academy-Subdomain' => 'test-academy'],
        );

        $response->assertStatus(404);
    });

    it('writes homework_degree (not memorization fields) for academic sessions', function () {
        $academicTeacher = User::factory()->create([
            'academy_id' => $this->academy->id,
            'user_type' => 'academic_teacher',
        ]);
        $academicProfile = AcademicTeacherProfile::factory()->create([
            'user_id' => $academicTeacher->id,
            'academy_id' => $this->academy->id,
        ]);

        $academicSession = AcademicSession::factory()->create([
            'academy_id' => $this->academy->id,
            'academic_teacher_id' => $academicProfile->id,
            'student_id' => $this->student->id,
        ]);

        Sanctum::actingAs($academicTeacher, ['teacher:*']);

        $response = $this->putJson(
            "/api/v1/teacher/academic/sessions/{$academicSession->id}/reports/{$this->student->id}",
            [
                'attendance_status' => 'partially_attended',
                'homework_degree' => 6.5,
                'notes' => 'Submitted on time',
            ],
            ['X-Academy-Subdomain' => 'test-academy'],
        );

        $response->assertStatus(200);

        $report = AcademicSessionReport::where('session_id', $academicSession->id)
            ->where('student_id', $this->student->id)
            ->first();

        expect($report)->not->toBeNull();
        expect((float) $report->homework_degree)->toBe(6.5);
        expect($report->attendance_status)->toBe(AttendanceStatus::PARTIALLY_ATTENDED);
    });
});

describe('PUT /api/v1/teacher/{type}/sessions/{id}/lesson-content', function () {

    it('updates the session lesson_content column', function () {
        $response = $this->putJson(
            "/api/v1/teacher/quran/sessions/{$this->session->id}/lesson-content",
            ['lesson_content' => 'Reviewed Surah Al-Fatiha and started Al-Baqarah verses 1-5'],
            ['X-Academy-Subdomain' => 'test-academy'],
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.session.lesson_content',
                'Reviewed Surah Al-Fatiha and started Al-Baqarah verses 1-5');

        expect($this->session->fresh()->lesson_content)
            ->toBe('Reviewed Surah Al-Fatiha and started Al-Baqarah verses 1-5');
    });

    it('rejects empty lesson content', function () {
        $response = $this->putJson(
            "/api/v1/teacher/quran/sessions/{$this->session->id}/lesson-content",
            ['lesson_content' => ''],
            ['X-Academy-Subdomain' => 'test-academy'],
        );

        $response->assertStatus(422);
    });

    it('caps lesson content at 5000 chars', function () {
        $response = $this->putJson(
            "/api/v1/teacher/quran/sessions/{$this->session->id}/lesson-content",
            ['lesson_content' => str_repeat('x', 5001)],
            ['X-Academy-Subdomain' => 'test-academy'],
        );

        $response->assertStatus(422);
    });

    it('rejects access from a teacher who does not own the session', function () {
        $otherTeacher = User::factory()->create([
            'academy_id' => $this->academy->id,
            'user_type' => 'quran_teacher',
        ]);
        QuranTeacherProfile::factory()->create([
            'user_id' => $otherTeacher->id,
            'academy_id' => $this->academy->id,
        ]);
        Sanctum::actingAs($otherTeacher, ['teacher:*']);

        $response = $this->putJson(
            "/api/v1/teacher/quran/sessions/{$this->session->id}/lesson-content",
            ['lesson_content' => 'attempted overwrite'],
            ['X-Academy-Subdomain' => 'test-academy'],
        );

        $response->assertStatus(404);
    });
});
