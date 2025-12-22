<?php

use App\Enums\SessionStatus;
use App\Models\Academy;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\User;

describe('AcademicSession Model', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
        $this->teacherProfile = AcademicTeacherProfile::factory()->create([
            'user_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);
        $this->student = User::factory()->student()->forAcademy($this->academy)->create();
    });

    describe('factory', function () {
        it('can create an academic session using factory', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
            ]);

            expect($session)->toBeInstanceOf(AcademicSession::class)
                ->and($session->id)->toBeInt()
                ->and($session->session_code)->toBeString();
        });

        it('auto-generates session code on creation', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
            ]);

            expect($session->session_code)->toStartWith('AS-');
        });

        it('creates session with default status as scheduled', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
            ]);

            expect($session->status)->toBe(SessionStatus::SCHEDULED);
        });
    });

    describe('relationships', function () {
        it('belongs to an academy', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
            ]);

            expect($session->academy)->toBeInstanceOf(Academy::class)
                ->and($session->academy->id)->toBe($this->academy->id);
        });

        it('belongs to an academic teacher', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
            ]);

            expect($session->academicTeacher)->toBeInstanceOf(AcademicTeacherProfile::class);
        });

        it('belongs to a student', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
            ]);

            expect($session->student)->toBeInstanceOf(User::class)
                ->and($session->student->id)->toBe($this->student->id);
        });

        it('has many session reports', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
            ]);

            AcademicSessionReport::factory()->create([
                'session_id' => $session->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->id, // Should be User ID, not profile ID
                'academy_id' => $this->academy->id,
            ]);

            expect($session->sessionReports)->toHaveCount(1);
        });
    });

    describe('scopes', function () {
        it('can filter sessions for a teacher', function () {
            AcademicSession::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
            ]);

            $otherTeacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $otherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $otherTeacher->id,
                'academy_id' => $this->academy->id,
            ]);

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $otherProfile->id,
            ]);

            expect(AcademicSession::forTeacher($this->teacherProfile->id)->count())->toBe(2);
        });

        it('can filter sessions for a student', function () {
            AcademicSession::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
            ]);

            $otherStudent = User::factory()->student()->forAcademy($this->academy)->create();
            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $otherStudent->id,
            ]);

            expect(AcademicSession::forStudent($this->student->id)->count())->toBe(2);
        });

        it('can filter individual sessions', function () {
            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'session_type' => 'individual',
            ]);

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'session_type' => 'interactive_course',
            ]);

            expect(AcademicSession::individual()->count())->toBe(1);
        });
    });

    describe('attributes and casts', function () {
        it('casts status to SessionStatus enum', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'status' => SessionStatus::SCHEDULED,
            ]);

            expect($session->status)->toBeInstanceOf(SessionStatus::class);
        });

        it('casts scheduled_at to datetime', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'scheduled_at' => now(),
            ]);

            expect($session->scheduled_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
        });

        it('casts subscription_counted to boolean', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'subscription_counted' => 1,
            ]);

            expect($session->subscription_counted)->toBeBool();
        });

        it('casts recording_enabled to boolean', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'recording_enabled' => true,
            ]);

            expect($session->recording_enabled)->toBeBool()->toBeTrue();
        });

        it('casts homework_assigned to boolean', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'homework_assigned' => true,
            ]);

            expect($session->homework_assigned)->toBeBool();
        });
    });

    describe('accessors', function () {
        it('returns display name with session code', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'title' => 'Math Lesson',
            ]);

            expect($session->display_name)->toContain('Math Lesson')
                ->and($session->display_name)->toContain($session->session_code);
        });

        it('returns formatted duration', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'duration_minutes' => 90,
            ]);

            expect($session->formatted_duration)->toBe('1h 30m');
        });

        it('returns formatted duration for minutes only', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'duration_minutes' => 45,
            ]);

            expect($session->formatted_duration)->toBe('45m');
        });

        it('returns status badge color', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'status' => SessionStatus::SCHEDULED,
            ]);

            expect($session->status_badge_color)->toBe('blue');
        });
    });

    describe('methods', function () {
        it('can check if session is individual', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'session_type' => 'individual',
            ]);

            expect($session->isIndividual())->toBeTrue();
        });

        it('can check if session has homework', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'homework_description' => 'Complete exercises 1-10',
            ]);

            expect($session->hasHomework())->toBeTrue();
        });

        it('returns false for has homework when empty', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'homework_description' => null,
                'homework_file' => null,
            ]);

            expect($session->hasHomework())->toBeFalse();
        });

        it('returns meeting type as academic', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
            ]);

            expect($session->getMeetingType())->toBe('academic');
        });

        it('returns meeting configuration', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
            ]);

            $config = $session->getMeetingConfiguration();

            expect($config)->toBeArray()
                ->and($config['max_participants'])->toBe(2)
                ->and($config['chat_enabled'])->toBeTrue()
                ->and($config['screen_sharing_enabled'])->toBeTrue();
        });

        it('can check if user can manage meeting', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
            ]);

            expect($session->canUserManageMeeting($this->teacher))->toBeTrue();
        });

        it('returns participants list', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
            ]);

            $participants = $session->getParticipants();

            expect($participants)->toBeArray()
                ->and(count($participants))->toBeGreaterThanOrEqual(1);
        });

        it('can check if user is participant', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
            ]);

            expect($session->isUserParticipant($this->student))->toBeTrue();
        });
    });

    describe('status management', function () {
        it('can mark session as ongoing', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $result = $session->markAsOngoing();

            expect($result)->toBeTrue()
                ->and($session->fresh()->status)->toBe(SessionStatus::ONGOING);
        });

        it('can mark session as completed', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'status' => SessionStatus::ONGOING,
            ]);

            $result = $session->markAsCompleted();

            expect($result)->toBeTrue()
                ->and($session->fresh()->status)->toBe(SessionStatus::COMPLETED);
        });

        it('can mark session as cancelled', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $result = $session->markAsCancelled('Student unavailable');

            expect($result)->toBeTrue()
                ->and($session->fresh()->status)->toBe(SessionStatus::CANCELLED)
                ->and($session->fresh()->cancellation_reason)->toBe('Student unavailable');
        });

        it('can mark session as absent', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'status' => SessionStatus::SCHEDULED,
                'session_type' => 'individual',
                'scheduled_at' => now()->subHour(),
            ]);

            $result = $session->markAsAbsent('Student did not show up');

            expect($result)->toBeTrue()
                ->and($session->fresh()->status)->toBe(SessionStatus::ABSENT)
                ->and($session->fresh()->cancellation_reason)->toBe('Student did not show up');
        });
    });

    describe('default attributes', function () {
        it('has default session type as individual', function () {
            $session = new AcademicSession();

            expect($session->session_type)->toBe('individual');
        });

        it('has default status as scheduled', function () {
            $session = new AcademicSession();

            // Status is cast to enum, but default value is set as string
            // When accessed, Laravel casts it to the enum
            expect($session->status)->toBe(SessionStatus::SCHEDULED);
        });

        it('has default duration of 60 minutes', function () {
            $session = new AcademicSession();

            expect($session->duration_minutes)->toBe(60);
        });

        it('has subscription_counted as false by default', function () {
            $session = new AcademicSession();

            expect($session->subscription_counted)->toBeFalse();
        });

        it('has recording_enabled as false by default', function () {
            $session = new AcademicSession();

            expect($session->recording_enabled)->toBeFalse();
        });
    });
});
