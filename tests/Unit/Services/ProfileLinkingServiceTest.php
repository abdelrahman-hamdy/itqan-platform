<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\ParentProfile;
use App\Models\QuranTeacherProfile;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ProfileLinkingService;

describe('ProfileLinkingService', function () {
    beforeEach(function () {
        $this->service = new ProfileLinkingService();
        $this->academy = Academy::factory()->create();
        $this->gradeLevel = AcademicGradeLevel::factory()->create([
            'academy_id' => $this->academy->id,
        ]);
    });

    describe('hasExistingProfile', function () {
        it('returns true when student profile exists with email', function () {
            $email = 'student@test.com';
            StudentProfile::factory()->create([
                'email' => $email,
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            expect($this->service->hasExistingProfile($email))->toBeTrue();
        });

        it('returns true when quran teacher profile exists with email', function () {
            $email = 'teacher@test.com';
            $user = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            QuranTeacherProfile::factory()->create([
                'email' => $email,
                'user_id' => $user->id,
                'academy_id' => $this->academy->id,
            ]);

            expect($this->service->hasExistingProfile($email))->toBeTrue();
        });

        it('returns true when parent profile exists with email', function () {
            $email = 'parent@test.com';
            ParentProfile::factory()->create([
                'email' => $email,
                'academy_id' => $this->academy->id,
            ]);

            expect($this->service->hasExistingProfile($email))->toBeTrue();
        });

        it('returns false when no profile exists with email', function () {
            expect($this->service->hasExistingProfile('nonexistent@test.com'))->toBeFalse();
        });
    });

    describe('getProfileTypeByEmail', function () {
        it('returns student type for student profile', function () {
            $email = 'student@test.com';
            StudentProfile::factory()->create([
                'email' => $email,
                'grade_level_id' => $this->gradeLevel->id,
            ]);

            expect($this->service->getProfileTypeByEmail($email))->toBe('طالب');
        });

        it('returns quran teacher type for quran teacher profile', function () {
            $email = 'quranteacher@test.com';
            $user = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            QuranTeacherProfile::factory()->create([
                'email' => $email,
                'user_id' => $user->id,
                'academy_id' => $this->academy->id,
            ]);

            expect($this->service->getProfileTypeByEmail($email))->toBe('معلم قرآن');
        });

        it('returns parent type for parent profile', function () {
            $email = 'parent@test.com';
            ParentProfile::factory()->create([
                'email' => $email,
                'academy_id' => $this->academy->id,
            ]);

            expect($this->service->getProfileTypeByEmail($email))->toBe('ولي أمر');
        });

        it('returns null for non-existent email', function () {
            expect($this->service->getProfileTypeByEmail('unknown@test.com'))->toBeNull();
        });
    });

    describe('getUnlinkedProfilesCount', function () {
        it('returns counts of unlinked profiles by type', function () {
            // Create unlinked student profiles
            StudentProfile::factory()->count(3)->create([
                'grade_level_id' => $this->gradeLevel->id,
                'user_id' => null,
            ]);

            // Create unlinked parent profiles
            ParentProfile::factory()->count(2)->create([
                'academy_id' => $this->academy->id,
                'user_id' => null,
            ]);

            $counts = $this->service->getUnlinkedProfilesCount();

            expect($counts)->toBeArray()
                ->and($counts)->toHaveKey('students')
                ->and($counts)->toHaveKey('parents')
                ->and($counts['students'])->toBeGreaterThanOrEqual(3)
                ->and($counts['parents'])->toBeGreaterThanOrEqual(2);
        });
    });

    describe('registerUserWithProfile', function () {
        it('returns error when profile does not exist', function () {
            $result = $this->service->registerUserWithProfile([
                'email' => 'nonexistent@test.com',
                'password' => 'password123',
            ]);

            expect($result['success'])->toBeFalse()
                ->and($result['profile'])->toBeNull()
                ->and($result['user'])->toBeNull();
        });

        it('returns error when profile is already linked', function () {
            $email = 'linked@test.com';
            $existingUser = User::factory()->student()->forAcademy($this->academy)->create();
            StudentProfile::factory()->create([
                'email' => $email,
                'grade_level_id' => $this->gradeLevel->id,
                'user_id' => $existingUser->id,
            ]);

            $result = $this->service->registerUserWithProfile([
                'email' => $email,
                'password' => 'password123',
            ]);

            expect($result['success'])->toBeFalse()
                ->and($result['user'])->toBeNull();
        });

        it('creates user and links to unlinked quran teacher profile', function () {
            // Using QuranTeacherProfile because student/parent profiles trigger auto-creation
            // when User is created, which causes duplicate email issues
            $email = 'unlinked-quran-'.uniqid().'@test.com';
            $profile = QuranTeacherProfile::factory()->create([
                'email' => $email,
                'academy_id' => $this->academy->id,
                'user_id' => null, // Unlinked profile
            ]);

            $result = $this->service->registerUserWithProfile([
                'email' => $email,
                'password' => 'password123',
            ]);

            expect($result['success'])->toBeTrue()
                ->and($result['user'])->toBeInstanceOf(User::class)
                ->and($result['user']->email)->toBe($email)
                ->and($result['user']->user_type)->toBe('quran_teacher')
                ->and($result['profile']->fresh()->user_id)->toBe($result['user']->id);
        });
    });
});
