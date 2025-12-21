<?php

namespace Tests\Unit\Models;

use App\Models\Academy;
use App\Models\User;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Enums\SessionStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Unit tests for QuranSession model
 *
 * Tests cover:
 * - Session creation
 * - Status transitions
 * - Relationships
 * - Scopes
 * - Session lifecycle
 */
class QuranSessionTest extends TestCase
{
    use DatabaseTransactions;

    protected string $testId;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake(); // Prevent observers from triggering during tests
        $this->createAcademy();
        $this->testId = uniqid();
    }

    /**
     * Create a teacher with profile.
     */
    protected function makeTeacher(): array
    {
        $user = User::factory()->quranTeacher()->create([
            'academy_id' => $this->academy->id,
        ]);
        $profile = QuranTeacherProfile::create([
            'academy_id' => $this->academy->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => '050' . rand(1000000, 9999999),
            'teacher_code' => 'QT-' . $this->testId . '-' . uniqid(),
            'is_active' => true,
        ]);
        return ['user' => $user, 'profile' => $profile];
    }

    /**
     * Create a student.
     */
    protected function makeStudent(): User
    {
        return User::factory()->student()->create([
            'academy_id' => $this->academy->id,
        ]);
    }

    /**
     * Generate a unique session code.
     */
    protected function generateSessionCode(): string
    {
        return 'QSE-' . $this->testId . '-' . uniqid();
    }

    /**
     * Test quran session can be created.
     */
    public function test_quran_session_can_be_created(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent();

        $session = QuranSession::create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $teacher['user']->id,
            'student_id' => $student->id,
            'session_type' => 'individual',
            'scheduled_at' => Carbon::now()->addDay(),
            'duration_minutes' => 60,
            'status' => SessionStatus::SCHEDULED,
            'session_code' => $this->generateSessionCode(),
        ]);

        $this->assertDatabaseHas('quran_sessions', [
            'id' => $session->id,
            'session_type' => 'individual',
        ]);
    }

    /**
     * Test session has correct status cast.
     */
    public function test_session_status_is_cast_to_enum(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent();

        $session = QuranSession::create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $teacher['user']->id,
            'student_id' => $student->id,
            'session_type' => 'individual',
            'scheduled_at' => Carbon::now()->addDay(),
            'duration_minutes' => 60,
            'status' => SessionStatus::SCHEDULED,
            'session_code' => $this->generateSessionCode(),
        ]);

        $this->assertInstanceOf(SessionStatus::class, $session->status);
        $this->assertEquals(SessionStatus::SCHEDULED, $session->status);
    }

    /**
     * Test session belongs to academy.
     */
    public function test_session_belongs_to_academy(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent();

        $session = QuranSession::create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $teacher['user']->id,
            'student_id' => $student->id,
            'session_type' => 'individual',
            'scheduled_at' => Carbon::now()->addDay(),
            'duration_minutes' => 60,
            'status' => SessionStatus::SCHEDULED,
            'session_code' => $this->generateSessionCode(),
        ]);

        $this->assertInstanceOf(Academy::class, $session->academy);
        $this->assertEquals($this->academy->id, $session->academy->id);
    }

    /**
     * Test session belongs to teacher.
     */
    public function test_session_belongs_to_teacher(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent();

        $session = QuranSession::create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $teacher['user']->id,
            'student_id' => $student->id,
            'session_type' => 'individual',
            'scheduled_at' => Carbon::now()->addDay(),
            'duration_minutes' => 60,
            'status' => SessionStatus::SCHEDULED,
            'session_code' => $this->generateSessionCode(),
        ]);

        $this->assertEquals($teacher['user']->id, $session->quran_teacher_id);
    }

    /**
     * Test session belongs to student.
     */
    public function test_session_belongs_to_student(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent();

        $session = QuranSession::create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $teacher['user']->id,
            'student_id' => $student->id,
            'session_type' => 'individual',
            'scheduled_at' => Carbon::now()->addDay(),
            'duration_minutes' => 60,
            'status' => SessionStatus::SCHEDULED,
            'session_code' => $this->generateSessionCode(),
        ]);

        $this->assertEquals($student->id, $session->student_id);
    }

    /**
     * Test session scheduled_at is cast to datetime.
     */
    public function test_session_scheduled_at_is_datetime(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent();
        $scheduledTime = Carbon::now()->addDay();

        $session = QuranSession::create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $teacher['user']->id,
            'student_id' => $student->id,
            'session_type' => 'individual',
            'scheduled_at' => $scheduledTime,
            'duration_minutes' => 60,
            'status' => SessionStatus::SCHEDULED,
            'session_code' => $this->generateSessionCode(),
        ]);

        $this->assertInstanceOf(Carbon::class, $session->scheduled_at);
    }

    /**
     * Test session scope scheduled.
     */
    public function test_session_scope_scheduled(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent();

        QuranSession::create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $teacher['user']->id,
            'student_id' => $student->id,
            'session_type' => 'individual',
            'scheduled_at' => Carbon::now()->addDay(),
            'duration_minutes' => 60,
            'status' => SessionStatus::SCHEDULED,
            'session_code' => $this->generateSessionCode(),
        ]);

        QuranSession::create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $teacher['user']->id,
            'student_id' => $student->id,
            'session_type' => 'individual',
            'scheduled_at' => Carbon::now()->subDay(),
            'duration_minutes' => 60,
            'status' => SessionStatus::COMPLETED,
            'session_code' => $this->generateSessionCode(),
        ]);

        $scheduledSessions = QuranSession::where('status', SessionStatus::SCHEDULED)->get();

        $this->assertEquals(1, $scheduledSessions->count());
    }

    /**
     * Test session can be cancelled.
     */
    public function test_session_can_be_cancelled(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent();

        $session = QuranSession::create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $teacher['user']->id,
            'student_id' => $student->id,
            'session_type' => 'individual',
            'scheduled_at' => Carbon::now()->addDay(),
            'duration_minutes' => 60,
            'status' => SessionStatus::SCHEDULED,
            'session_code' => $this->generateSessionCode(),
        ]);

        $session->update([
            'status' => SessionStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => 'Teacher unavailable',
        ]);

        $session->refresh();

        $this->assertEquals(SessionStatus::CANCELLED, $session->status);
        $this->assertNotNull($session->cancelled_at);
    }

    /**
     * Test session can be completed.
     */
    public function test_session_can_be_completed(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent();

        $session = QuranSession::create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $teacher['user']->id,
            'student_id' => $student->id,
            'session_type' => 'individual',
            'scheduled_at' => Carbon::now()->subHour(),
            'duration_minutes' => 60,
            'status' => SessionStatus::ONGOING,
            'session_code' => $this->generateSessionCode(),
        ]);

        $session->update([
            'status' => SessionStatus::COMPLETED,
            'ended_at' => now(),
        ]);

        $session->refresh();

        $this->assertEquals(SessionStatus::COMPLETED, $session->status);
        $this->assertNotNull($session->ended_at);
    }

    /**
     * Test group session type.
     */
    public function test_group_session_type(): void
    {
        $teacher = $this->makeTeacher();

        $session = QuranSession::create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $teacher['user']->id,
            'student_id' => null,
            'session_type' => 'group',
            'scheduled_at' => Carbon::now()->addDay(),
            'duration_minutes' => 60,
            'status' => SessionStatus::SCHEDULED,
            'session_code' => $this->generateSessionCode(),
        ]);

        $this->assertEquals('group', $session->session_type);
        $this->assertNull($session->student_id);
    }

    /**
     * Test session duration.
     */
    public function test_session_duration(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent();

        $session = QuranSession::create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $teacher['user']->id,
            'student_id' => $student->id,
            'session_type' => 'individual',
            'scheduled_at' => Carbon::now()->addDay(),
            'duration_minutes' => 45,
            'status' => SessionStatus::SCHEDULED,
            'session_code' => $this->generateSessionCode(),
        ]);

        $this->assertEquals(45, $session->duration_minutes);
    }

    /**
     * Test session with meeting data.
     */
    public function test_session_with_meeting_data(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent();

        $session = QuranSession::create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $teacher['user']->id,
            'student_id' => $student->id,
            'session_type' => 'individual',
            'scheduled_at' => Carbon::now()->addMinutes(5),
            'duration_minutes' => 60,
            'status' => SessionStatus::READY,
            'session_code' => $this->generateSessionCode(),
            'meeting_room_name' => 'room-test-123',
            'meeting_link' => 'https://meet.example.com/room-test-123',
        ]);

        $this->assertNotNull($session->meeting_room_name);
        $this->assertNotNull($session->meeting_link);
    }

    /**
     * Test session fillable attributes.
     */
    public function test_session_fillable_attributes(): void
    {
        $session = new QuranSession();
        $fillable = $session->getFillable();

        $this->assertContains('academy_id', $fillable);
        $this->assertContains('quran_teacher_id', $fillable);
        $this->assertContains('student_id', $fillable);
        $this->assertContains('session_type', $fillable);
        $this->assertContains('scheduled_at', $fillable);
        $this->assertContains('status', $fillable);
    }
}
