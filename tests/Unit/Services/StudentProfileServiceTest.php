<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\StudentProfileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

describe('StudentProfileService', function () {
    beforeEach(function () {
        $this->service = new StudentProfileService();
        $this->academy = Academy::factory()->create();
    });

    describe('getOrCreateProfile()', function () {
        it('returns existing profile if it exists', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $gradeLevel = AcademicGradeLevel::factory()->forAcademy($this->academy)->create();
            $profile = StudentProfile::factory()->create([
                'user_id' => $user->id,
                'grade_level_id' => $gradeLevel->id,
            ]);

            $result = $this->service->getOrCreateProfile($user);

            expect($result)->not->toBeNull()
                ->and($result->id)->toBe($profile->id)
                ->and($result->user_id)->toBe($user->id);
        });

        it('creates new profile if none exists', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            AcademicGradeLevel::factory()->forAcademy($this->academy)->create();

            expect(StudentProfile::where('user_id', $user->id)->exists())->toBeFalse();

            $result = $this->service->getOrCreateProfile($user);

            expect($result)->not->toBeNull()
                ->and($result->user_id)->toBe($user->id)
                ->and(StudentProfile::where('user_id', $user->id)->exists())->toBeTrue();
        });

        it('returns null if profile creation fails', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) use ($user) {
                    return str_contains($message, 'Failed to create basic student profile')
                        && $context['user_id'] === $user->id;
                });

            StudentProfile::shouldReceive('withoutGlobalScopes')
                ->once()
                ->andThrow(new \Exception('Database error'));

            $result = $this->service->getOrCreateProfile($user);

            expect($result)->toBeNull();
        });
    });

    describe('createBasicProfile()', function () {
        it('creates basic profile with default values', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create([
                'first_name' => 'أحمد',
                'last_name' => 'محمد',
                'email' => 'ahmed@test.com',
            ]);
            $gradeLevel = AcademicGradeLevel::factory()->forAcademy($this->academy)->create();

            $result = $this->service->createBasicProfile($user);

            expect($result)->not->toBeNull()
                ->and($result->user_id)->toBe($user->id)
                ->and($result->email)->toBe('ahmed@test.com')
                ->and($result->first_name)->toBe('أحمد')
                ->and($result->last_name)->toBe('محمد')
                ->and($result->student_code)->toStartWith('STU')
                ->and($result->grade_level_id)->toBe($gradeLevel->id)
                ->and($result->enrollment_date)->not->toBeNull()
                ->and($result->notes)->toContain('تم إنشاء الملف الشخصي تلقائياً');
        });

        it('uses default names when user has no name', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create([
                'first_name' => null,
                'last_name' => null,
            ]);
            AcademicGradeLevel::factory()->forAcademy($this->academy)->create();

            $result = $this->service->createBasicProfile($user);

            expect($result)->not->toBeNull()
                ->and($result->first_name)->toBe('طالب')
                ->and($result->last_name)->toBe('جديد');
        });

        it('returns existing orphaned profile if found', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $gradeLevel = AcademicGradeLevel::factory()->forAcademy($this->academy)->create();

            $existingProfile = StudentProfile::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'email' => $user->email,
                'first_name' => 'Existing',
                'last_name' => 'Profile',
                'student_code' => 'STU000001',
                'grade_level_id' => $gradeLevel->id,
                'enrollment_date' => now(),
            ]);

            $result = $this->service->createBasicProfile($user);

            expect($result)->not->toBeNull()
                ->and($result->id)->toBe($existingProfile->id)
                ->and($result->first_name)->toBe('Existing');
        });

        it('generates unique student code', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            AcademicGradeLevel::factory()->forAcademy($this->academy)->create();

            $result = $this->service->createBasicProfile($user);

            expect($result)->not->toBeNull()
                ->and($result->student_code)->toMatch('/^STU\d{6}$/');
        });

        it('finds default grade level for user academy', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $gradeLevel1 = AcademicGradeLevel::factory()->forAcademy($this->academy)->create([
                'name' => 'ثانوي',
                'is_active' => true,
            ]);
            $gradeLevel2 = AcademicGradeLevel::factory()->forAcademy($this->academy)->create([
                'name' => 'ابتدائي',
                'is_active' => true,
            ]);

            $result = $this->service->createBasicProfile($user);

            expect($result)->not->toBeNull()
                ->and($result->grade_level_id)->toBe($gradeLevel2->id);
        });

        it('handles no active grade levels gracefully', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            AcademicGradeLevel::factory()->forAcademy($this->academy)->create([
                'is_active' => false,
            ]);

            $result = $this->service->createBasicProfile($user);

            expect($result)->not->toBeNull()
                ->and($result->grade_level_id)->toBeNull();
        });

        it('logs success when profile is created', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            AcademicGradeLevel::factory()->forAcademy($this->academy)->create();

            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) use ($user) {
                    return str_contains($message, 'Created basic student profile')
                        && $context['user_id'] === $user->id
                        && isset($context['profile_id']);
                });

            $this->service->createBasicProfile($user);
        });

        it('returns null and logs error on exception', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) use ($user) {
                    return str_contains($message, 'Failed to create basic student profile')
                        && $context['user_id'] === $user->id
                        && isset($context['error']);
                });

            StudentProfile::shouldReceive('withoutGlobalScopes')
                ->once()
                ->andThrow(new \Exception('Database error'));

            $result = $this->service->createBasicProfile($user);

            expect($result)->toBeNull();
        });
    });

    describe('updateProfile()', function () {
        it('updates profile with validated data', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $gradeLevel = AcademicGradeLevel::factory()->forAcademy($this->academy)->create();
            $profile = StudentProfile::factory()->create([
                'user_id' => $user->id,
                'grade_level_id' => $gradeLevel->id,
                'first_name' => 'Old Name',
            ]);

            $data = [
                'first_name' => 'New Name',
                'last_name' => 'Updated',
            ];

            $result = $this->service->updateProfile($profile, $data);

            expect($result)->not->toBeNull()
                ->and($result->first_name)->toBe('New Name')
                ->and($result->last_name)->toBe('Updated');
        });

        it('handles avatar upload', function () {
            Storage::fake('public');

            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $gradeLevel = AcademicGradeLevel::factory()->forAcademy($this->academy)->create();
            $profile = StudentProfile::factory()->create([
                'user_id' => $user->id,
                'grade_level_id' => $gradeLevel->id,
                'avatar' => null,
            ]);

            $avatarFile = UploadedFile::fake()->image('avatar.jpg');
            $data = ['first_name' => 'Test'];

            $result = $this->service->updateProfile($profile, $data, $avatarFile);

            expect($result->avatar)->not->toBeNull()
                ->and($result->avatar)->toContain('avatars/');

            Storage::disk('public')->assertExists($result->avatar);
        });

        it('deletes old avatar when uploading new one', function () {
            Storage::fake('public');

            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $gradeLevel = AcademicGradeLevel::factory()->forAcademy($this->academy)->create();

            $oldAvatar = UploadedFile::fake()->image('old_avatar.jpg')->store('avatars', 'public');

            $profile = StudentProfile::factory()->create([
                'user_id' => $user->id,
                'grade_level_id' => $gradeLevel->id,
                'avatar' => $oldAvatar,
            ]);

            Storage::disk('public')->assertExists($oldAvatar);

            $newAvatarFile = UploadedFile::fake()->image('new_avatar.jpg');
            $data = ['first_name' => 'Test'];

            $result = $this->service->updateProfile($profile, $data, $newAvatarFile);

            Storage::disk('public')->assertMissing($oldAvatar);
            Storage::disk('public')->assertExists($result->avatar);
        });

        it('returns fresh profile after update', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $gradeLevel = AcademicGradeLevel::factory()->forAcademy($this->academy)->create();
            $profile = StudentProfile::factory()->create([
                'user_id' => $user->id,
                'grade_level_id' => $gradeLevel->id,
                'first_name' => 'Old',
            ]);

            $data = ['first_name' => 'New'];

            $result = $this->service->updateProfile($profile, $data);

            $freshProfile = StudentProfile::find($profile->id);
            expect($result->first_name)->toBe($freshProfile->first_name)
                ->and($result->updated_at->timestamp)->toBe($freshProfile->updated_at->timestamp);
        });

        it('updates profile without avatar if not provided', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $gradeLevel = AcademicGradeLevel::factory()->forAcademy($this->academy)->create();
            $profile = StudentProfile::factory()->create([
                'user_id' => $user->id,
                'grade_level_id' => $gradeLevel->id,
                'first_name' => 'Old',
                'avatar' => null,
            ]);

            $data = ['first_name' => 'New'];

            $result = $this->service->updateProfile($profile, $data, null);

            expect($result->first_name)->toBe('New')
                ->and($result->avatar)->toBeNull();
        });
    });

    describe('generateUniqueStudentCode()', function () {
        it('generates code with STU prefix and padded user id', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create(['id' => 123]);

            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('generateUniqueStudentCode');
            $method->setAccessible(true);

            $code = $method->invoke($this->service, $user);

            expect($code)->toMatch('/^STU\d{6}(-\d+)?$/');
        });

        it('adds suffix if code already exists', function () {
            $user1 = User::factory()->student()->forAcademy($this->academy)->create();
            $user2 = User::factory()->student()->forAcademy($this->academy)->create();
            $gradeLevel = AcademicGradeLevel::factory()->forAcademy($this->academy)->create();

            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('generateUniqueStudentCode');
            $method->setAccessible(true);

            $code1 = $method->invoke($this->service, $user1);

            StudentProfile::factory()->create([
                'user_id' => $user1->id,
                'grade_level_id' => $gradeLevel->id,
                'student_code' => $code1,
            ]);

            $baseCode = 'STU' . str_pad($user2->id, 6, '0', STR_PAD_LEFT);
            StudentProfile::factory()->create([
                'user_id' => $user1->id,
                'grade_level_id' => $gradeLevel->id,
                'student_code' => $baseCode,
            ]);

            $code2 = $method->invoke($this->service, $user2);

            expect($code2)->toContain('-')
                ->and($code2)->toStartWith('STU');
        });

        it('increments suffix until unique code is found', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $gradeLevel = AcademicGradeLevel::factory()->forAcademy($this->academy)->create();

            $baseCode = 'STU' . str_pad($user->id, 6, '0', STR_PAD_LEFT);

            StudentProfile::factory()->create([
                'user_id' => $user->id,
                'grade_level_id' => $gradeLevel->id,
                'student_code' => $baseCode,
            ]);
            StudentProfile::factory()->create([
                'user_id' => $user->id,
                'grade_level_id' => $gradeLevel->id,
                'student_code' => $baseCode . '-1',
            ]);
            StudentProfile::factory()->create([
                'user_id' => $user->id,
                'grade_level_id' => $gradeLevel->id,
                'student_code' => $baseCode . '-2',
            ]);

            $reflection = new \ReflectionClass($this->service);
            $method = $reflection->getMethod('generateUniqueStudentCode');
            $method->setAccessible(true);

            $code = $method->invoke($this->service, $user);

            expect($code)->toBe($baseCode . '-3');
        });
    });

    describe('getGradeLevels()', function () {
        it('returns grade levels for user academy', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();
            $otherAcademy = Academy::factory()->create();

            $gradeLevel1 = AcademicGradeLevel::factory()->forAcademy($this->academy)->create([
                'name' => 'الصف الأول',
                'is_active' => true,
            ]);
            $gradeLevel2 = AcademicGradeLevel::factory()->forAcademy($this->academy)->create([
                'name' => 'الصف الثاني',
                'is_active' => true,
            ]);
            AcademicGradeLevel::factory()->forAcademy($otherAcademy)->create([
                'is_active' => true,
            ]);

            $result = $this->service->getGradeLevels($user);

            expect($result)->toHaveCount(2)
                ->and($result->pluck('id')->toArray())->toContain($gradeLevel1->id)
                ->and($result->pluck('id')->toArray())->toContain($gradeLevel2->id);
        });

        it('returns only active grade levels', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $activeGrade = AcademicGradeLevel::factory()->forAcademy($this->academy)->create([
                'is_active' => true,
            ]);
            AcademicGradeLevel::factory()->forAcademy($this->academy)->create([
                'is_active' => false,
            ]);

            $result = $this->service->getGradeLevels($user);

            expect($result)->toHaveCount(1)
                ->and($result->first()->id)->toBe($activeGrade->id)
                ->and($result->first()->is_active)->toBeTrue();
        });

        it('returns grade levels in ordered sequence', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            AcademicGradeLevel::factory()->forAcademy($this->academy)->create([
                'name' => 'ثالث',
                'is_active' => true,
            ]);
            AcademicGradeLevel::factory()->forAcademy($this->academy)->create([
                'name' => 'أول',
                'is_active' => true,
            ]);
            AcademicGradeLevel::factory()->forAcademy($this->academy)->create([
                'name' => 'ثاني',
                'is_active' => true,
            ]);

            $result = $this->service->getGradeLevels($user);

            expect($result)->toHaveCount(3);
        });

        it('returns empty collection when no grade levels exist', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $result = $this->service->getGradeLevels($user);

            expect($result)->toBeEmpty();
        });
    });
});
