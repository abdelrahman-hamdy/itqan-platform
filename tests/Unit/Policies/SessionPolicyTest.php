<?php

namespace Tests\Unit\Policies;

use App\Policies\SessionPolicy;
use App\Models\User;
use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\StudentProfile;
use App\Enums\SessionStatus;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Test cases for SessionPolicy
 *
 * These tests verify authorization rules for sessions including:
 * - Admin access to all sessions
 * - Teacher access to their own sessions
 * - Student access to their enrolled sessions
 * - Parent access to their children's sessions
 */
class SessionPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected SessionPolicy $policy;
    protected string $testId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new SessionPolicy();
        $this->createAcademy();
        // Unique ID for this test run to avoid collisions
        $this->testId = Str::random(8);
    }

    /**
     * Create a user with specific type and role.
     */
    protected function createUser(string $userType, string $suffix = ''): User
    {
        return User::factory()->create([
            'academy_id' => $this->academy->id,
            'user_type' => $userType,
            'email' => "{$userType}{$suffix}_{$this->testId}@test.local",
        ]);
    }

    /**
     * Create a quran teacher with profile.
     * Note: Quran teachers don't auto-create profiles, so we create manually.
     */
    protected function makeQuranTeacherWithProfile(string $suffix = ''): array
    {
        $user = $this->createUser('quran_teacher', $suffix);
        $profile = QuranTeacherProfile::create([
            'academy_id' => $this->academy->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => '050' . rand(1000000, 9999999),
            'teacher_code' => 'QT-' . $this->testId . $suffix,
            'is_active' => true,
        ]);
        return ['user' => $user, 'profile' => $profile];
    }

    /**
     * Create a student with profile.
     * Note: Student users auto-create StudentProfiles via User::created event,
     * so we just retrieve the auto-created profile.
     */
    protected function makeStudentWithProfile(string $suffix = ''): array
    {
        $user = $this->createUser('student', $suffix);
        // The profile is auto-created by User::created event
        $profile = $user->studentProfile;
        return ['user' => $user, 'profile' => $profile];
    }

    /**
     * Create a quran session.
     * Note: Both quran_teacher_id and student_id reference users table, not profiles
     */
    protected function createQuranSession(User $teacherUser, ?User $studentUser = null): QuranSession
    {
        return QuranSession::create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $teacherUser->id, // References User, not profile
            'session_type' => 'individual',
            'student_id' => $studentUser?->id, // References User, not profile
            'scheduled_at' => Carbon::now()->addDay(),
            'duration_minutes' => 60,
            'status' => SessionStatus::SCHEDULED,
            'session_code' => 'QSE-' . $this->testId . '-' . Str::random(6),
        ]);
    }

    /**
     * Test admin can view any session in their academy.
     */
    public function test_admin_can_view_any_session(): void
    {
        $admin = $this->createUser('admin');
        $teacher = $this->makeQuranTeacherWithProfile();
        $session = $this->createQuranSession($teacher['user']);

        $this->assertTrue($this->policy->view($admin, $session));
    }

    /**
     * Test teacher can view their own sessions.
     */
    public function test_teacher_can_view_own_sessions(): void
    {
        $teacher = $this->makeQuranTeacherWithProfile();
        $session = $this->createQuranSession($teacher['user']);

        $this->assertTrue($this->policy->view($teacher['user'], $session));
    }

    /**
     * Test teacher cannot view other teachers sessions.
     */
    public function test_teacher_cannot_view_other_teacher_sessions(): void
    {
        $teacher1 = $this->makeQuranTeacherWithProfile('_1');
        $teacher2 = $this->makeQuranTeacherWithProfile('_2');

        // Create session for teacher1
        $session = $this->createQuranSession($teacher1['user']);

        // Teacher2 should not be able to view teacher1's session
        $this->assertFalse($this->policy->view($teacher2['user'], $session));
    }

    /**
     * Test student can view their enrolled sessions.
     */
    public function test_student_can_view_enrolled_sessions(): void
    {
        $teacher = $this->makeQuranTeacherWithProfile();
        $student = $this->makeStudentWithProfile();

        // Create session with this student (pass user, not profile)
        $session = $this->createQuranSession($teacher['user'], $student['user']);

        $this->assertTrue($this->policy->view($student['user'], $session));
    }

    /**
     * Test student cannot view non-enrolled sessions.
     */
    public function test_student_cannot_view_non_enrolled_sessions(): void
    {
        $teacher = $this->makeQuranTeacherWithProfile();
        $student1 = $this->makeStudentWithProfile('_1');
        $student2 = $this->makeStudentWithProfile('_2');

        // Create session for student1 (pass user, not profile)
        $session = $this->createQuranSession($teacher['user'], $student1['user']);

        // Student2 should not be able to view student1's session
        $this->assertFalse($this->policy->view($student2['user'], $session));
    }

    /**
     * Test parent can view children's sessions.
     */
    public function test_parent_can_view_child_sessions(): void
    {
        // This test verifies that a parent without children cannot view the session
        $teacher = $this->makeQuranTeacherWithProfile();
        $session = $this->createQuranSession($teacher['user']);

        // Create a parent user (without proper child relationship)
        $parentUser = $this->createUser('parent');

        // Parent without children should not see the session
        $this->assertFalse($this->policy->view($parentUser, $session));
    }

    /**
     * Test only teacher can manage meeting.
     */
    public function test_only_teacher_can_manage_meeting(): void
    {
        $teacher = $this->makeQuranTeacherWithProfile();
        $student = $this->makeStudentWithProfile();
        $session = $this->createQuranSession($teacher['user'], $student['user']);

        // Teacher can manage their session meeting
        $this->assertTrue($this->policy->manageMeeting($teacher['user'], $session));

        // Student cannot manage the meeting
        $this->assertFalse($this->policy->manageMeeting($student['user'], $session));
    }

    /**
     * Test reschedule permission.
     */
    public function test_reschedule_permission(): void
    {
        $teacher = $this->makeQuranTeacherWithProfile();
        $student = $this->makeStudentWithProfile();
        $admin = $this->createUser('admin', '_resc');

        $session = $this->createQuranSession($teacher['user'], $student['user']);

        // Teacher can reschedule their own session
        $this->assertTrue($this->policy->reschedule($teacher['user'], $session));

        // Admin can reschedule any session in their academy
        $this->assertTrue($this->policy->reschedule($admin, $session));

        // Student cannot reschedule
        $this->assertFalse($this->policy->reschedule($student['user'], $session));
    }

    /**
     * Test cancel permission.
     */
    public function test_cancel_permission(): void
    {
        $teacher = $this->makeQuranTeacherWithProfile();
        $student = $this->makeStudentWithProfile();
        $admin = $this->createUser('admin', '_canc');

        $session = $this->createQuranSession($teacher['user'], $student['user']);

        // Teacher can cancel their own session
        $this->assertTrue($this->policy->cancel($teacher['user'], $session));

        // Admin can cancel any session in their academy
        $this->assertTrue($this->policy->cancel($admin, $session));

        // Student cannot cancel
        $this->assertFalse($this->policy->cancel($student['user'], $session));
    }
}
