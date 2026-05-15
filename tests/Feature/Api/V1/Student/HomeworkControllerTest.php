<?php

use App\Enums\HomeworkSubmissionStatus;
use App\Models\AcademicSession;
use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\QuranSessionHomework;
use App\Models\StudentSessionReport;
use App\Models\User;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Sanctum\Sanctum;

/**
 * Student homework API — Phase 1 contract checks.
 *
 * Exercises the rewritten index + show responses for the three homework
 * types. The point of these tests is the response shape — Quran items carry
 * an evaluation block when a StudentSessionReport exists, academic +
 * interactive responses include attachments_config, and submission_status
 * uses the raw HomeworkSubmissionStatus enum (not the old 3-state collapse).
 */
beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    $this->academy = Academy::factory()->create([
        'subdomain' => 'student-homework-test',
        'is_active' => true,
    ]);

    $this->student = User::factory()->create([
        'academy_id' => $this->academy->id,
        'user_type' => 'student',
    ]);

    Sanctum::actingAs($this->student, ['student:*']);
});

describe('GET /api/v1/student/homework (index)', function () {
    it('returns an empty envelope for a student with no homework', function () {
        $response = $this->getJson('/api/v1/student/homework', [
            'X-Academy-Subdomain' => 'student-homework-test',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.homework', [])
            ->assertJsonPath('data.pagination.total', 0)
            ->assertJsonPath('data.stats.total', 0)
            ->assertJsonPath('data.stats.upcoming', 0)
            ->assertJsonPath('data.stats.past', 0);
    });

    it('surfaces academic homework with the real submission_status enum value', function () {
        AcademicSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'homework_description' => 'حل التمارين من ١ إلى ٥',
            'homework_assigned' => true,
            'scheduled_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/v1/student/homework', [
            'X-Academy-Subdomain' => 'student-homework-test',
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data.homework')
            ->assertJsonPath('data.homework.0.type', 'academic')
            ->assertJsonPath('data.homework.0.submission_status', HomeworkSubmissionStatus::PENDING->value)
            ->assertJsonPath('data.homework.0.can_submit', true)
            ->assertJsonPath('data.homework.0.is_evaluated', false);
    });

    it('returns quran homework with submission_status=pending when no evaluation exists', function () {
        $session = QuranSession::factory()->individual()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'scheduled_at' => now()->subDay(),
        ]);
        QuranSessionHomework::factory()->newMemorizationOnly()->create([
            'session_id' => $session->id,
            'created_by' => $this->student->id,
        ]);

        $response = $this->getJson('/api/v1/student/homework', [
            'X-Academy-Subdomain' => 'student-homework-test',
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data.homework')
            ->assertJsonPath('data.homework.0.type', 'quran')
            ->assertJsonPath('data.homework.0.submission_status', HomeworkSubmissionStatus::PENDING->value)
            ->assertJsonPath('data.homework.0.can_submit', false)
            ->assertJsonPath('data.homework.0.is_evaluated', false);
    });

    it('marks quran homework as graded + evaluated once the session report carries an evaluated_at', function () {
        $session = QuranSession::factory()->individual()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'scheduled_at' => now()->subDays(2),
        ]);
        QuranSessionHomework::factory()->newMemorizationOnly()->create([
            'session_id' => $session->id,
            'created_by' => $this->student->id,
        ]);
        StudentSessionReport::factory()->excellentPerformance()->create([
            'session_id' => $session->id,
            'student_id' => $this->student->id,
            'teacher_id' => $session->quran_teacher_id,
            'academy_id' => $this->academy->id,
            'new_memorization_degree' => 9.5,
            'reservation_degree' => 9.0,
            'notes' => 'ماشاء الله أداء متميز',
            'evaluated_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/v1/student/homework', [
            'X-Academy-Subdomain' => 'student-homework-test',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.homework.0.type', 'quran')
            ->assertJsonPath('data.homework.0.submission_status', HomeworkSubmissionStatus::GRADED->value)
            ->assertJsonPath('data.homework.0.is_evaluated', true)
            ->assertJsonPath('data.homework.0.grade_summary.label', 'excellent');
    });

    it('computes stats over the unfiltered base set, not the filtered slice', function () {
        AcademicSession::factory()->count(2)->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'homework_description' => 'مهمة',
            'scheduled_at' => now()->subDays(3),
        ]);
        $qs = QuranSession::factory()->individual()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'scheduled_at' => now()->subDays(4),
        ]);
        QuranSessionHomework::factory()->newMemorizationOnly()->create([
            'session_id' => $qs->id,
            'created_by' => $this->student->id,
        ]);
        StudentSessionReport::factory()->create([
            'session_id' => $qs->id,
            'student_id' => $this->student->id,
            'teacher_id' => $qs->quran_teacher_id,
            'academy_id' => $this->academy->id,
            'evaluated_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/v1/student/homework?status=upcoming', [
            'X-Academy-Subdomain' => 'student-homework-test',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.stats.total', 3)
            ->assertJsonPath('data.stats.upcoming', 2)
            ->assertJsonPath('data.stats.past', 1);
    });
});

describe('GET /api/v1/student/homework/quran/{id} (show)', function () {
    it('exposes the oral evaluation block when the session has been evaluated', function () {
        $session = QuranSession::factory()->individual()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'scheduled_at' => now()->subDays(2),
        ]);
        $hw = QuranSessionHomework::factory()->comprehensive()->create([
            'session_id' => $session->id,
            'created_by' => $this->student->id,
        ]);
        StudentSessionReport::factory()->create([
            'session_id' => $session->id,
            'student_id' => $this->student->id,
            'teacher_id' => $session->quran_teacher_id,
            'academy_id' => $this->academy->id,
            'new_memorization_degree' => 8.5,
            'reservation_degree' => 8.5,
            'notes' => 'ممتاز',
            'evaluated_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/v1/student/homework/quran/'.$hw->id, [
            'X-Academy-Subdomain' => 'student-homework-test',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.homework.type', 'quran')
            ->assertJsonPath('data.homework.evaluation.tier', 'very_good')
            ->assertJsonPath('data.homework.evaluation.percentage', 85)
            ->assertJsonPath('data.homework.evaluation.teacher_notes', 'ممتاز')
            ->assertJsonStructure([
                'data' => [
                    'homework' => [
                        'id',
                        'type',
                        'new_memorization',
                        'review',
                        'comprehensive_review',
                        'evaluation' => ['tier', 'percentage', 'teacher_notes', 'evaluated_at'],
                    ],
                ],
            ]);
    });

    it('returns evaluation=null when the report has not been evaluated yet', function () {
        $session = QuranSession::factory()->individual()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'scheduled_at' => now()->subDay(),
        ]);
        $hw = QuranSessionHomework::factory()->newMemorizationOnly()->create([
            'session_id' => $session->id,
            'created_by' => $this->student->id,
        ]);

        $response = $this->getJson('/api/v1/student/homework/quran/'.$hw->id, [
            'X-Academy-Subdomain' => 'student-homework-test',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.homework.evaluation', null)
            ->assertJsonPath('data.homework.submission_status', HomeworkSubmissionStatus::PENDING->value);
    });
});

describe('GET /api/v1/student/homework/academic/{id} (show)', function () {
    it('exposes attachments_config + max_grade on academic detail', function () {
        $session = AcademicSession::factory()->create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'homework_description' => 'حل التمارين',
            'homework_assigned' => true,
            'scheduled_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/v1/student/homework/academic/'.$session->id, [
            'X-Academy-Subdomain' => 'student-homework-test',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.homework.type', 'academic')
            ->assertJsonPath('data.homework.submission_status', HomeworkSubmissionStatus::PENDING->value)
            ->assertJsonPath('data.homework.can_submit', true)
            ->assertJsonStructure([
                'data' => [
                    'homework' => [
                        'attachments_config' => [
                            'max_files',
                            'max_file_size_mb',
                            'allowed_extensions',
                            'submission_types',
                        ],
                    ],
                ],
            ]);

        $cfg = $response->json('data.homework.attachments_config');
        expect($cfg['max_files'])->toBe(5);
        expect($cfg['max_file_size_mb'])->toBe(10);
        expect($cfg['allowed_extensions'])->toContain('pdf');
    });
});
