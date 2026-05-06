<?php

use App\Enums\SessionStatus;
use App\Enums\TrialRequestStatus;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\QuranTrialRequest;
use App\Services\SessionManagementService;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Sanctum\Sanctum;

/**
 * Authorization regression suite for trial-session meeting-token access.
 * Validates that students reach a token through both a direct student_id
 * match and the trial_request_id fallback (legacy NULL rows), and that
 * the LiveKit room is provisioned at trial creation.
 */
beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    // LiveKit's createMeeting + token generation work offline as long as
    // the API key/secret/server_url config keys are populated — no HTTP.
    config()->set('livekit.api_key', 'test-key');
    config()->set('livekit.api_secret', 'test-secret-with-enough-bytes-for-hmac-signing');
    config()->set('livekit.server_url', 'wss://test.livekit.local');
    config()->set('livekit.api_url', 'https://test.livekit.local');

    $this->academy = createAcademy(['subdomain' => 'meeting-token-test']);
    $this->subdomainHeader = ['X-Academy-Subdomain' => 'meeting-token-test'];

    $this->teacher = createQuranTeacher($this->academy);
    $this->student = createStudent($this->academy);
});

/**
 * Build an approved trial request linking teacher → student.
 */
function buildTrialRequest($academy, $teacher, $student): QuranTrialRequest
{
    return QuranTrialRequest::factory()->approved()->create([
        'academy_id' => $academy->id,
        'teacher_id' => QuranTeacherProfile::where('user_id', $teacher->id)->value('id'),
        'student_id' => $student->id,
        'status' => TrialRequestStatus::SCHEDULED,
    ]);
}

/**
 * Create a trial QuranSession with optional student_id population, mimicking
 * the two real creation paths we care about (student_id NULL = legacy
 * SessionManagementService path; student_id populated = fixed path / Filament
 * / Teacher API). withMeeting() pre-populates meeting_room_name so the
 * controller skips the auto-provision branch and goes straight to token gen.
 */
function buildTrialSession($academy, $teacher, $trialRequest, bool $populateStudentId, $student): QuranSession
{
    return QuranSession::factory()
        ->trial()
        ->withMeeting()
        ->create([
            'academy_id' => $academy->id,
            'quran_teacher_id' => $teacher->id,
            'student_id' => $populateStudentId ? $student->id : null,
            'trial_request_id' => $trialRequest->id,
            'status' => SessionStatus::SCHEDULED,
            'scheduled_at' => now()->addMinutes(2),
            'duration_minutes' => 30,
        ]);
}

describe('GET /api/v1/meetings/quran/{id}/token — trial sessions', function () {

    it('grants a token to a trial student even when student_id is NULL (legacy createTrialSession rows)', function () {
        $trialRequest = buildTrialRequest($this->academy, $this->teacher, $this->student);
        $session = buildTrialSession(
            $this->academy,
            $this->teacher,
            $trialRequest,
            populateStudentId: false,
            student: $this->student,
        );

        Sanctum::actingAs($this->student, ['*']);

        $this->getJson("/api/v1/meetings/quran/{$session->id}/token", $this->subdomainHeader)
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.participant.role', 'student')
            ->assertJsonPath('data.participant.user_id', $this->student->id)
            ->assertJsonStructure(['data' => ['token', 'room_name', 'livekit_url']]);
    });

    it('grants a token to a trial student when student_id is populated (Filament + Teacher API + fixed createTrialSession path)', function () {
        $trialRequest = buildTrialRequest($this->academy, $this->teacher, $this->student);
        $session = buildTrialSession(
            $this->academy,
            $this->teacher,
            $trialRequest,
            populateStudentId: true,
            student: $this->student,
        );

        Sanctum::actingAs($this->student, ['*']);

        $this->getJson("/api/v1/meetings/quran/{$session->id}/token", $this->subdomainHeader)
            ->assertStatus(200)
            ->assertJsonPath('data.participant.role', 'student');
    });

    it('grants a token to the trial teacher (baseline)', function () {
        $trialRequest = buildTrialRequest($this->academy, $this->teacher, $this->student);
        $session = buildTrialSession(
            $this->academy,
            $this->teacher,
            $trialRequest,
            populateStudentId: false,
            student: $this->student,
        );

        Sanctum::actingAs($this->teacher, ['*']);

        $this->getJson("/api/v1/meetings/quran/{$session->id}/token", $this->subdomainHeader)
            ->assertStatus(200)
            ->assertJsonPath('data.participant.role', 'teacher');
    });

    it('returns 404 for an unrelated student (the new orWhereHas does not over-grant)', function () {
        $trialRequest = buildTrialRequest($this->academy, $this->teacher, $this->student);
        $session = buildTrialSession(
            $this->academy,
            $this->teacher,
            $trialRequest,
            populateStudentId: false,
            student: $this->student,
        );

        $intruder = createStudent($this->academy);
        Sanctum::actingAs($intruder, ['*']);

        $this->getJson("/api/v1/meetings/quran/{$session->id}/token", $this->subdomainHeader)
            ->assertStatus(404);
    });
});

describe('SessionManagementService::createTrialSession', function () {

    it('populates student_id and provisions a meeting room (Changes 2a + 2b)', function () {
        $trialRequest = QuranTrialRequest::factory()->approved()->create([
            'academy_id' => $this->academy->id,
            'teacher_id' => QuranTeacherProfile::where('user_id', $this->teacher->id)->value('id'),
            'student_id' => $this->student->id,
        ]);

        // Schedule far enough in the future to clear "in the past" + slot conflict checks.
        $tomorrow = now()->addDay()->toDateString();

        app(SessionManagementService::class)->createTrialSession($trialRequest, [
            'schedule_start_date' => $tomorrow,
            'schedule_time' => '10:00',
        ]);

        $session = QuranSession::where('trial_request_id', $trialRequest->id)->firstOrFail();

        expect($session->student_id)->toBe($this->student->id)
            ->and($session->session_type)->toBe('trial')
            ->and($session->meeting_room_name)->not->toBeNull();
    });
});
