<?php

use App\Enums\SessionStatus;
use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\Certificate;
use App\Models\CourseSubscription;
use App\Models\ParentProfile;
use App\Models\Payment;
use App\Models\QuizAttempt;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ParentDataService;
use Illuminate\Http\Exceptions\HttpResponseException;

describe('ParentDataService', function () {
    beforeEach(function () {
        $this->service = new ParentDataService();
        $this->academy = Academy::factory()->create();

        $parentUser = User::factory()->parent()->forAcademy($this->academy)->create();
        $this->parent = ParentProfile::factory()->create([
            'academy_id' => $this->academy->id,
            'user_id' => $parentUser->id,
        ]);

        $studentUser = User::factory()->student()->forAcademy($this->academy)->create();
        $this->student = StudentProfile::factory()->create([
            'user_id' => $studentUser->id,
        ]);

        $this->parent->students()->attach($this->student->id, [
            'relationship_type' => 'father',
        ]);
    });

    describe('getChildren()', function () {
        it('returns all linked children for parent', function () {
            $children = $this->service->getChildren($this->parent);

            expect($children)->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($children)->toHaveCount(1)
                ->and($children->first()->id)->toBe($this->student->id);
        });

        it('returns children with user relationship loaded', function () {
            $children = $this->service->getChildren($this->parent);

            expect($children->first()->relationLoaded('user'))->toBeTrue();
        });

        it('returns empty collection when parent has no children', function () {
            $parentUser = User::factory()->parent()->forAcademy($this->academy)->create();
            $parentWithoutChildren = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $parentUser->id,
            ]);

            $children = $this->service->getChildren($parentWithoutChildren);

            expect($children)->toBeEmpty();
        });

        it('filters children by parent academy', function () {
            $otherAcademy = Academy::factory()->create();
            $otherStudentUser = User::factory()->student()->forAcademy($otherAcademy)->create();
            $otherStudent = StudentProfile::factory()->create([
                'user_id' => $otherStudentUser->id,
            ]);

            $this->parent->students()->attach($otherStudent->id);

            $children = $this->service->getChildren($this->parent);

            expect($children)->toHaveCount(1)
                ->and($children->first()->id)->toBe($this->student->id);
        });

        it('returns multiple children when parent has many', function () {
            $studentUser2 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = StudentProfile::factory()->create([
                'user_id' => $studentUser2->id,
            ]);

            $studentUser3 = User::factory()->student()->forAcademy($this->academy)->create();
            $student3 = StudentProfile::factory()->create([
                'user_id' => $studentUser3->id,
            ]);

            $this->parent->students()->attach([
                $student2->id => ['relationship_type' => 'father'],
                $student3->id => ['relationship_type' => 'father'],
            ]);

            $children = $this->service->getChildren($this->parent);

            expect($children)->toHaveCount(3);
        });
    });

    describe('getChildData()', function () {
        it('returns child data with user relationship', function () {
            $data = $this->service->getChildData($this->parent, $this->student->id);

            expect($data)->toHaveKeys(['child', 'user', 'relationship_type', 'subscriptions_count', 'certificates_count', 'upcoming_sessions_count'])
                ->and($data['child']->id)->toBe($this->student->id)
                ->and($data['user']->id)->toBe($this->student->user_id)
                ->and($data['relationship_type'])->toBe('father');
        });

        it('returns correct subscriptions count', function () {
            QuranSubscription::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => 'active',
            ]);

            $data = $this->service->getChildData($this->parent, $this->student->id);

            expect($data['subscriptions_count'])->toBe(1);
        });

        it('throws 403 when parent tries to access unlinked child', function () {
            $otherStudentUser = User::factory()->student()->forAcademy($this->academy)->create();
            $otherStudent = StudentProfile::factory()->create([
                'user_id' => $otherStudentUser->id,
            ]);

            expect(fn () => $this->service->getChildData($this->parent, $otherStudent->id))
                ->toThrow(HttpResponseException::class);
        });

        it('throws 403 when accessing child from different academy', function () {
            $otherAcademy = Academy::factory()->create();
            $otherStudentUser = User::factory()->student()->forAcademy($otherAcademy)->create();
            $otherStudent = StudentProfile::factory()->create([
                'user_id' => $otherStudentUser->id,
            ]);

            expect(fn () => $this->service->getChildData($this->parent, $otherStudent->id))
                ->toThrow(HttpResponseException::class);
        });
    });

    describe('getChildSubscriptions()', function () {
        it('returns all subscription types', function () {
            $subscriptions = $this->service->getChildSubscriptions($this->parent, $this->student->id);

            expect($subscriptions)->toHaveKeys(['quran', 'academic', 'courses'])
                ->and($subscriptions['quran'])->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($subscriptions['academic'])->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($subscriptions['courses'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
        });

        it('returns quran subscriptions with relationships', function () {
            QuranSubscription::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
            ]);

            $subscriptions = $this->service->getChildSubscriptions($this->parent, $this->student->id);

            expect($subscriptions['quran'])->toHaveCount(1);
        });

        it('filters subscriptions by academy', function () {
            QuranSubscription::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
            ]);

            $otherAcademy = Academy::factory()->create();

            $otherTeacher = User::factory()->quranTeacher()->forAcademy($otherAcademy)->create();
            QuranSubscription::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $otherAcademy->id,
                'quran_teacher_id' => $otherTeacher->id,
            ]);

            $subscriptions = $this->service->getChildSubscriptions($this->parent, $this->student->id);

            expect($subscriptions['quran'])->toHaveCount(1);
        });

        it('orders subscriptions by created_at desc', function () {
            QuranSubscription::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'created_at' => now()->subDays(2),
            ]);

            QuranSubscription::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'created_at' => now()->subDay(),
            ]);

            $subscriptions = $this->service->getChildSubscriptions($this->parent, $this->student->id);

            expect($subscriptions['quran']->first()->created_at)
                ->toBeGreaterThan($subscriptions['quran']->last()->created_at);
        });

        it('throws 403 when accessing unlinked child subscriptions', function () {
            $otherStudentUser = User::factory()->student()->forAcademy($this->academy)->create();
            $otherStudent = StudentProfile::factory()->create([
                'user_id' => $otherStudentUser->id,
            ]);

            expect(fn () => $this->service->getChildSubscriptions($this->parent, $otherStudent->id))
                ->toThrow(HttpResponseException::class);
        });
    });

    describe('getChildUpcomingSessions()', function () {
        it('returns upcoming sessions sorted by scheduled_at', function () {
            QuranSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(2),
            ]);

            QuranSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDay(),
            ]);

            $sessions = $this->service->getChildUpcomingSessions($this->parent, $this->student->id);

            expect($sessions)->toHaveCount(2)
                ->and($sessions->first()->scheduled_at)->toBeLessThan($sessions->last()->scheduled_at);
        });

        it('includes both quran and academic sessions', function () {
            QuranSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDay(),
            ]);

            AcademicSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(2),
            ]);

            $sessions = $this->service->getChildUpcomingSessions($this->parent, $this->student->id);

            expect($sessions)->toHaveCount(2);
        });

        it('only returns scheduled sessions', function () {
            QuranSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDay(),
            ]);

            QuranSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => now()->addDays(2),
            ]);

            $sessions = $this->service->getChildUpcomingSessions($this->parent, $this->student->id);

            expect($sessions)->toHaveCount(1)
                ->and($sessions->first()->status)->toBe(SessionStatus::SCHEDULED);
        });

        it('only returns future sessions', function () {
            QuranSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->subDay(),
            ]);

            QuranSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDay(),
            ]);

            $sessions = $this->service->getChildUpcomingSessions($this->parent, $this->student->id);

            expect($sessions)->toHaveCount(1);
        });

        it('filters sessions by academy', function () {
            QuranSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDay(),
            ]);

            $otherAcademy = Academy::factory()->create();
            QuranSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $otherAcademy->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(2),
            ]);

            $sessions = $this->service->getChildUpcomingSessions($this->parent, $this->student->id);

            expect($sessions)->toHaveCount(1);
        });

        it('throws 403 when accessing unlinked child sessions', function () {
            $otherStudentUser = User::factory()->student()->forAcademy($this->academy)->create();
            $otherStudent = StudentProfile::factory()->create([
                'user_id' => $otherStudentUser->id,
            ]);

            expect(fn () => $this->service->getChildUpcomingSessions($this->parent, $otherStudent->id))
                ->toThrow(HttpResponseException::class);
        });
    });

    describe('getChildProgressReport()', function () {
        it('returns progress report with all statistics', function () {
            $report = $this->service->getChildProgressReport($this->parent, $this->student->id);

            expect($report)->toHaveKeys([
                'quran_sessions_total',
                'quran_sessions_completed',
                'academic_sessions_total',
                'academic_sessions_completed',
                'total_sessions',
                'completed_sessions',
                'attendance_rate',
                'certificates_count',
            ]);
        });

        it('calculates quran session statistics correctly', function () {
            QuranSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            QuranSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $report = $this->service->getChildProgressReport($this->parent, $this->student->id);

            expect($report['quran_sessions_total'])->toBe(2)
                ->and($report['quran_sessions_completed'])->toBe(1);
        });

        it('calculates academic session statistics correctly', function () {
            AcademicSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            AcademicSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::SCHEDULED,
            ]);

            AcademicSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            $report = $this->service->getChildProgressReport($this->parent, $this->student->id);

            expect($report['academic_sessions_total'])->toBe(3)
                ->and($report['academic_sessions_completed'])->toBe(2);
        });

        it('calculates total sessions correctly', function () {
            QuranSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            AcademicSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $report = $this->service->getChildProgressReport($this->parent, $this->student->id);

            expect($report['total_sessions'])->toBe(2)
                ->and($report['completed_sessions'])->toBe(1);
        });

        it('calculates attendance rate correctly', function () {
            QuranSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            QuranSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::SCHEDULED,
            ]);

            AcademicSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            AcademicSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $report = $this->service->getChildProgressReport($this->parent, $this->student->id);

            expect($report['attendance_rate'])->toBe(50.0);
        });

        it('returns zero attendance rate when no sessions exist', function () {
            $report = $this->service->getChildProgressReport($this->parent, $this->student->id);

            expect($report['attendance_rate'])->toBe(0)
                ->and($report['total_sessions'])->toBe(0)
                ->and($report['completed_sessions'])->toBe(0);
        });

        it('filters sessions by academy', function () {
            QuranSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            $otherAcademy = Academy::factory()->create();
            QuranSession::factory()->create([
                'student_id' => $this->student->user_id,
                'academy_id' => $otherAcademy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            $report = $this->service->getChildProgressReport($this->parent, $this->student->id);

            expect($report['quran_sessions_total'])->toBe(1);
        });

        it('throws 403 when accessing unlinked child progress', function () {
            $otherStudentUser = User::factory()->student()->forAcademy($this->academy)->create();
            $otherStudent = StudentProfile::factory()->create([
                'user_id' => $otherStudentUser->id,
            ]);

            expect(fn () => $this->service->getChildProgressReport($this->parent, $otherStudent->id))
                ->toThrow(HttpResponseException::class);
        });
    });
});
