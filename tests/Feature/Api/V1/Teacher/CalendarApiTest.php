<?php

use App\Models\QuranSession;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Sanctum\Sanctum;

/**
 * Teacher calendar API — covers route wiring, middleware gates, and the
 * ResolveCalendarContext effective-teacher resolution. Deep scheduling-rule
 * tests live with the strategy/validator unit tests; here we focus on the
 * HTTP surface and authorization.
 */
beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    $this->academy = createAcademy(['subdomain' => 'cal-test']);
    $this->teacher = createQuranTeacher($this->academy);
    $this->otherTeacher = createQuranTeacher($this->academy);
    $this->student = createStudent($this->academy);

    $this->subdomainHeader = ['X-Academy-Subdomain' => 'cal-test'];
});

describe('GET /api/v1/teacher/calendar/events', function () {

    it('requires authentication', function () {
        $this->getJson('/api/v1/teacher/calendar/events?start=2026-05-01&end=2026-05-31', $this->subdomainHeader)
            ->assertStatus(401);
    });

    it('rejects non-teacher users with 403', function () {
        Sanctum::actingAs($this->student, ['*']);

        $this->getJson('/api/v1/teacher/calendar/events?start=2026-05-01&end=2026-05-31', $this->subdomainHeader)
            ->assertStatus(403);
    });

    it('returns events for the authenticated teacher', function () {
        Sanctum::actingAs($this->teacher, ['teacher:*']);

        QuranSession::factory()->individual()->create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'scheduled_at' => now()->addDays(3),
            'duration_minutes' => 30,
        ]);

        $response = $this->getJson(
            '/api/v1/teacher/calendar/events?start='.now()->toDateString().'&end='.now()->addDays(14)->toDateString(),
            $this->subdomainHeader,
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'events',
                    'period' => ['start', 'end', 'timezone'],
                    'total',
                ],
            ]);
    });

    it('requires start and end query params', function () {
        Sanctum::actingAs($this->teacher, ['teacher:*']);

        $this->getJson('/api/v1/teacher/calendar/events', $this->subdomainHeader)
            ->assertStatus(422);
    });

    it('ignores teacher_id when caller is a teacher (cannot act on behalf of another)', function () {
        Sanctum::actingAs($this->teacher, ['teacher:*']);

        // Session belongs to otherTeacher, NOT the caller
        QuranSession::factory()->individual()->create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $this->otherTeacher->id,
            'student_id' => $this->student->id,
            'scheduled_at' => now()->addDays(3),
        ]);

        $response = $this->getJson(
            '/api/v1/teacher/calendar/events'
                .'?start='.now()->toDateString()
                .'&end='.now()->addDays(14)->toDateString()
                .'&teacher_id='.$this->otherTeacher->id,
            $this->subdomainHeader,
        );

        // Request should succeed but return zero events (scoped to caller)
        $response->assertStatus(200)
            ->assertJsonPath('data.total', 0);
    });
});

describe('GET /api/v1/teacher/calendar/schedulable-items', function () {

    it('returns tab configuration for quran teachers when tab is omitted', function () {
        Sanctum::actingAs($this->teacher, ['teacher:*']);

        $response = $this->getJson('/api/v1/teacher/calendar/schedulable-items', $this->subdomainHeader);

        $response->assertStatus(200)
            ->assertJsonPath('data.teacher_type', 'quran_teacher')
            ->assertJsonStructure([
                'data' => [
                    'teacher_type',
                    'tabs' => [
                        ['key', 'label'],
                    ],
                ],
            ]);

        $tabKeys = collect($response->json('data.tabs'))->pluck('key')->all();
        expect($tabKeys)->toContain('group', 'individual', 'trials');
    });

    it('returns 422 for unknown tab', function () {
        Sanctum::actingAs($this->teacher, ['teacher:*']);

        $this->getJson('/api/v1/teacher/calendar/schedulable-items?tab=unknown', $this->subdomainHeader)
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVALID_TAB');
    });
});

describe('POST /api/v1/teacher/calendar/check-conflicts', function () {

    it('rejects non-quarter-hour time values', function () {
        Sanctum::actingAs($this->teacher, ['teacher:*']);

        $this->postJson('/api/v1/teacher/calendar/check-conflicts', [
            'date' => now()->addDay()->toDateString(),
            'time' => '10:07', // not a quarter-hour
            'duration_minutes' => 30,
        ], $this->subdomainHeader)
            ->assertStatus(422);
    });

    it('returns a conflicts envelope for a valid slot', function () {
        Sanctum::actingAs($this->teacher, ['teacher:*']);

        $this->postJson('/api/v1/teacher/calendar/check-conflicts', [
            'date' => now()->addDay()->toDateString(),
            'time' => '10:00',
            'duration_minutes' => 30,
        ], $this->subdomainHeader)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['has_conflicts', 'conflicts'],
            ]);
    });
});

describe('POST /api/v1/teacher/calendar/schedule', function () {

    it('rejects payload with past schedule_start_date', function () {
        Sanctum::actingAs($this->teacher, ['teacher:*']);

        $this->postJson('/api/v1/teacher/calendar/schedule', [
            'item_id' => 1,
            'item_type' => 'group',
            'schedule_days' => ['Monday'],
            'schedule_time' => '10:00',
            'schedule_start_date' => now()->subDays(5)->toDateString(),
            'session_count' => 1,
        ], $this->subdomainHeader)
            ->assertStatus(422);
    });

    it('rejects trial item_type with non-trial schedule_days missing', function () {
        // Trial allows empty schedule_days, but other types require it
        Sanctum::actingAs($this->teacher, ['teacher:*']);

        $this->postJson('/api/v1/teacher/calendar/schedule', [
            'item_id' => 1,
            'item_type' => 'group',
            // schedule_days omitted
            'schedule_time' => '10:00',
            'schedule_start_date' => now()->addDay()->toDateString(),
            'session_count' => 1,
        ], $this->subdomainHeader)
            ->assertStatus(422);
    });
});

describe('PUT /api/v1/teacher/calendar/sessions/{type}/{id}/reschedule', function () {

    it('rejects reschedule of a non-owned session with 404', function () {
        Sanctum::actingAs($this->teacher, ['teacher:*']);

        $session = QuranSession::factory()->individual()->create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $this->otherTeacher->id,
            'student_id' => $this->student->id,
            'scheduled_at' => now()->addDays(3),
        ]);

        $this->putJson("/api/v1/teacher/calendar/sessions/quran/{$session->id}/reschedule", [
            'scheduled_at' => now()->addDays(5)->toIso8601String(),
        ], $this->subdomainHeader)
            ->assertStatus(404);
    });

    it('rejects reschedule to a past time', function () {
        Sanctum::actingAs($this->teacher, ['teacher:*']);

        $session = QuranSession::factory()->individual()->create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'scheduled_at' => now()->addDays(3),
        ]);

        $this->putJson("/api/v1/teacher/calendar/sessions/quran/{$session->id}/reschedule", [
            'scheduled_at' => now()->subDay()->toIso8601String(),
        ], $this->subdomainHeader)
            ->assertStatus(422);
    });
});
