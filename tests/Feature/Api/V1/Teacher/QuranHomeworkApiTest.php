<?php

use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\QuranSessionHomework;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Sanctum\Sanctum;

/**
 * Quran branch of the teacher homework API.
 *
 * The endpoint accepts the flattened QuranSessionHomework shape (sections +
 * surahs + verses + pages + difficulty). It must NOT touch the
 * `quran_sessions.homework_assigned` JSON column — that's the bug the prod
 * log surfaced as `SQLSTATE[22032] Invalid JSON text`.
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
    ]);

    $this->session = QuranSession::factory()->individual()->create([
        'academy_id' => $this->academy->id,
        'quran_teacher_id' => $this->teacher->id,
        'student_id' => $this->student->id,
    ]);

    Sanctum::actingAs($this->teacher, ['teacher:*']);
});

describe('POST /api/v1/teacher/homework/assign — quran branch', function () {

    it('creates a QuranSessionHomework row with the new memorization section', function () {
        $response = $this->postJson(
            '/api/v1/teacher/homework/assign',
            [
                'session_type' => 'quran',
                'session_id' => $this->session->id,
                'has_new_memorization' => true,
                'new_memorization_surah' => 'سورة البقرة',
                'new_memorization_pages' => 1.5,
                'new_memorization_from_verse' => 1,
                'new_memorization_to_verse' => 20,
            ],
            ['X-Academy-Subdomain' => 'test-academy'],
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.homework.has_new_memorization', true)
            ->assertJsonPath('data.homework.new_memorization_surah', 'سورة البقرة');

        $row = QuranSessionHomework::where('session_id', $this->session->id)->first();
        expect($row)->not->toBeNull();
        expect((bool) $row->has_new_memorization)->toBeTrue();
        expect((float) $row->new_memorization_pages)->toBe(1.5);
        expect((int) $row->new_memorization_from_verse)->toBe(1);
    });

    it('does not write to quran_sessions.homework_assigned (JSON column quirk)', function () {
        // Snapshot: the column must remain untouched. Writing `1` would raise
        // SQLSTATE[22032] because the QuranSession model declines to cast it.
        $before = $this->session->getRawOriginal('homework_assigned');

        $this->postJson(
            '/api/v1/teacher/homework/assign',
            [
                'session_type' => 'quran',
                'session_id' => $this->session->id,
                'has_review' => true,
                'review_surah' => 'سورة الفاتحة',
                'review_pages' => 0.5,
            ],
            ['X-Academy-Subdomain' => 'test-academy'],
        )->assertStatus(200);

        expect($this->session->fresh()->getRawOriginal('homework_assigned'))->toBe($before);
    });

    it('upserts the same row on a second call (no duplicate per session)', function () {
        $this->postJson('/api/v1/teacher/homework/assign', [
            'session_type' => 'quran',
            'session_id' => $this->session->id,
            'has_new_memorization' => true,
            'new_memorization_surah' => 'سورة الفاتحة',
        ], ['X-Academy-Subdomain' => 'test-academy'])->assertStatus(200);

        $this->postJson('/api/v1/teacher/homework/assign', [
            'session_type' => 'quran',
            'session_id' => $this->session->id,
            'has_new_memorization' => true,
            'new_memorization_surah' => 'سورة البقرة',
        ], ['X-Academy-Subdomain' => 'test-academy'])->assertStatus(200);

        expect(QuranSessionHomework::where('session_id', $this->session->id)->count())->toBe(1);
        expect(QuranSessionHomework::where('session_id', $this->session->id)->first()->new_memorization_surah)
            ->toBe('سورة البقرة');
    });

    it('rejects assigning to a session owned by another teacher', function () {
        $otherTeacher = User::factory()->create([
            'academy_id' => $this->academy->id,
            'user_type' => 'quran_teacher',
        ]);
        QuranTeacherProfile::factory()->create([
            'user_id' => $otherTeacher->id,
            'academy_id' => $this->academy->id,
        ]);
        Sanctum::actingAs($otherTeacher, ['teacher:*']);

        $response = $this->postJson('/api/v1/teacher/homework/assign', [
            'session_type' => 'quran',
            'session_id' => $this->session->id,
            'has_new_memorization' => true,
            'new_memorization_surah' => 'سورة البقرة',
        ], ['X-Academy-Subdomain' => 'test-academy']);

        $response->assertStatus(404);
    });
});

describe('GET /api/v1/teacher/homework/quran/{id}', function () {

    it('returns the canonical QuranSessionHomework shape', function () {
        $hw = QuranSessionHomework::factory()->create([
            'session_id' => $this->session->id,
            'created_by' => $this->teacher->id,
            'has_new_memorization' => true,
            'new_memorization_surah' => 'سورة آل عمران',
            'new_memorization_pages' => 2.0,
            'has_review' => false,
            'has_comprehensive_review' => false,
        ]);

        $response = $this->getJson(
            "/api/v1/teacher/homework/quran/{$hw->id}",
            ['X-Academy-Subdomain' => 'test-academy'],
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.homework.has_new_memorization', true)
            ->assertJsonPath('data.homework.new_memorization_surah', 'سورة آل عمران')
            ->assertJsonPath('data.homework.evaluated_orally', true);
    });
});
