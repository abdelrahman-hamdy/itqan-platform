<?php

use App\Enums\NotificationType;
use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\Certificate;
use App\Models\HomeworkSubmission;
use App\Models\ParentProfile;
use App\Models\QuizAssignment;
use App\Models\QuizAttempt;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\ParentNotificationService;
use Illuminate\Support\Facades\DB;

describe('ParentNotificationService', function () {
    beforeEach(function () {
        $this->notificationService = Mockery::mock(NotificationService::class);
        $this->service = new ParentNotificationService($this->notificationService);
        $this->academy = Academy::factory()->create();
    });

    describe('sendSessionReminder()', function () {
        it('sends notification to parent for quran session', function () {
            $parent = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent->id,
            ]);

            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $quranTeacher = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => $quranTeacher->id,
                'scheduled_at' => now()->addHours(2),
            ]);

            $this->notificationService->shouldReceive('send')
                ->once()
                ->withArgs(function ($user, $type, $data, $url, $metadata, $isCritical) use ($parent, $student, $teacher, $session) {
                    return $user->id === $parent->user->id
                        && $type === NotificationType::SESSION_REMINDER
                        && $data['child_name'] === $student->name
                        && $data['session_type'] === 'قرآن'
                        && $data['teacher_name'] === $teacher->name
                        && $data['scheduled_at'] === $session->scheduled_at->format('Y-m-d H:i')
                        && $metadata['session_id'] === $session->id
                        && $metadata['child_id'] === $student->id
                        && $isCritical === false;
                });

            $this->service->sendSessionReminder($session);
        });

        it('sends notification to parent for academic session', function () {
            $parent = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent->id,
            ]);

            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $academicTeacher = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'academic_teacher_id' => $academicTeacher->id,
                'scheduled_at' => now()->addHours(3),
            ]);

            $this->notificationService->shouldReceive('send')
                ->once()
                ->withArgs(function ($user, $type, $data, $url, $metadata, $isCritical) use ($parent, $student, $teacher) {
                    return $user->id === $parent->user->id
                        && $data['session_type'] === 'أكاديمية'
                        && $data['teacher_name'] === $teacher->name;
                });

            $this->service->sendSessionReminder($session);
        });

        it('sends to multiple parents if student has multiple parents', function () {
            $parent1 = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $parent2 = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent1->id,
            ]);

            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent2->id,
            ]);

            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            $this->notificationService->shouldReceive('send')->twice();

            $this->service->sendSessionReminder($session);
        });

        it('does not send notification if student not found', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => 99999,
            ]);

            $this->notificationService->shouldReceive('send')->never();

            $this->service->sendSessionReminder($session);
        });

        it('does not send notification if student has no parents', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            $this->notificationService->shouldReceive('send')->never();

            $this->service->sendSessionReminder($session);
        });

        it('uses default teacher name when teacher not found', function () {
            $parent = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent->id,
            ]);

            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => null,
            ]);

            $this->notificationService->shouldReceive('send')
                ->once()
                ->withArgs(function ($user, $type, $data) {
                    return $data['teacher_name'] === 'المعلم';
                });

            $this->service->sendSessionReminder($session);
        });
    });

    describe('sendHomeworkAssigned()', function () {
        it('sends notification to parent when homework is assigned', function () {
            $parent = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent->id,
            ]);

            $homework = HomeworkSubmission::factory()->create([
                'student_id' => $student->id,
                'title' => 'واجب الرياضيات',
                'due_date' => now()->addDays(3),
            ]);

            $this->notificationService->shouldReceive('send')
                ->once()
                ->withArgs(function ($user, $type, $data, $url, $metadata, $isCritical) use ($parent, $student, $homework) {
                    return $user->id === $parent->user->id
                        && $type === NotificationType::HOMEWORK_ASSIGNED
                        && $data['child_name'] === $student->name
                        && $data['homework_title'] === 'واجب الرياضيات'
                        && $data['due_date'] === $homework->due_date->format('Y-m-d')
                        && $metadata['homework_id'] === $homework->id
                        && $metadata['child_id'] === $student->id
                        && $isCritical === false;
                });

            $this->service->sendHomeworkAssigned($homework);
        });

        it('uses default homework title when not provided', function () {
            $parent = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent->id,
            ]);

            $homework = HomeworkSubmission::factory()->create([
                'student_id' => $student->id,
                'title' => null,
            ]);

            $this->notificationService->shouldReceive('send')
                ->once()
                ->withArgs(function ($user, $type, $data) {
                    return $data['homework_title'] === 'واجب جديد';
                });

            $this->service->sendHomeworkAssigned($homework);
        });

        it('does not send notification if student not found', function () {
            $homework = HomeworkSubmission::factory()->create([
                'student_id' => 99999,
            ]);

            $this->notificationService->shouldReceive('send')->never();

            $this->service->sendHomeworkAssigned($homework);
        });

        it('sends to multiple parents', function () {
            $parent1 = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $parent2 = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent1->id,
            ]);

            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent2->id,
            ]);

            $homework = HomeworkSubmission::factory()->create([
                'student_id' => $student->id,
            ]);

            $this->notificationService->shouldReceive('send')->twice();

            $this->service->sendHomeworkAssigned($homework);
        });
    });

    describe('sendCertificateIssued()', function () {
        it('sends notification to parent when certificate is issued', function () {
            $parent = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent->id,
            ]);

            $certificate = Certificate::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'certificate_number' => 'CERT-2024-001',
            ]);

            $this->notificationService->shouldReceive('send')
                ->once()
                ->withArgs(function ($user, $type, $data, $url, $metadata, $isCritical) use ($parent, $student, $certificate) {
                    return $user->id === $parent->user->id
                        && $type === NotificationType::CERTIFICATE_EARNED
                        && $data['child_name'] === $student->name
                        && $data['certificate_number'] === 'CERT-2024-001'
                        && $metadata['certificate_id'] === $certificate->id
                        && $metadata['child_id'] === $student->id
                        && $isCritical === false;
                });

            $this->service->sendCertificateIssued($certificate);
        });

        it('does not send notification if student not found', function () {
            $certificate = Certificate::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => 99999,
            ]);

            $this->notificationService->shouldReceive('send')->never();

            $this->service->sendCertificateIssued($certificate);
        });

        it('sends to multiple parents', function () {
            $parent1 = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $parent2 = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent1->id,
            ]);

            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent2->id,
            ]);

            $certificate = Certificate::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            $this->notificationService->shouldReceive('send')->twice();

            $this->service->sendCertificateIssued($certificate);
        });
    });

    describe('sendPaymentReminder()', function () {
        it('sends notification to parent for quran subscription payment', function () {
            $parent = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent->id,
            ]);

            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'final_price' => 500.00,
                'total_price' => 600.00,
                'currency' => 'SAR',
                'next_payment_at' => now()->addDays(5),
            ]);

            $this->notificationService->shouldReceive('send')
                ->once()
                ->withArgs(function ($user, $type, $data, $url, $metadata, $isCritical) use ($parent, $student, $subscription) {
                    return $user->id === $parent->user->id
                        && $type === NotificationType::SUBSCRIPTION_EXPIRING
                        && $data['child_name'] === $student->name
                        && $data['amount'] == 500.00
                        && $data['currency'] === 'SAR'
                        && $data['due_date'] === $subscription->next_payment_at->format('Y-m-d')
                        && $metadata['subscription_id'] === $subscription->id
                        && $metadata['child_id'] === $student->id
                        && $isCritical === true;
                });

            $this->service->sendPaymentReminder($subscription);
        });

        it('uses total price when final price is null', function () {
            $parent = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent->id,
            ]);

            $subscription = AcademicSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'final_price' => null,
                'total_price' => 600.00,
            ]);

            $this->notificationService->shouldReceive('send')
                ->once()
                ->withArgs(function ($user, $type, $data) {
                    return $data['amount'] == 600.00;
                });

            $this->service->sendPaymentReminder($subscription);
        });

        it('marks payment reminder as critical', function () {
            $parent = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent->id,
            ]);

            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            $this->notificationService->shouldReceive('send')
                ->once()
                ->withArgs(function ($user, $type, $data, $url, $metadata, $isCritical) {
                    return $isCritical === true;
                });

            $this->service->sendPaymentReminder($subscription);
        });

        it('does not send notification if student not found', function () {
            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => 99999,
            ]);

            $this->notificationService->shouldReceive('send')->never();

            $this->service->sendPaymentReminder($subscription);
        });

        it('sends to multiple parents', function () {
            $parent1 = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $parent2 = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent1->id,
            ]);

            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent2->id,
            ]);

            $subscription = QuranSubscription::factory()->create([
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
            ]);

            $this->notificationService->shouldReceive('send')->twice();

            $this->service->sendPaymentReminder($subscription);
        });
    });

    describe('sendQuizGraded()', function () {
        it('sends passed notification when quiz is passed', function () {
            $parent = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent->id,
            ]);

            $quizAssignment = QuizAssignment::factory()->create();
            $quizAttempt = QuizAttempt::factory()->create([
                'student_profile_id' => $studentProfile->id,
                'quiz_assignment_id' => $quizAssignment->id,
                'score' => 85,
                'passed' => true,
            ]);

            $this->notificationService->shouldReceive('send')
                ->once()
                ->withArgs(function ($user, $type, $data, $url, $metadata, $isCritical) use ($parent, $student, $quizAttempt) {
                    return $user->id === $parent->user->id
                        && $type === NotificationType::QUIZ_PASSED
                        && $data['child_name'] === $student->name
                        && $data['score'] === 85
                        && $data['passed'] === 'نجح'
                        && $metadata['quiz_attempt_id'] === $quizAttempt->id
                        && $metadata['child_id'] === $student->id
                        && $isCritical === false;
                });

            $this->service->sendQuizGraded($quizAttempt);
        });

        it('sends failed notification when quiz is failed', function () {
            $parent = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent->id,
            ]);

            $quizAssignment = QuizAssignment::factory()->create();
            $quizAttempt = QuizAttempt::factory()->create([
                'student_profile_id' => $studentProfile->id,
                'quiz_assignment_id' => $quizAssignment->id,
                'score' => 45,
                'passed' => false,
            ]);

            $this->notificationService->shouldReceive('send')
                ->once()
                ->withArgs(function ($user, $type, $data) {
                    return $type === NotificationType::QUIZ_FAILED
                        && $data['passed'] === 'لم ينجح';
                });

            $this->service->sendQuizGraded($quizAttempt);
        });

        it('does not send notification if student profile not found', function () {
            $quizAttempt = QuizAttempt::factory()->create([
                'student_profile_id' => 99999,
            ]);

            $this->notificationService->shouldReceive('send')->never();

            $this->service->sendQuizGraded($quizAttempt);
        });

        it('sends to multiple parents', function () {
            $parent1 = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $parent2 = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $studentProfile = StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent1->id,
            ]);

            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent2->id,
            ]);

            $quizAssignment = QuizAssignment::factory()->create();
            $quizAttempt = QuizAttempt::factory()->create([
                'student_profile_id' => $studentProfile->id,
                'quiz_assignment_id' => $quizAssignment->id,
                'passed' => true,
            ]);

            $this->notificationService->shouldReceive('send')->twice();

            $this->service->sendQuizGraded($quizAttempt);
        });
    });

    describe('getParentsForStudent()', function () {
        it('returns empty collection when student has no profile', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $parents = $this->service->getParentsForStudent($student);

            expect($parents)->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($parents)->toBeEmpty();
        });

        it('returns empty collection when student has no parents', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
            ]);

            $parents = $this->service->getParentsForStudent($student);

            expect($parents)->toBeEmpty();
        });

        it('returns parents linked to student', function () {
            $parent = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent->id,
            ]);

            $parents = $this->service->getParentsForStudent($student);

            expect($parents)->toHaveCount(1)
                ->and($parents->first()->id)->toBe($parent->id);
        });

        it('returns multiple parents for student', function () {
            $parent1 = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $parent2 = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent1->id,
            ]);

            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent2->id,
            ]);

            $parents = $this->service->getParentsForStudent($student);

            expect($parents)->toHaveCount(2);
        });

        it('only returns parents from same academy', function () {
            $otherAcademy = Academy::factory()->create();

            $parentSameAcademy = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $parentOtherAcademy = ParentProfile::factory()->create(['academy_id' => $otherAcademy->id]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parentSameAcademy->id,
            ]);

            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parentOtherAcademy->id,
            ]);

            $parents = $this->service->getParentsForStudent($student);

            expect($parents)->toHaveCount(1)
                ->and($parents->first()->id)->toBe($parentSameAcademy->id);
        });

        it('only returns parents with linked user accounts', function () {
            $parentWithUser = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $parentWithoutUser = ParentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => null,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parentWithUser->id,
            ]);

            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parentWithoutUser->id,
            ]);

            $parents = $this->service->getParentsForStudent($student);

            expect($parents)->toHaveCount(1)
                ->and($parents->first()->id)->toBe($parentWithUser->id);
        });

        it('eager loads user relationship', function () {
            $parent = ParentProfile::factory()->create(['academy_id' => $this->academy->id]);
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'user_id' => $student->id,
                'parent_id' => $parent->id,
            ]);

            $parents = $this->service->getParentsForStudent($student);

            expect($parents->first()->relationLoaded('user'))->toBeTrue();
        });
    });

    afterEach(function () {
        Mockery::close();
    });
});
