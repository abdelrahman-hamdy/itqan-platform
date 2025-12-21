<?php

use App\Enums\SessionStatus;
use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\User;

describe('QuranSession Model', function () {
    describe('factory', function () {
        it('creates a session with default attributes', function () {
            $session = QuranSession::factory()->create();

            expect($session)->toBeInstanceOf(QuranSession::class)
                ->and($session->exists)->toBeTrue()
                ->and($session->session_type)->toBe('individual')
                ->and($session->status)->toBe(SessionStatus::SCHEDULED)
                ->and($session->duration_minutes)->toBe(45);
        });

        it('creates an individual session', function () {
            $session = QuranSession::factory()->individual()->create();

            expect($session->session_type)->toBe('individual')
                ->and($session->student_id)->not->toBeNull();
        });

        it('creates a group session', function () {
            $session = QuranSession::factory()->group()->create();

            expect($session->session_type)->toBe('group')
                ->and($session->student_id)->toBeNull();
        });

        it('creates a scheduled session', function () {
            $session = QuranSession::factory()->scheduled()->create();

            expect($session->status)->toBe(SessionStatus::SCHEDULED)
                ->and($session->scheduled_at)->not->toBeNull();
        });

        it('creates an ongoing session', function () {
            $session = QuranSession::factory()->ongoing()->create();

            expect($session->status)->toBe(SessionStatus::ONGOING)
                ->and($session->started_at)->not->toBeNull();
        });

        it('creates a completed session', function () {
            $session = QuranSession::factory()->completed()->create();

            expect($session->status)->toBe(SessionStatus::COMPLETED)
                ->and($session->started_at)->not->toBeNull()
                ->and($session->ended_at)->not->toBeNull()
                ->and($session->actual_duration_minutes)->toBe(45);
        });

        it('creates a cancelled session', function () {
            $session = QuranSession::factory()->cancelled()->create();

            expect($session->status)->toBe(SessionStatus::CANCELLED)
                ->and($session->cancelled_at)->not->toBeNull()
                ->and($session->cancellation_reason)->not->toBeNull();
        });

        it('creates an absent session', function () {
            $session = QuranSession::factory()->absent()->create();

            expect($session->status)->toBe(SessionStatus::ABSENT)
                ->and($session->attendance_status)->toBe('absent');
        });

        it('creates a ready session with meeting data', function () {
            $session = QuranSession::factory()->ready()->create();

            expect($session->status)->toBe(SessionStatus::READY)
                ->and($session->meeting_room_name)->not->toBeNull()
                ->and($session->meeting_link)->not->toBeNull();
        });

        it('creates a session for a specific teacher', function () {
            $teacher = User::factory()->quranTeacher()->create();
            $session = QuranSession::factory()->forTeacher($teacher)->create();

            expect($session->quran_teacher_id)->toBe($teacher->id)
                ->and($session->academy_id)->toBe($teacher->academy_id);
        });

        it('creates a session for a specific student', function () {
            $student = User::factory()->student()->create();
            $session = QuranSession::factory()->forStudent($student)->create();

            expect($session->student_id)->toBe($student->id)
                ->and($session->academy_id)->toBe($student->academy_id);
        });
    });

    describe('relationships', function () {
        it('belongs to an academy', function () {
            $academy = Academy::factory()->create();
            $session = QuranSession::factory()->create(['academy_id' => $academy->id]);

            expect($session->academy)->toBeInstanceOf(Academy::class)
                ->and($session->academy->id)->toBe($academy->id);
        });

        it('belongs to a quran teacher', function () {
            $teacher = User::factory()->quranTeacher()->create();
            $session = QuranSession::factory()->create(['quran_teacher_id' => $teacher->id]);

            expect($session->quranTeacher)->toBeInstanceOf(User::class)
                ->and($session->quranTeacher->id)->toBe($teacher->id);
        });

        it('belongs to a student for individual sessions', function () {
            $student = User::factory()->student()->create();
            $session = QuranSession::factory()->individual()->create([
                'student_id' => $student->id,
            ]);

            expect($session->student)->toBeInstanceOf(User::class)
                ->and($session->student->id)->toBe($student->id);
        });
    });

    describe('status enum casting', function () {
        it('casts status to SessionStatus enum', function () {
            $session = QuranSession::factory()->create(['status' => 'scheduled']);

            expect($session->status)->toBe(SessionStatus::SCHEDULED)
                ->and($session->status)->toBeInstanceOf(SessionStatus::class);
        });

        it('handles all status values correctly', function () {
            foreach (SessionStatus::cases() as $status) {
                $session = QuranSession::factory()->create(['status' => $status]);

                expect($session->status)->toBe($status);
            }
        });
    });

    describe('datetime casting', function () {
        it('casts scheduled_at to Carbon instance', function () {
            $session = QuranSession::factory()->create();

            expect($session->scheduled_at)->toBeInstanceOf(\Carbon\Carbon::class);
        });

        it('casts started_at to Carbon instance when set', function () {
            $session = QuranSession::factory()->ongoing()->create();

            expect($session->started_at)->toBeInstanceOf(\Carbon\Carbon::class);
        });

        it('casts ended_at to Carbon instance when set', function () {
            $session = QuranSession::factory()->completed()->create();

            expect($session->ended_at)->toBeInstanceOf(\Carbon\Carbon::class);
        });
    });

    describe('scopes', function () {
        beforeEach(function () {
            // Create sessions with different statuses
            QuranSession::factory()->scheduled()->create();
            QuranSession::factory()->completed()->create();
            QuranSession::factory()->cancelled()->create();
            QuranSession::factory()->ongoing()->create();
        });

        it('filters scheduled sessions', function () {
            $sessions = QuranSession::scheduled()->get();

            expect($sessions)->each(fn ($session) =>
                $session->status->toBe(SessionStatus::SCHEDULED)
            );
        });

        it('filters completed sessions', function () {
            $sessions = QuranSession::completed()->get();

            expect($sessions)->each(fn ($session) =>
                $session->status->toBe(SessionStatus::COMPLETED)
            );
        });

        it('filters cancelled sessions', function () {
            $sessions = QuranSession::cancelled()->get();

            expect($sessions)->each(fn ($session) =>
                $session->status->toBe(SessionStatus::CANCELLED)
            );
        });

        it('filters ongoing sessions', function () {
            $sessions = QuranSession::ongoing()->get();

            expect($sessions)->each(fn ($session) =>
                $session->status->toBe(SessionStatus::ONGOING)
            );
        });
    });

    describe('fillable attributes', function () {
        it('allows mass assignment of session fields', function () {
            $academy = Academy::factory()->create();
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();
            $student = User::factory()->student()->forAcademy($academy)->create();

            $session = QuranSession::factory()->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'session_type' => 'individual',
                'status' => SessionStatus::SCHEDULED,
                'duration_minutes' => 60,
                'current_surah' => 2,
                'current_page' => 50,
            ]);

            expect($session->academy_id)->toBe($academy->id)
                ->and($session->quran_teacher_id)->toBe($teacher->id)
                ->and($session->student_id)->toBe($student->id)
                ->and($session->session_type)->toBe('individual')
                ->and($session->status)->toBe(SessionStatus::SCHEDULED)
                ->and($session->duration_minutes)->toBe(60)
                ->and($session->current_surah)->toBe(2)
                ->and($session->current_page)->toBe(50);
        });
    });

    describe('meeting data', function () {
        it('stores meeting information', function () {
            $session = QuranSession::factory()->withMeeting()->create();

            expect($session->meeting_room_name)->not->toBeNull()
                ->and($session->meeting_link)->not->toBeNull()
                ->and($session->meeting_id)->not->toBeNull()
                ->and($session->meeting_platform)->toBe('livekit');
        });
    });

    describe('homework', function () {
        it('can store homework details', function () {
            $session = QuranSession::factory()->create([
                'homework_details' => 'Memorize Surah Al-Fatiha',
            ]);

            expect($session->homework_details)->toBe('Memorize Surah Al-Fatiha');
        });
    });
});
