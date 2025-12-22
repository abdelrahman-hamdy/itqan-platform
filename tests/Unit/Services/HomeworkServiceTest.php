<?php

use App\Models\Academy;
use App\Models\AcademicHomework;
use App\Models\AcademicHomeworkSubmission;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\User;
use App\Services\HomeworkService;

describe('HomeworkService', function () {
    beforeEach(function () {
        $this->service = new HomeworkService();
        $this->academy = Academy::factory()->create();
        $this->teacherUser = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
        $this->teacherProfile = AcademicTeacherProfile::factory()->create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->teacherUser->id,
        ]);
        $this->student = User::factory()->student()->forAcademy($this->academy)->create();
        $this->academicSession = AcademicSession::factory()->create([
            'academy_id' => $this->academy->id,
            'academic_teacher_id' => $this->teacherProfile->id,
            'student_id' => $this->student->id,
        ]);
    });

    describe('createAcademicHomework', function () {
        it('creates academic homework', function () {
            $homework = $this->service->createAcademicHomework([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $this->academicSession->id,
                'teacher_id' => $this->teacherUser->id,
                'title' => 'Homework Assignment',
                'description' => 'Complete the exercises',
                'due_date' => now()->addWeek(),
                'assigned_at' => now(),
                'max_score' => 100,
            ]);

            expect($homework)->toBeInstanceOf(AcademicHomework::class)
                ->and($homework->title)->toBe('Homework Assignment')
                ->and((int) $homework->max_score)->toBe(100);
        });

        it('creates homework with all fields', function () {
            $dueDate = now()->addWeek();
            $homework = $this->service->createAcademicHomework([
                'academy_id' => $this->academy->id,
                'academic_session_id' => $this->academicSession->id,
                'teacher_id' => $this->teacherUser->id,
                'title' => 'Math Homework',
                'description' => 'Solve problems 1-10',
                'due_date' => $dueDate,
                'assigned_at' => now(),
                'max_score' => 50,
                'is_mandatory' => true,
            ]);

            expect($homework->title)->toBe('Math Homework')
                ->and($homework->description)->toBe('Solve problems 1-10')
                ->and((int) $homework->max_score)->toBe(50);
        });
    });

    describe('gradeAcademicHomework', function () {
        it('grades homework submission', function () {
            $homework = AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'teacher_id' => $this->teacherUser->id,
                'max_score' => 100,
            ]);

            $submission = AcademicHomeworkSubmission::factory()->submitted()->create([
                'academy_id' => $this->academy->id,
                'academic_homework_id' => $homework->id,
                'student_id' => $this->student->id,
            ]);

            $gradedSubmission = $this->service->gradeAcademicHomework(
                $submission->id,
                85.5,
                'Good work!',
                null,
                $this->teacherUser->id
            );

            expect((float) $gradedSubmission->score)->toBe(85.5)
                ->and($gradedSubmission->teacher_feedback)->toBe('Good work!')
                ->and($gradedSubmission->submission_status)->toBe('graded');
        });
    });

    describe('getStudentHomeworkStatistics', function () {
        it('returns empty statistics when no homework', function () {
            $stats = $this->service->getStudentHomeworkStatistics($this->student->id, $this->academy->id);

            expect($stats)->toBeArray()
                ->and($stats['total'])->toBe(0)
                ->and($stats['submitted'])->toBe(0)
                ->and($stats['graded'])->toBe(0);
        });

        it('returns statistics structure', function () {
            $stats = $this->service->getStudentHomeworkStatistics($this->student->id, $this->academy->id);

            expect($stats)->toHaveKeys([
                'total',
                'submitted',
                'graded',
                'overdue',
                'late',
                'pending',
                'submission_rate',
                'average_score',
            ]);
        });
    });

    describe('getPendingHomework', function () {
        it('returns empty array when no pending homework', function () {
            $pending = $this->service->getPendingHomework($this->student->id, $this->academy->id);

            expect($pending)->toBeArray()
                ->and($pending)->toBeEmpty();
        });
    });

    describe('getTeacherHomeworkStatistics', function () {
        it('returns teacher homework statistics', function () {
            $stats = $this->service->getTeacherHomeworkStatistics($this->teacherUser->id, $this->academy->id);

            expect($stats)->toBeArray()
                ->and($stats)->toHaveKey('total_homework')
                ->and($stats)->toHaveKey('total_submissions')
                ->and($stats)->toHaveKey('graded')
                ->and($stats)->toHaveKey('pending_grading')
                ->and($stats)->toHaveKey('average_score');
        });
    });

    describe('returnHomeworkToStudent', function () {
        it('returns homework to student after grading', function () {
            $homework = AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'teacher_id' => $this->teacherUser->id,
            ]);

            $submission = AcademicHomeworkSubmission::factory()->graded()->create([
                'academy_id' => $this->academy->id,
                'academic_homework_id' => $homework->id,
                'student_id' => $this->student->id,
            ]);

            $returned = $this->service->returnHomeworkToStudent($submission->id);

            expect($returned->submission_status)->toBe('returned');
        });
    });

    describe('requestRevision', function () {
        it('requests revision for homework submission', function () {
            $homework = AcademicHomework::factory()->create([
                'academy_id' => $this->academy->id,
                'teacher_id' => $this->teacherUser->id,
            ]);

            $submission = AcademicHomeworkSubmission::factory()->submitted()->create([
                'academy_id' => $this->academy->id,
                'academic_homework_id' => $homework->id,
                'student_id' => $this->student->id,
            ]);

            $revised = $this->service->requestRevision($submission->id, 'Please add more details');

            expect($revised->submission_status)->toBe('revision_requested')
                ->and($revised->teacher_feedback)->toContain('مطلوب تعديل')
                ->and($revised->teacher_feedback)->toContain('Please add more details');
        });
    });
});
