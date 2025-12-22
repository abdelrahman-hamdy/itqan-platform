<?php

use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\QuranSessionHomework;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use App\Services\QuranHomeworkService;
use Illuminate\Support\Facades\Log;

describe('QuranHomeworkService', function () {
    beforeEach(function () {
        $this->service = new QuranHomeworkService();
        $this->academy = Academy::factory()->create();
        $this->teacherUser = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $this->teacherProfile = QuranTeacherProfile::factory()->create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->teacherUser->id,
        ]);
        $this->student = User::factory()->student()->forAcademy($this->academy)->create();
        $this->session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $this->teacherProfile->id,
            'student_id' => $this->student->id,
        ]);

        $this->actingAs($this->teacherUser);
    });

    describe('createSessionHomework', function () {
        it('creates session homework with new memorization', function () {
            $homeworkData = [
                'has_new_memorization' => true,
                'new_memorization_pages' => 2.5,
                'new_memorization_surah' => 'البقرة',
                'new_memorization_from_verse' => 1,
                'new_memorization_to_verse' => 10,
                'has_review' => false,
                'has_comprehensive_review' => false,
                'review_pages' => 0,
                'difficulty_level' => 'medium',
                'due_date' => now()->addDays(3),
                'is_active' => true,
            ];

            $homework = $this->service->createSessionHomework($this->session, $homeworkData);

            expect($homework)->toBeInstanceOf(QuranSessionHomework::class)
                ->and($homework->session_id)->toBe($this->session->id)
                ->and($homework->created_by)->toBe($this->teacherUser->id)
                ->and($homework->has_new_memorization)->toBeTrue()
                ->and((float) $homework->new_memorization_pages)->toBe(2.5)
                ->and($homework->new_memorization_surah)->toBe('البقرة')
                ->and($homework->new_memorization_from_verse)->toBe(1)
                ->and($homework->new_memorization_to_verse)->toBe(10)
                ->and($homework->difficulty_level)->toBe('medium');
        });

        it('creates session homework with review', function () {
            $homeworkData = [
                'has_new_memorization' => false,
                'new_memorization_pages' => 0,
                'has_review' => true,
                'review_pages' => 3.0,
                'review_surah' => 'آل عمران',
                'review_from_verse' => 50,
                'review_to_verse' => 100,
                'has_comprehensive_review' => false,
                'difficulty_level' => 'easy',
                'due_date' => now()->addDays(2),
                'is_active' => true,
            ];

            $homework = $this->service->createSessionHomework($this->session, $homeworkData);

            expect($homework)->toBeInstanceOf(QuranSessionHomework::class)
                ->and($homework->has_review)->toBeTrue()
                ->and((float) $homework->review_pages)->toBe(3.0)
                ->and($homework->review_surah)->toBe('آل عمران')
                ->and($homework->review_from_verse)->toBe(50)
                ->and($homework->review_to_verse)->toBe(100);
        });

        it('creates session homework with comprehensive review', function () {
            $homeworkData = [
                'has_new_memorization' => false,
                'new_memorization_pages' => 0,
                'has_review' => false,
                'review_pages' => 0,
                'has_comprehensive_review' => true,
                'comprehensive_review_surahs' => ['الفاتحة', 'الإخلاص', 'الفلق'],
                'difficulty_level' => 'hard',
                'due_date' => now()->addWeek(),
                'is_active' => true,
            ];

            $homework = $this->service->createSessionHomework($this->session, $homeworkData);

            expect($homework)->toBeInstanceOf(QuranSessionHomework::class)
                ->and($homework->has_comprehensive_review)->toBeTrue()
                ->and($homework->comprehensive_review_surahs)->toBeArray()
                ->and($homework->comprehensive_review_surahs)->toHaveCount(3)
                ->and($homework->comprehensive_review_surahs)->toContain('الفاتحة');
        });

        it('creates session homework with all homework types', function () {
            $homeworkData = [
                'has_new_memorization' => true,
                'new_memorization_pages' => 1.5,
                'new_memorization_surah' => 'الكهف',
                'new_memorization_from_verse' => 1,
                'new_memorization_to_verse' => 20,
                'has_review' => true,
                'review_pages' => 2.0,
                'review_surah' => 'يس',
                'review_from_verse' => 1,
                'review_to_verse' => 83,
                'has_comprehensive_review' => true,
                'comprehensive_review_surahs' => ['الملك', 'الواقعة'],
                'additional_instructions' => 'Focus on tajweed rules',
                'difficulty_level' => 'medium',
                'due_date' => now()->addDays(5),
                'is_active' => true,
            ];

            $homework = $this->service->createSessionHomework($this->session, $homeworkData);

            expect($homework->has_new_memorization)->toBeTrue()
                ->and($homework->has_review)->toBeTrue()
                ->and($homework->has_comprehensive_review)->toBeTrue()
                ->and($homework->additional_instructions)->toBe('Focus on tajweed rules')
                ->and($homework->total_pages)->toBe(3.5);
        });

        it('creates session homework with additional instructions', function () {
            $homeworkData = [
                'has_new_memorization' => true,
                'new_memorization_pages' => 1.0,
                'new_memorization_surah' => 'الرحمن',
                'has_review' => false,
                'has_comprehensive_review' => false,
                'review_pages' => 0,
                'additional_instructions' => 'Pay attention to Makharij',
                'difficulty_level' => 'medium',
                'due_date' => now()->addDays(3),
                'is_active' => true,
            ];

            $homework = $this->service->createSessionHomework($this->session, $homeworkData);

            expect($homework->additional_instructions)->toBe('Pay attention to Makharij');
        });

        it('logs homework creation', function () {
            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) {
                    return $message === 'Session homework created'
                        && isset($context['session_id'])
                        && isset($context['homework_id'])
                        && isset($context['created_by']);
                });

            $homeworkData = [
                'has_new_memorization' => true,
                'new_memorization_pages' => 1.0,
                'new_memorization_surah' => 'الفاتحة',
                'has_review' => false,
                'has_comprehensive_review' => false,
                'review_pages' => 0,
                'difficulty_level' => 'easy',
                'due_date' => now()->addDays(1),
                'is_active' => true,
            ];

            $this->service->createSessionHomework($this->session, $homeworkData);
        });

        it('wraps homework creation in transaction', function () {
            $homeworkData = [
                'has_new_memorization' => true,
                'new_memorization_pages' => 1.0,
                'new_memorization_surah' => 'الناس',
                'has_review' => false,
                'has_comprehensive_review' => false,
                'review_pages' => 0,
                'difficulty_level' => 'easy',
                'due_date' => now()->addDays(1),
                'is_active' => true,
            ];

            $homework = $this->service->createSessionHomework($this->session, $homeworkData);

            expect($homework->exists)->toBeTrue();
        });
    });

    describe('updateSessionHomework', function () {
        beforeEach(function () {
            $this->homework = QuranSessionHomework::factory()->create([
                'session_id' => $this->session->id,
                'created_by' => $this->teacherUser->id,
                'has_new_memorization' => true,
                'new_memorization_pages' => 1.0,
                'new_memorization_surah' => 'البقرة',
                'difficulty_level' => 'easy',
            ]);
        });

        it('updates session homework', function () {
            $updateData = [
                'new_memorization_pages' => 2.0,
                'new_memorization_surah' => 'آل عمران',
                'difficulty_level' => 'medium',
            ];

            $updated = $this->service->updateSessionHomework($this->homework, $updateData);

            expect($updated)->toBeInstanceOf(QuranSessionHomework::class)
                ->and((float) $updated->new_memorization_pages)->toBe(2.0)
                ->and($updated->new_memorization_surah)->toBe('آل عمران')
                ->and($updated->difficulty_level)->toBe('medium');
        });

        it('updates homework additional instructions', function () {
            $updateData = [
                'additional_instructions' => 'Updated instructions for better memorization',
            ];

            $updated = $this->service->updateSessionHomework($this->homework, $updateData);

            expect($updated->additional_instructions)->toBe('Updated instructions for better memorization');
        });

        it('updates homework due date', function () {
            $newDueDate = now()->addWeek();
            $updateData = [
                'due_date' => $newDueDate,
            ];

            $updated = $this->service->updateSessionHomework($this->homework, $updateData);

            expect($updated->due_date->toDateString())->toBe($newDueDate->toDateString());
        });

        it('updates homework types', function () {
            $updateData = [
                'has_review' => true,
                'review_pages' => 3.0,
                'review_surah' => 'النساء',
                'review_from_verse' => 1,
                'review_to_verse' => 50,
            ];

            $updated = $this->service->updateSessionHomework($this->homework, $updateData);

            expect($updated->has_review)->toBeTrue()
                ->and((float) $updated->review_pages)->toBe(3.0)
                ->and($updated->review_surah)->toBe('النساء');
        });

        it('updates comprehensive review surahs', function () {
            $updateData = [
                'has_comprehensive_review' => true,
                'comprehensive_review_surahs' => ['الملك', 'الواقعة', 'الرحمن'],
            ];

            $updated = $this->service->updateSessionHomework($this->homework, $updateData);

            expect($updated->has_comprehensive_review)->toBeTrue()
                ->and($updated->comprehensive_review_surahs)->toHaveCount(3);
        });

        it('logs homework update', function () {
            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) {
                    return $message === 'Session homework updated'
                        && isset($context['homework_id'])
                        && isset($context['session_id'])
                        && isset($context['updated_by']);
                });

            $updateData = [
                'difficulty_level' => 'hard',
            ];

            $this->service->updateSessionHomework($this->homework, $updateData);
        });

        it('wraps homework update in transaction', function () {
            $updateData = [
                'new_memorization_pages' => 5.0,
            ];

            $updated = $this->service->updateSessionHomework($this->homework, $updateData);

            expect((float) $updated->fresh()->new_memorization_pages)->toBe(5.0);
        });

        it('updates homework difficulty level', function () {
            $updateData = [
                'difficulty_level' => 'hard',
            ];

            $updated = $this->service->updateSessionHomework($this->homework, $updateData);

            expect($updated->difficulty_level)->toBe('hard')
                ->and($updated->difficulty_level_arabic)->toBe('صعب');
        });
    });

    describe('deleteSessionHomework', function () {
        beforeEach(function () {
            $this->homework = QuranSessionHomework::factory()->create([
                'session_id' => $this->session->id,
                'created_by' => $this->teacherUser->id,
            ]);
        });

        it('deletes session homework', function () {
            $homeworkId = $this->homework->id;

            $result = $this->service->deleteSessionHomework($this->homework);

            expect($result)->toBeTrue()
                ->and(QuranSessionHomework::find($homeworkId))->toBeNull();
        });

        it('logs homework deletion', function () {
            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) {
                    return $message === 'Session homework deleted'
                        && isset($context['homework_id'])
                        && isset($context['session_id'])
                        && isset($context['deleted_by']);
                });

            $this->service->deleteSessionHomework($this->homework);
        });

        it('wraps homework deletion in transaction', function () {
            $homeworkId = $this->homework->id;

            $result = $this->service->deleteSessionHomework($this->homework);

            expect($result)->toBeTrue()
                ->and(QuranSessionHomework::find($homeworkId))->toBeNull();
        });

        it('returns false when deletion fails', function () {
            $homework = Mockery::mock(QuranSessionHomework::class);
            $homework->shouldReceive('delete')->andReturn(false);
            $homework->id = 1;
            $homework->session_id = $this->session->id;

            Log::shouldReceive('info');

            $result = $this->service->deleteSessionHomework($homework);

            expect($result)->toBeFalse();
        });
    });

    describe('getSessionHomeworkDetails', function () {
        it('returns no homework when session has no homework', function () {
            $details = $this->service->getSessionHomeworkDetails($this->session);

            expect($details)->toBeArray()
                ->and($details['has_homework'])->toBeFalse()
                ->and($details['homework'])->toBeNull();
        });

        it('returns homework details when homework exists', function () {
            $homework = QuranSessionHomework::factory()->create([
                'session_id' => $this->session->id,
                'created_by' => $this->teacherUser->id,
                'has_new_memorization' => true,
                'new_memorization_pages' => 2.0,
                'new_memorization_surah' => 'البقرة',
                'new_memorization_from_verse' => 1,
                'new_memorization_to_verse' => 10,
                'has_review' => true,
                'review_pages' => 1.5,
                'review_surah' => 'آل عمران',
                'review_from_verse' => 20,
                'review_to_verse' => 40,
                'difficulty_level' => 'medium',
                'additional_instructions' => 'Test instructions',
                'due_date' => now()->addDays(3),
            ]);

            $details = $this->service->getSessionHomeworkDetails($this->session);

            expect($details['has_homework'])->toBeTrue()
                ->and($details['homework'])->toBeInstanceOf(QuranSessionHomework::class)
                ->and($details['total_pages'])->toBe(3.5)
                ->and($details['new_memorization_pages'])->toBe(2.0)
                ->and($details['review_pages'])->toBe(1.5)
                ->and($details['new_memorization_range'])->toContain('البقرة')
                ->and($details['review_range'])->toContain('آل عمران')
                ->and($details['difficulty_level'])->toBe('متوسط')
                ->and($details['additional_instructions'])->toBe('Test instructions')
                ->and($details)->toHaveKey('due_date')
                ->and($details)->toHaveKey('is_overdue');
        });

        it('returns homework with comprehensive review', function () {
            $homework = QuranSessionHomework::factory()->create([
                'session_id' => $this->session->id,
                'created_by' => $this->teacherUser->id,
                'has_new_memorization' => false,
                'new_memorization_pages' => 0,
                'has_review' => false,
                'review_pages' => 0,
                'has_comprehensive_review' => true,
                'comprehensive_review_surahs' => ['الملك', 'الواقعة', 'الرحمن'],
                'difficulty_level' => 'hard',
            ]);

            $details = $this->service->getSessionHomeworkDetails($this->session);

            expect($details['has_homework'])->toBeTrue()
                ->and($details['comprehensive_review_surahs'])->toContain('الملك')
                ->and($details['difficulty_level'])->toBe('صعب');
        });

        it('returns all expected keys in homework details', function () {
            QuranSessionHomework::factory()->create([
                'session_id' => $this->session->id,
                'created_by' => $this->teacherUser->id,
            ]);

            $details = $this->service->getSessionHomeworkDetails($this->session);

            expect($details)->toHaveKeys([
                'has_homework',
                'homework',
                'total_pages',
                'new_memorization_pages',
                'review_pages',
                'new_memorization_range',
                'review_range',
                'comprehensive_review_surahs',
                'difficulty_level',
                'due_date',
                'is_overdue',
                'additional_instructions',
            ]);
        });

        it('returns null ranges when no surah is set', function () {
            QuranSessionHomework::factory()->create([
                'session_id' => $this->session->id,
                'created_by' => $this->teacherUser->id,
                'has_new_memorization' => false,
                'new_memorization_surah' => null,
                'has_review' => false,
                'review_surah' => null,
            ]);

            $details = $this->service->getSessionHomeworkDetails($this->session);

            expect($details['new_memorization_range'])->toBeNull()
                ->and($details['review_range'])->toBeNull();
        });

        it('formats difficulty level to arabic', function () {
            QuranSessionHomework::factory()->create([
                'session_id' => $this->session->id,
                'created_by' => $this->teacherUser->id,
                'difficulty_level' => 'easy',
            ]);

            $details = $this->service->getSessionHomeworkDetails($this->session);

            expect($details['difficulty_level'])->toBe('سهل');
        });

        it('handles overdue homework', function () {
            QuranSessionHomework::factory()->create([
                'session_id' => $this->session->id,
                'created_by' => $this->teacherUser->id,
                'due_date' => now()->subDays(1),
            ]);

            $details = $this->service->getSessionHomeworkDetails($this->session);

            expect($details['is_overdue'])->toBeTrue();
        });

        it('handles future due date homework', function () {
            QuranSessionHomework::factory()->create([
                'session_id' => $this->session->id,
                'created_by' => $this->teacherUser->id,
                'due_date' => now()->addDays(3),
            ]);

            $details = $this->service->getSessionHomeworkDetails($this->session);

            expect($details['is_overdue'])->toBeFalse();
        });
    });
});
