<?php

use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\User;
use App\Policies\SessionPolicy;

describe('SessionPolicy', function () {
    beforeEach(function () {
        $this->policy = new SessionPolicy();
        $this->academy = Academy::factory()->create();
    });

    describe('viewAny()', function () {
        it('allows super admin to view any sessions', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows academy admin to view sessions', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows supervisors to view sessions', function () {
            $user = User::factory()->supervisor()->forAcademy($this->academy)->create();

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows quran teachers to view sessions', function () {
            $user = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows academic teachers to view sessions', function () {
            $user = User::factory()->academicTeacher()->forAcademy($this->academy)->create();

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows students to view sessions', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('denies parents from viewAny', function () {
            $user = User::factory()->parent()->forAcademy($this->academy)->create();

            expect($this->policy->viewAny($user))->toBeFalse();
        });
    });

    describe('view()', function () {
        it('allows super admin to view any session', function () {
            $user = User::factory()->superAdmin()->create();
            $session = QuranSession::factory()->create(['academy_id' => $this->academy->id]);

            expect($this->policy->view($user, $session))->toBeTrue();
        });

        it('allows academy admin to view sessions in their academy', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create(['academy_id' => $this->academy->id]);

            expect($this->policy->view($user, $session))->toBeTrue();
        });

        it('denies academy admin from viewing sessions in other academies', function () {
            $otherAcademy = Academy::factory()->create();
            $user = User::factory()->admin()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create(['academy_id' => $otherAcademy->id]);

            expect($this->policy->view($user, $session))->toBeFalse();
        });

        it('allows teachers to view their own sessions', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            expect($this->policy->view($teacher, $session))->toBeTrue();
        });

        it('denies teachers from viewing other teachers sessions', function () {
            $teacher1 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacher2 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher2->id,
            ]);

            expect($this->policy->view($teacher1, $session))->toBeFalse();
        });

        it('allows students to view their enrolled sessions', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            expect($this->policy->view($student, $session))->toBeTrue();
        });

        it('denies students from viewing other students sessions', function () {
            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student2->id,
            ]);

            expect($this->policy->view($student1, $session))->toBeFalse();
        });
    });

    describe('create()', function () {
        it('allows super admin to create sessions', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows academy admin to create sessions', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();

            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows supervisors to create sessions', function () {
            $user = User::factory()->supervisor()->forAcademy($this->academy)->create();

            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows quran teachers to create sessions', function () {
            $user = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows academic teachers to create sessions', function () {
            $user = User::factory()->academicTeacher()->forAcademy($this->academy)->create();

            expect($this->policy->create($user))->toBeTrue();
        });

        it('denies students from creating sessions', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            expect($this->policy->create($user))->toBeFalse();
        });

        it('denies parents from creating sessions', function () {
            $user = User::factory()->parent()->forAcademy($this->academy)->create();

            expect($this->policy->create($user))->toBeFalse();
        });
    });

    describe('update()', function () {
        it('allows super admin to update any session', function () {
            $user = User::factory()->superAdmin()->create();
            $session = QuranSession::factory()->create(['academy_id' => $this->academy->id]);

            expect($this->policy->update($user, $session))->toBeTrue();
        });

        it('allows academy admin to update sessions in their academy', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create(['academy_id' => $this->academy->id]);

            expect($this->policy->update($user, $session))->toBeTrue();
        });

        it('allows teachers to update their own sessions', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            expect($this->policy->update($teacher, $session))->toBeTrue();
        });

        it('denies teachers from updating other teachers sessions', function () {
            $teacher1 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacher2 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher2->id,
            ]);

            expect($this->policy->update($teacher1, $session))->toBeFalse();
        });

        it('denies students from updating sessions', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            expect($this->policy->update($student, $session))->toBeFalse();
        });
    });

    describe('delete()', function () {
        it('allows super admin to delete any session', function () {
            $user = User::factory()->superAdmin()->create();
            $session = QuranSession::factory()->create(['academy_id' => $this->academy->id]);

            expect($this->policy->delete($user, $session))->toBeTrue();
        });

        it('allows academy admin to delete sessions in their academy', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create(['academy_id' => $this->academy->id]);

            expect($this->policy->delete($user, $session))->toBeTrue();
        });

        it('denies supervisors from deleting sessions', function () {
            $user = User::factory()->supervisor()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create(['academy_id' => $this->academy->id]);

            expect($this->policy->delete($user, $session))->toBeFalse();
        });

        it('denies teachers from deleting sessions', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            expect($this->policy->delete($teacher, $session))->toBeFalse();
        });

        it('denies students from deleting sessions', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            expect($this->policy->delete($student, $session))->toBeFalse();
        });
    });

    describe('manageMeeting()', function () {
        it('allows super admin to manage any session meeting', function () {
            $user = User::factory()->superAdmin()->create();
            $session = QuranSession::factory()->create(['academy_id' => $this->academy->id]);

            expect($this->policy->manageMeeting($user, $session))->toBeTrue();
        });

        it('allows academy admin to manage session meetings', function () {
            $user = User::factory()->admin()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create(['academy_id' => $this->academy->id]);

            expect($this->policy->manageMeeting($user, $session))->toBeTrue();
        });

        it('allows teachers to manage their own session meetings', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            expect($this->policy->manageMeeting($teacher, $session))->toBeTrue();
        });

        it('denies teachers from managing other session meetings', function () {
            $teacher1 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacher2 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher2->id,
            ]);

            expect($this->policy->manageMeeting($teacher1, $session))->toBeFalse();
        });

        it('denies students from managing meetings', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            expect($this->policy->manageMeeting($student, $session))->toBeFalse();
        });
    });

    describe('reschedule()', function () {
        it('uses same logic as update', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            expect($this->policy->reschedule($teacher, $session))
                ->toBe($this->policy->update($teacher, $session));
        });
    });

    describe('cancel()', function () {
        it('uses same logic as update', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            expect($this->policy->cancel($teacher, $session))
                ->toBe($this->policy->update($teacher, $session));
        });
    });
});
